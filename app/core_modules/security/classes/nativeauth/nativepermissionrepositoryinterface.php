<?php
/**
 * Permission resolution contract matching existing Chisimba semantics.
 */
interface NativePermissionRepositoryInterface
{
    /** @return array List of effective permission names. */
    public function getEffectivePermissionsForUser($userId);

    /** @return bool */
    public function userHasPermission($userId, $permission, array $context = array());

    /** @return bool */
    public function groupHasPermission($groupId, $permission, array $context = array());
}
