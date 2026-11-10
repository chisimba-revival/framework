<?php
/** Guarded, repeatable API schema upgrade through the shared schema service.
 * @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class restschema extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        parent::init($tableName ?? 'tbl_rest_identity_epoch', $pearDb, $errorCallback);
    }

    public function upgrade(): void
    {
        require_once dirname(__DIR__, 3) . '/classes/core/chisimbadatabaseschema.php';
        $schema = new ChisimbaDatabaseSchema($this);
        foreach (['tbl_rest_refresh_tokens', 'tbl_rest_api_keys', 'tbl_rest_identity_epoch'] as $table) {
            if (!$schema->tableExists($table)) {
                $definition = static function ($file) {
                    include $file;
                    return [$tablename, $fields, $options, $tableIndexes];
                };
                [$name, $fields, $options, $indexes] = $definition(dirname(__DIR__) . '/sql/' . $table . '.sql');
                $schema->createTable($name, $fields, $options);
                foreach ($indexes as $index => $spec) {
                    if (!empty($spec['primary'])) continue; // puid is the framework primary key.
                    $schema->createIndex($name, $index, $spec, !empty($spec['unique']));
                }
            }
            $schema->requireTransactional($table);
            $type = $this->selectPrepared('SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$table, 'user_id'])[0]['data_type'] ?? null;
            if ($type === null) throw new RuntimeException('REST API user_id column is missing.');
            if (in_array($type, ['tinyint','smallint','mediumint','int','bigint','decimal','float','double'], true)) {
                $schema->alterTable($table, ['change' => ['user_id' => ['type'=>'text','length'=>25,'notnull'=>true]]]);
            }
        }
        // The catalogue's legacy index path does not enforce unique declarations.
        // Epoch upserts require exactly one row per user. Refuse duplicate data;
        // never guess which user's revocation state should be retained.
        $unique = false;
        $indexes = [];
        foreach ($schema->indexes('tbl_rest_identity_epoch') as $row) {
            $indexes[$row['Key_name']][] = $row;
        }
        foreach ($indexes as $rows) {
            if (count($rows) === 1 && (int)$rows[0]['Non_unique'] === 0 && $rows[0]['Column_name'] === 'user_id') $unique = true;
        }
        if (!$unique) $schema->createIndex('tbl_rest_identity_epoch', 'rest_epoch_user_unique', ['unique'=>true,'fields'=>['user_id'=>[]]], true);
        $this->executePrepared('UPDATE tbl_rest_api_keys SET id = puid WHERE id IS NULL');
    }
}
