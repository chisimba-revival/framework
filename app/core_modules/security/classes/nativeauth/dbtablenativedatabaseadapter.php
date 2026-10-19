<?php
require_once __DIR__ . '/nativedatabaseadapterinterface.php';

/** Authentication persistence through the canonical dbTable boundary.
 * @author Derek Keats
 */
final class DbTableNativeDatabaseAdapter implements NativeDatabaseAdapterInterface
{
    private dbTable $table;

    public function __construct(dbTable $table) { $this->table = $table; }

    public function fetchOne(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters = [])
    {
        return $this->fetchAll($sql, $parameters)[0] ?? null;
    }

    public function fetchAll(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters = [])
    {
        return $this->table->selectPrepared($sql, $parameters);
    }

    public function execute(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters = [])
    {
        return $this->table->executePrepared($sql, $parameters);
    }
}
