<?php
/** Explicit MySQL/MariaDB schema operations, shared by administration callers.
 * DDL is not transactional. Never invoke creation/removal during page rendering.
 * @author Derek Keats
 */
final class ChisimbaDatabaseSchema
{
    public function __construct(private dbTable $table)
    {
        if (!in_array($table->dbType, ['mysql','mysqli'], true)) {
            throw new RuntimeException('Schema operations require MySQL or MariaDB.');
        }
    }

    public static function identifier(string $name): string
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D', $name)) {
            throw new InvalidArgumentException('Invalid schema identifier.');
        }
        return '`' . $name . '`';
    }

    public function tableExists(string $name): bool
    {
        return (bool)$this->table->selectExactPrepared('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND TABLE_TYPE=?', [$name, 'BASE TABLE']);
    }

    public function columns(string $name): array
    {
        return array_column($this->table->selectExactPrepared('SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$name]), 'name');
    }

    public function requireTransactional(string $name): void
    {
        $rows = $this->table->selectExactPrepared('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$name]);
        if (strcasecmp($rows[0]['engine'] ?? '', 'InnoDB') !== 0) {
            throw new RuntimeException('Writes require transactional InnoDB tables.');
        }
    }

    /** Named text columns: length=null means LONGTEXT; nullability is explicit. */
    public function createTextTable(string $name, array $columns): void
    {
        $this->requireOutsideTransaction();
        $fields = [];
        foreach ($columns as $column => $spec) {
            $length = $spec['length'] ?? null;
            if ($length !== null && (!is_int($length) || $length < 1 || $length > 16383)) {
                throw new InvalidArgumentException('Invalid text column length.');
            }
            $fields[] = self::identifier($column) . ' ' . ($length === null ? 'LONGTEXT' : 'VARCHAR(' . $length . ')')
                . (!empty($spec['notnull']) ? ' NOT NULL' : ' NULL');
        }
        if (!$fields) throw new InvalidArgumentException('Columns are required.');
        $this->table->executeExactPrepared('CREATE TABLE ' . self::identifier($name) . ' (' . implode(',', $fields) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function dropTable(string $name): void
    {
        $this->requireOutsideTransaction();
        $this->table->executeExactPrepared('DROP TABLE ' . self::identifier($name));
    }

    /** Compile registered portable column definitions without a vendor manager. */
    public function columnDefinition(string $name, array $field): string
    {
        $type=$field['type']??'';
        $length=isset($field['length'])?(int)$field['length']:0;
        if ($length<0) throw new InvalidArgumentException('Invalid column length.');
        $sqlType=match($type) {
            'text','char'=>!empty($field['fixed'])||$type==='char' ? 'CHAR('.($length?:255).')' : ($length?'VARCHAR('.$length.')':(array_key_exists('default',$field)?'VARCHAR(255)':'TEXT')),
            'clob','blob'=>($length && $length<=255?'TINY':($length && $length<=65532?'':($length && $length<=16777215?'MEDIUM':'LONG'))).($type==='clob'?'TEXT':'BLOB'),
            'integer'=>match(true) {$length===1=>'TINYINT',$length===2=>'SMALLINT',$length===3=>'MEDIUMINT',$length>4=>'BIGINT',default=>'INT'},
            'boolean'=>'TINYINT(1)', 'date'=>'DATE', 'time'=>'TIME', 'timestamp'=>'DATETIME',
            'float'=>'DOUBLE'.($length?'('.$length.(!empty($field['scale'])?','.(int)$field['scale']:'').')':''),
            'decimal'=>'DECIMAL('.($length?:18).','.(int)($field['scale']??2).')',
            default=>throw new InvalidArgumentException('Unsupported registered column type.')
        };
        $sql=self::identifier($name).' '.$sqlType;
        if (!empty($field['unsigned']) && in_array($type,['integer','float','decimal'],true)) $sql.=' UNSIGNED';
        $sql.=!empty($field['notnull'])?' NOT NULL':' NULL';
        if (!empty($field['autoincrement'])) return $sql.' AUTO_INCREMENT PRIMARY KEY';
        if (array_key_exists('default',$field) && !($field['default']===null && !empty($field['notnull']))) {
            $value=$field['default'];
            // Preserve the historical schema default; dbTable trims this on reads.
            if ($type==='text' && $value==='' && !empty($field['notnull'])) $value=' ';
            if (in_array($type,['integer','boolean','float','decimal'],true) && $value==='') $value=empty($field['notnull'])?null:0;
            $sql.=' DEFAULT '.$this->literal($value);
        }
        return $sql;
    }

    /** DDL cannot bind defaults/comments. Ask the canonical connection to quote. */
    private function literal($value): string
    {
        if ($value===null) return 'NULL';
        if (is_bool($value)) return $value?'1':'0';
        $row=$this->table->selectExactPrepared('SELECT QUOTE(?) AS literal',[(string)$value]);
        return $row[0]['literal'];
    }

    public function createTable(string $name,array $fields,array $options=[]): bool
    {
        $this->requireOutsideTransaction();
        $fields['puid']=['type'=>'integer','length'=>50,'autoincrement'=>true];
        $parts=[]; foreach ($fields as $column=>$spec) $parts[]=$this->columnDefinition($column,$spec);
        $charset=$options['charset']??$options['character_set']??'utf8';
        $collation=$options['collate']??($charset==='utf8mb4'?'utf8mb4_unicode_ci':'utf8_unicode_ci');
        foreach ([$charset,$collation] as $token) if (!preg_match('/^[a-zA-Z0-9_]+$/D',$token)) throw new InvalidArgumentException('Invalid encoding identifier.');
        // Historical createPK used createIndex: the application ID is indexed,
        // while puid is the real primary key. Do not silently add uniqueness.
        if (isset($fields['id'])) $parts[]='KEY `chisimba_id_idx` (`id`)';
        $sql='CREATE TABLE '.self::identifier($name).' ('.implode(',',$parts).') ENGINE=InnoDB DEFAULT CHARSET='.$charset.' COLLATE='.$collation;
        if (isset($options['comment'])) $sql.=' COMMENT='.$this->literal($options['comment']);
        $this->table->executeExactPrepared($sql);
        return true;
    }

    /** Match catalogue createIndex semantics; unique/primary belong to constraints. */
    public function createIndex(string $table,string $name,array $definition,bool $constraint=false): bool
    {
        $this->requireOutsideTransaction();
        $columns=[];
        foreach ($definition['fields']??[] as $field=>$spec) {
            $part=self::identifier($field);
            if (!empty($spec['length'])) { if ((int)$spec['length']<1) throw new InvalidArgumentException('Invalid index prefix.'); $part.='('.(int)$spec['length'].')'; }
            $columns[]=$part;
        }
        if (!$columns) throw new InvalidArgumentException('Index fields are required.');
        $kind=$constraint&&!empty($definition['primary'])?'PRIMARY KEY':($constraint&&!empty($definition['unique'])?'UNIQUE INDEX '.self::identifier($name):'INDEX '.self::identifier($name));
        if (!empty($definition['foreign'])) throw new InvalidArgumentException('Foreign constraints require an explicit schema migration.');
        $this->table->executeExactPrepared('ALTER TABLE '.self::identifier($table).' ADD '.$kind.' ('.implode(',',$columns).')');
        return true;
    }

    public function indexes(string $name): array
    {
        return $this->table->selectExactPrepared('SHOW INDEX FROM '.self::identifier($name));
    }

    public function dropIndex(string $table,string $name): bool
    {
        $this->requireOutsideTransaction();
        $this->table->executeExactPrepared('ALTER TABLE '.self::identifier($table).' DROP '.(strtoupper($name)==='PRIMARY'?'PRIMARY KEY':'INDEX '.self::identifier($name)));
        return true;
    }

    public function tables(): array
    {
        return array_column($this->table->selectExactPrepared('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE=? ORDER BY TABLE_NAME',['BASE TABLE']),'name');
    }

    /** Validate every clause before executing one ALTER; check=true never writes. */
    public function alterTable(string $name,array $changes,bool $check=false): bool
    {
        $this->requireOutsideTransaction();
        $clauses=[];
        foreach ($changes as $operation=>$definitions) {
            switch ($operation) {
                case 'add': foreach ($definitions as $column=>$spec) $clauses[]='ADD COLUMN '.$this->columnDefinition($column,$spec); break;
                case 'remove': foreach ($definitions as $column=>$spec) $clauses[]='DROP COLUMN '.self::identifier(is_int($column)?$spec:$column); break;
                case 'change': foreach ($definitions as $column=>$spec) $clauses[]='MODIFY COLUMN '.$this->columnDefinition($column,$spec['definition']??$spec); break;
                case 'rename': foreach ($definitions as $column=>$spec) $clauses[]='RENAME COLUMN '.self::identifier($column).' TO '.self::identifier(is_array($spec)?$spec['name']:$spec); break;
                case 'name': $clauses[]='RENAME TO '.self::identifier($definitions); break;
                default: throw new InvalidArgumentException('Unsupported schema alteration.');
            }
        }
        $sql='ALTER TABLE '.self::identifier($name).' '.implode(',',$clauses);
        if (!$clauses) throw new InvalidArgumentException('Schema alterations are required.');
        if (!$check) $this->table->executeExactPrepared($sql);
        return true;
    }

    /** Execute a reviewed installation statement; never accept request-supplied SQL. */
    public function executeMigration(string $sql): int
    {
        $this->requireOutsideTransaction();
        return $this->table->executeExactPrepared($sql);
    }

    private function requireOutsideTransaction(): void
    {
        if ($this->table->hasActiveTransaction() || (int)($this->table->selectExactPrepared('SELECT @@in_transaction AS active')[0]['active']??0)!==0) throw new LogicException('Schema changes cannot run inside a transaction.');
    }
}
