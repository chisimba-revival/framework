<?php

/**
 * Repository contract for Chisimba groups and memberships.
 */
interface GroupRepositoryInterface
{
    /**
     * Return groups associated with a permission-user identifier.
     *
     * @param mixed $permissionUserId
     * @return array
     */
    public function findGroupsForPermissionUser($permissionUserId);

    /**
     * Determine whether a permission user belongs to a group.
     *
     * @param mixed $permissionUserId
     * @param mixed $groupId
     * @return bool
     */
    public function isMember($permissionUserId, $groupId);

    /**
     * Return a group definition.
     *
     * @param mixed $groupId
     * @return array|null
     */
    public function findGroup($groupId);
}
