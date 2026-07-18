<?php
/**
 * Minimal parameterised database boundary for native authentication.
 *
 * Implementations must bind every value parameter. SQL identifiers remain
 * internal constants in repository classes and are never accepted from input.
 */
interface NativeDatabaseAdapterInterface
{
    /** @return array|null */
    public function fetchOne($sql, array $parameters = array());

    /** @return array */
    public function fetchAll($sql, array $parameters = array());

    /** @return int Number of affected rows. */
    public function execute($sql, array $parameters = array());
}
