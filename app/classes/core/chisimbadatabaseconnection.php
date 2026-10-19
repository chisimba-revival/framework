<?php
/**
 * Native MySQL persistence boundary. No MDB2 objects or result emulation.
 *
 * Native consumers receive exact associative values; dbTable owns its historical
 * row conversions. Errors intentionally exclude SQL, credentials and parameters.
 * @author Derek Keats
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2 or later
 */
class ChisimbaDatabaseException extends RuntimeException
{
    public function __construct(public readonly string $sqlState = 'HY000')
    {
        parent::__construct('Database operation failed (SQLSTATE ' . $sqlState . ').');
    }
}

final class ChisimbaDatabaseConnection
{
    private PDO $pdo;
    private int $transactionGeneration = 0;
    private ?int $activeTransaction = null;

    /** Inject an existing connection for composition and isolated integration tests. */
    public function __construct(PDO $pdo)
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new InvalidArgumentException('This database boundary requires MySQL PDO.');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo = $pdo;
    }

    /** Translate parsed legacy configuration without exposing authentication values. */
    public static function fromLegacyDsn(#[SensitiveParameter] array $dsn): self
    {
        $nativeDsn = self::mysqlDsn($dsn);
        if (!class_exists(PDO::class) || !in_array('mysql', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('The PHP image requires the pdo_mysql extension.');
        }
        try {
            return new self(new PDO($nativeDsn, $dsn['username'] ?? '', $dsn['password'] ?? '',
                [PDO::ATTR_PERSISTENT => false]));
        } catch (PDOException $error) {
            throw self::failure($error);
        }
    }

    /** Build only the non-secret PDO DSN; supports host:port and Unix sockets. */
    public static function mysqlDsn(#[SensitiveParameter] array $dsn): string
    {
        if (!in_array($dsn['phptype'] ?? '', ['mysql', 'mysqli'], true)) {
            throw new InvalidArgumentException('Unsupported database driver.');
        }
        $database = self::dsnValue($dsn['database'] ?? '');
        if ($database === '') throw new InvalidArgumentException('Database name is required.');
        $charset = $dsn['charset'] ?? 'utf8';
        if (!in_array($charset, ['utf8', 'utf8mb3', 'utf8mb4'], true)) {
            throw new InvalidArgumentException('Unsupported database character set.');
        }
        $host = self::dsnValue($dsn['hostspec'] ?? 'localhost');
        $socket = self::dsnValue($dsn['socket'] ?? $dsn['unix_socket'] ?? '');
        if (str_starts_with($host, 'unix(') && str_ends_with($host, ')')) {
            $socket = substr($host, 5, -1);
        } elseif (str_starts_with($host, '/')) {
            $socket = $host;
        }
        if ($socket !== '') {
            $location = 'unix_socket=' . $socket;
        } else {
            $port = $dsn['port'] ?? null;
            if (preg_match('/^\[([^]]+)\](?::([0-9]+))?$/D', $host, $parts)) {
                $host = $parts[1];
                $port = $port ?? ($parts[2] ?? null);
            } elseif (preg_match('/^([^:]+):([0-9]+)$/D', $host, $parts)) {
                $host = $parts[1];
                $port = $port ?? $parts[2];
            }
            $location = 'host=' . $host;
            if ($port !== null) {
                if (!ctype_digit((string)$port) || (int)$port < 1 || (int)$port > 65535) {
                    throw new InvalidArgumentException('Invalid database port.');
                }
                $location .= ';port=' . (int)$port;
            }
        }
        return 'mysql:' . $location . ';dbname=' . $database . ';charset=' . $charset;
    }

    private static function dsnValue(#[SensitiveParameter] mixed $value): string
    {
        if (!is_string($value) || preg_match('/[;\x00-\x1f]/', $value)) {
            throw new InvalidArgumentException('Invalid database connection setting.');
        }
        return $value;
    }

    /** Only SQLSTATE survives. Do not attach the original PDO exception as a cause. */
    private static function failure(#[SensitiveParameter] PDOException $error): ChisimbaDatabaseException
    {
        $state = (string)($error->errorInfo[0] ?? $error->getCode());
        return new ChisimbaDatabaseException(preg_match('/^[A-Z0-9]{5}$/D', $state) ? $state : 'HY000');
    }

    /** Bind values, never pre-quoted fragments. Named parameters must be unique. */
    private function statement(#[SensitiveParameter] string $sql, #[SensitiveParameter] array $parameters): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $positional = array_is_list($parameters);
        foreach ($parameters as $key => $value) {
            if (!$positional && (!is_string($key) || !preg_match('/^:?[A-Za-z_][A-Za-z0-9_]*$/D', $key))) {
                throw new InvalidArgumentException('Invalid database parameter keys.');
            }
            $type = match (true) {
                $value === null => PDO::PARAM_NULL,
                is_bool($value) => PDO::PARAM_BOOL,
                is_int($value) => PDO::PARAM_INT,
                is_string($value), is_float($value) && is_finite($value) => PDO::PARAM_STR,
                default => throw new InvalidArgumentException('Unsupported database parameter value.'),
            };
            $statement->bindValue($positional ? $key + 1 : ':' . ltrim($key, ':'), $value, $type);
        }
        $statement->execute();
        return $statement;
    }

    /** Return rows for result statements and an affected-row count for writes. */
    public function query(#[SensitiveParameter] string $sql, #[SensitiveParameter] array $parameters = []): array|int
    {
        $statement = null;
        try {
            $statement = $this->statement($sql, $parameters);
            return $statement->columnCount() > 0 ? $statement->fetchAll(PDO::FETCH_ASSOC) : $statement->rowCount();
        } catch (PDOException $error) {
            throw self::failure($error);
        } finally {
            if ($statement !== null) $statement->closeCursor();
        }
    }

    /** Fetch associative rows without legacy trimming or type conversion. */
    public function fetchAll(#[SensitiveParameter] string $sql, #[SensitiveParameter] array $parameters = []): array
    {
        $result = $this->query($sql, $parameters);
        if (!is_array($result)) throw new LogicException('A row-producing statement is required.');
        return $result;
    }

    /** Fetch one associative row, or null when no row exists; close the cursor. */
    public function fetchOne(#[SensitiveParameter] string $sql, #[SensitiveParameter] array $parameters = []): ?array
    {
        $statement = null;
        try {
            $statement = $this->statement($sql, $parameters);
            if ($statement->columnCount() === 0) throw new LogicException('A row-producing statement is required.');
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return $row === false ? null : $row;
        } catch (PDOException $error) {
            throw self::failure($error);
        } finally {
            if ($statement !== null) $statement->closeCursor();
        }
    }

    /** Execute a write and retain the difference between zero affected and failure. */
    public function execute(#[SensitiveParameter] string $sql, #[SensitiveParameter] array $parameters = []): int
    {
        $result = $this->query($sql, $parameters);
        if (!is_int($result)) throw new LogicException('A write statement is required.');
        return $result;
    }

    /** Identifiers are trusted schema names, never expressions or value parameters. */
    public function quoteIdentifier(string $identifier): string
    {
        $parts = explode('.', $identifier);
        foreach ($parts as &$part) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_$]*$/D', $part)) {
                throw new InvalidArgumentException('Invalid database identifier.');
            }
            $part = '`' . $part . '`';
        }
        return implode('.', $parts);
    }

    /** Transitional value quoting for callers not yet using prepared parameters. */
    public function quoteValue(#[SensitiveParameter] mixed $value): string
    {
        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value)) return (string)$value;
        if (!is_string($value) && !(is_float($value) && is_finite($value))) {
            throw new InvalidArgumentException('Unsupported database value.');
        }
        try {
            $quoted = $this->pdo->quote((string)$value, PDO::PARAM_STR);
            if ($quoted === false) throw new ChisimbaDatabaseException();
            return $quoted;
        } catch (PDOException $error) {
            throw self::failure($error);
        }
    }

    public function inTransaction(): bool
    {
        try { return $this->pdo->inTransaction(); }
        catch (PDOException $error) { throw self::failure($error); }
    }

    /** Begin an owned transaction; return its generation token. Nesting is rejected. */
    public function beginTransaction(): int
    {
        if ($this->inTransaction()) throw new LogicException('A transaction is already active.');
        try {
            if (!$this->pdo->beginTransaction()) throw new ChisimbaDatabaseException();
            return $this->activeTransaction = ++$this->transactionGeneration;
        } catch (PDOException $error) { throw self::failure($error); }
    }

    /** Commit; an optional ownership token rejects stale or foreign owners. */
    public function commit(?int $owner = null): void
    {
        if (!$this->inTransaction()) throw new LogicException('No transaction is active.');
        if ($owner !== null && $owner !== $this->activeTransaction) throw new LogicException('Transaction ownership has changed.');
        try {
            if (!$this->pdo->commit()) throw new ChisimbaDatabaseException();
            $this->activeTransaction = null;
        } catch (PDOException $error) { throw self::failure($error); }
    }

    /** Roll back; an optional ownership token rejects stale or foreign owners. */
    public function rollback(?int $owner = null): void
    {
        if (!$this->inTransaction()) throw new LogicException('No transaction is active.');
        if ($owner !== null && $owner !== $this->activeTransaction) throw new LogicException('Transaction ownership has changed.');
        try {
            if (!$this->pdo->rollBack()) throw new ChisimbaDatabaseException();
            $this->activeTransaction = null;
        } catch (PDOException $error) { throw self::failure($error); }
    }
}
