<?php

/**
 * Repository contract for direct and derived Chisimba permissions.
 *
 * Permission precedence is deliberately not specified here. It must first
 * be measured against the PHP 7.4 behavioural baseline.
 */
interface PermissionRepositoryInterface
{
    /**
     * Return direct permission rows for a permission user.
     *
     * @param mixed $permissionUserId
     * @return array
     */
    public function findDirectPermissions($permissionUserId);

    /**
     * Determine whether a user has a named permission.
     *
     * The exact mapping between permission names, perm_type values, groups
     * and areas must be implemented only after baseline tests exist.
     *
     * @param mixed $permissionUserId
     * @param mixed $permission
     * @param mixed|null $context
     * @return bool
     */
    public function hasPermission(
        $permissionUserId,
        $permission,
        $context = null
    );
}
