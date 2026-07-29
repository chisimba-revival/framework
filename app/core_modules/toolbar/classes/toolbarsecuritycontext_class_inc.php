<?php
/**
 * Canonical authentication and authorization context for toolbar consumers.
 *
 * Toolbar renderers receive only the state they need. They do not read legacy
 * identity, ACL, group-admin, or generic session objects.
 *
 * @category  Chisimba
 * @package   toolbar
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
class toolbarsecuritycontext extends ChisimbaObject
{
    private $sessions;
    private $csrf;
    private $users;
    private $groups;
    private $permissions;

    public function init()
    {
        $stack = $this->getObject(
            'nativeauthwebcomposition',
            'security'
        )->build();
        $this->sessions = $stack['sessions'];
        $this->csrf = $stack['csrf'];
        $this->users = $this->getObject('userservice', 'security');
        $this->groups = $this->getObject('groupservice', 'groupadmin');
        $this->permissions = $this->getObject(
            'permissionservice',
            'security'
        );
    }

    public function isAuthenticated()
    {
        return $this->sessions->isAuthenticated();
    }

    public function userId()
    {
        return $this->sessions->getUserId();
    }

    public function displayName()
    {
        $userId = $this->userId();
        if ($userId === null) {
            return '';
        }
        $user = $this->users->findByUserId($userId);
        if (!is_array($user)) {
            return '';
        }
        $name = trim(
            (isset($user['firstname']) ? $user['firstname'] : '')
            . ' '
            . (isset($user['surname']) ? $user['surname'] : '')
        );
        if ($name !== '') {
            return $name;
        }

        return isset($user['username']) ? trim($user['username']) : '';
    }

    public function isSiteAdministrator()
    {
        $userId = $this->userId();
        if ($userId === null) {
            return false;
        }
        $groupId = $this->groups->groupIdForName('Site Admin');

        return $groupId !== null
            && $this->groups->isGroupMember($userId, $groupId);
    }

    /**
     * Empty right means deliberately public; every named right fails closed.
     */
    public function mayUseRight($rightId)
    {
        if ($rightId === null || $rightId === '') {
            return true;
        }
        $userId = $this->userId();

        return $userId !== null
            && $this->permissions->isGranted($userId, $rightId);
    }

    /**
     * Return the only logout control toolbar renderers may expose.
     */
    public function logoutForm($label, $cssClass = 'toolbar-logout-form')
    {
        if (!$this->isAuthenticated()) {
            return '';
        }
        $token = $this->csrf->issue('native_auth_logout');
        $action = $this->uri(array('action' => 'logout'), 'security');

        return '<form method="post" action="'
            . htmlspecialchars($action, ENT_QUOTES, 'UTF-8')
            . '" class="'
            . htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8')
            . '"><input type="hidden" name="native_auth_logout" value="'
            . htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
            . '" /><button type="submit">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</button></form>';
    }
}
?>
