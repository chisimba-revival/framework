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
    private $logoutRecoveryLoaded = false;
    private $user;
    private $permissions;
    private $context;
    private $userContext;

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
        $this->userContext = $this->getObject('usercontext', 'context');
    }

    public function isAuthenticated()
    {
        return $this->user->isLoggedIn();
    }

    public function userId()
    {
        return $this->user->userId();
    }

    public function userName()
    {
        return $this->isAuthenticated() ? $this->user->userName() : '';
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

    /** Whether the account lectures in at least one course. */
    public function isLecturer()
    {
        return $this->isAuthenticated() && $this->user->isLecturer();
    }

    /** Whether the account has a learner journey in at least one course. */
    public function hasStudentLearning()
    {
        $userId = $this->userId();
        return $userId !== null
            && count((array) $this->userContext->getContextWhereStudent($userId)) > 0;
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
        $recoveryScript = '';
        if (!$this->logoutRecoveryLoaded) {
            // Toolbars may render after the page head has already been emitted.
            $recoveryScript = '<script defer src="'.htmlspecialchars($this->getResourceUri('logout-recovery.js', 'security'), ENT_QUOTES, 'UTF-8').'?v=1"></script>';
            $this->logoutRecoveryLoaded = true;
        }
        $token = $this->csrf->issueForSession('native_auth_logout');
        $action = html_entity_decode(
            $this->uri(array('action' => 'logout'), 'security'),
            ENT_QUOTES,
            'UTF-8'
        );

        return '<form method="post" action="'
            . htmlspecialchars($action, ENT_QUOTES, 'UTF-8')
            . '" class="'
            . htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8')
            . '" data-logout-recovery data-token-url="'
            . htmlspecialchars(html_entity_decode($this->uri(array('action'=>'formtoken'), 'security'), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8')
            . '" data-recovery-error="'
            . htmlspecialchars($this->getObject('language','language')->languageText('mod_security_logout_retry','security'), ENT_QUOTES, 'UTF-8')
            . '"><input type="hidden" name="native_auth_actor" value="'
            . htmlspecialchars((string)$this->user->userId(), ENT_QUOTES, 'UTF-8')
            . '" /><input type="hidden" name="native_auth_logout" value="'
            . htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
            . '" /><button type="submit">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</button></form>' . $recoveryScript;
    }
}
?>
