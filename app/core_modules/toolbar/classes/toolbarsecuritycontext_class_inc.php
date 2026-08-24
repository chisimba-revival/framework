<?php
/**
 * Security context exposed through established toolbar-facing methods.
 *
 * Identity, login, display-name, and group decisions delegate to the existing
 * security user API. Those established user methods own canonical-service
 * integration; toolbar consumers do not construct identity or group services.
 *
 * @category  Chisimba
 * @package   toolbar
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
class toolbarsecuritycontext extends ChisimbaObject
{
    private $csrf;
    private $user;
    private $permissions;
    private $context;

    public function init()
    {
        $stack = $this->getObject(
            'nativeauthwebcomposition',
            'security'
        )->build();
        $this->csrf = $stack['csrf'];
        $this->user = $this->getObject('user', 'security');
        $this->permissions = $this->getObject(
            'permissionservice',
            'security'
        );
        $this->context = $this->getObject('dbcontext', 'context');
    }

    public function isAuthenticated()
    {
        return $this->user->isLoggedIn();
    }

    public function userId()
    {
        return $this->user->userId();
    }

    public function displayName()
    {
        if (!$this->isAuthenticated()) {
            return '';
        }

        return $this->user->fullname();
    }

    public function isSiteAdministrator()
    {
        $userId = $this->userId();

        return $userId !== null
            && $this->user->inAdminGroup($userId, 'Site Admin');
    }

    /**
     * Whether the current user may manage learning activities in this course.
     *
     * This is intentionally a current-context decision. A lecturer in one
     * course must not inherit lecturer navigation while visiting another.
     */
    public function isCurrentContextLecturer()
    {
        $userId = $this->userId();
        $contextCode = $this->context->getContextCode();

        return $userId !== null
            && $contextCode !== null
            && $contextCode !== ''
            && $this->user->isContextLecturer($userId, $contextCode);
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
        $action = html_entity_decode(
            $this->uri(array('action' => 'logout'), 'security'),
            ENT_QUOTES,
            'UTF-8'
        );

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
