<?php
/**
 * Canonical authorization boundary for native group administration.
 *
 * @package groupadmin
 * @author Derek Keats
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

require_once dirname(__FILE__)
    . '/../../security/classes/nativeauth/nativesessionservice.php';

class groupadminauthorizationservice extends ChisimbaObject
{
    private $objGroupService;
    private $objSessionService;

    public function init()
    {
        $this->objGroupService = $this->getObject('groupservice', 'groupadmin');
        $this->objSessionService = new NativeSessionService($this);
    }

    /**
     * Return whether the current authenticated identity belongs to Site Admin.
     *
     * Missing authentication, identity, or baseline group data fails closed.
     */
    public function isCurrentUserSiteAdministrator()
    {
        if (!$this->objSessionService->isAuthenticated()) {
            return false;
        }

        $userId = $this->objSessionService->getUserId();
        if (!is_scalar($userId) || trim((string) $userId) === '') {
            return false;
        }

        $groupId = $this->objGroupService->groupIdForName('Site Admin');
        if ($groupId === false || (int) $groupId <= 0) {
            return false;
        }

        return (bool) $this->objGroupService->isGroupMember(
            trim((string) $userId),
            (int) $groupId
        );
    }
}
?>
