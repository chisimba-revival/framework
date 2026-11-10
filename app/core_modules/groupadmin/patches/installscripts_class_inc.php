<?php
/**
 * Modern group registration is owned by the canonical catalogue/group services.
 *
 * The former LiveUser conversion deleted/recreated tbl_users records and mapped
 * obsolete permission IDs. Never run that historical conversion during an
 * ordinary installation or upgrade: credentials, user IDs and memberships must
 * remain unchanged. Legacy imports require a separately reviewed migration.
 * @author Derek Keats
 */
class groupadmin_installscripts extends ChisimbaObject
{
    public function init() {}

    public function preinstall($version = null)
    {
        return 'Canonical identities preserved';
    }

    public function postinstall($version = null)
    {
        return 'Canonical group registration complete';
    }
}
