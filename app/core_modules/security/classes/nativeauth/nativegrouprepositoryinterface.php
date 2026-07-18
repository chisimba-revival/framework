<?php
/**
 * Group membership lookup contract for the native authorisation layer.
 */
interface NativeGroupRepositoryInterface
{
    /** @return array List of canonical group identifiers. */
    public function getGroupIdsForUser($userId);

    /** @return bool */
    public function isUserInGroup($userId, $groupId);

    /** @return array|null */
    public function findGroupById($groupId);
}
