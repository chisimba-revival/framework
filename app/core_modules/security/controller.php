<?php
/**
 * Native authentication web controller.
 *
 * This controller is deliberately limited to the canonical guarded login,
 * MFA, authenticated landing, and logout web boundaries.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class security extends controller
{
    const LOGIN_CSRF_CONTEXT = 'native_auth_begin';
    const LOGOUT_CSRF_CONTEXT = 'native_auth_logout';

    public $objLanguage;
    public $objConfig;

    public function init()
    {
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objConfig = $this->getObject('altconfig', 'config');
    }

    public function requiresLogin($action)
    {
        return in_array($action, array('authenticated', 'logout'), true);
    }

    public function dispatch($action)
    {
        $this->setLayoutTemplate(null);
        $this->setVar('pageSuppressToolbar', true);

        switch ($action) {
            case 'login':
                return $this->nativeLogin();
            case 'mfa_enrol_start':
            case 'mfa_enrol_confirm':
            case 'mfa_totp':
            case 'mfa_recovery':
            case 'mfa_cancel':
                return $this->nativeMfa($action);
            case 'authenticated':
                return $this->nativeLanding();
            case 'logout':
                return $this->nativeLogout();
            case 'needpassword':
                return $this->nextAction(
                    'forgotpassword',
                    array(),
                    'registration-service'
                );
            case 'showlogin':
            default:
                return $this->nativeLoginPage();
        }
    }

    private function nativeAuthStack()
    {
        return $this->getObject(
            'nativeauthwebcomposition',
            'security'
        )->build();
    }

    private function nativeLoginPage($messageKey = null)
    {
        $stack = $this->nativeAuthStack();
        if ($stack['sessions']->isAuthenticated()) {
            return $this->nativeLanding();
        }

        $this->setVar(
            'nativeAuthBeginToken',
            $stack['csrf']->issue(self::LOGIN_CSRF_CONTEXT)
        );
        $this->setVar(
            'nativeAbuseEvidence',
            $stack['abuse']->issueFormEvidence('native.login')
        );
        $this->setVar('nativeLoginLabels', array(
            'title' => $this->text('mod_security_nativelogintitle'),
            'username' => $this->text('word_username', 'system'),
            'password' => $this->text('word_password', 'system'),
            'remember' => $this->text('mod_security_rememberme'),
            'submit' => $this->text('word_login', 'system'),
            'failure' => $messageKey === null
                ? ''
                : $this->text($messageKey),
        ));

        return 'native_login_tpl.php';
    }

    private function nativeLogin()
    {
        if (!$this->isPost()) {
            return $this->nativeLoginPage('mod_security_invalidrequest');
        }

        $this->unsetSession('native_auth_login_failure');
        $stack = $this->nativeAuthStack();
        $returnTo = $this->validatedReturnTo(
            $this->getParam('return_to', '')
        );
        if ($returnTo !== null) {
            $this->setSession('native_auth_return_to', $returnTo);
        } else {
            $this->unsetSession('native_auth_return_to');
        }
        $result = $stack['guarded_login']->begin(
            $this->getParam('native_auth_begin', ''),
            $this->getParam('username', ''),
            $this->getParam('password', ''),
            $this->getParam('remember', '') === 'on',
            array(
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'session' => session_id(),
            ),
            array(
                'issued_at' => $this->getParam('abuse_issued_at', ''),
                'nonce' => $this->getParam('abuse_nonce', ''),
                'signature' => $this->getParam('abuse_signature', ''),
                'website' => $this->getParam('website', ''),
            )
        );
        $status = isset($result['status'])
            ? $result['status']
            : 'invalid_request';

        if ($status === 'complete') {
            return $this->returnAfterAuthentication();
        }
        if ($status === 'mfa_required') {
            return $this->nativeMfaPage($stack['adapter']->challengePage());
        }
        if ($status === 'mfa_enrolment_required') {
            return $this->nativeMfaPage($stack['adapter']->enrolmentPage(
                $result['user_id'],
                $this->objConfig->getSiteName(),
                $result['username']
            ));
        }

        $username = $this->getParam('username', '');
        if (is_array($username) || is_object($username)) {
            $username = '';
        }
        $this->setSession('native_auth_login_failure', array(
            'message_key' => $status === 'pending_verification'
                ? 'mod_security_pendingverification'
                : 'mod_security_authenticationfailed',
            'username' => substr(trim((string) $username), 0, 255),
            'return_to' => $returnTo === null ? '' : $returnTo,
        ));

        header('Location: ' . $this->failedLoginPath(), true, 303);
        exit;
    }

    private function nativeMfa($action)
    {
        $stack = $this->nativeAuthStack();
        $adapter = $stack['adapter'];
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($action === 'mfa_enrol_start') {
            $result = $adapter->startEnrolment(
                $method,
                $this->getParam('csrf_token', ''),
                $this->getParam('user_id', ''),
                $this->objConfig->getSiteName(),
                $this->getParam('account_label', '')
            );
        } elseif ($action === 'mfa_enrol_confirm') {
            $result = $adapter->confirmEnrolment(
                $method,
                $this->getParam('csrf_token', ''),
                $this->getParam('user_id', ''),
                $this->getParam('enrolment_id', ''),
                $this->getParam('code', '')
            );
        } elseif ($action === 'mfa_totp') {
            $result = $adapter->completeTotp(
                $method,
                $this->getParam('csrf_token', ''),
                $this->getParam('transaction_id', ''),
                $this->getParam('code', '')
            );
        } elseif ($action === 'mfa_recovery') {
            $result = $adapter->completeRecovery(
                $method,
                $this->getParam('csrf_token', ''),
                $this->getParam('transaction_id', ''),
                $this->getParam('recovery_code', '')
            );
        } else {
            $result = $adapter->cancel(
                $method,
                $this->getParam('csrf_token', '')
            );
        }

        if (($result['status'] ?? '') === 'complete') {
            return $this->returnAfterAuthentication();
        }
        if (($result['status'] ?? '') === 'cancelled') {
            $this->unsetSession('native_auth_return_to');
            return $this->nativeLoginPage(
                'mod_security_mfacancelled'
            );
        }

        return $this->nativeMfaPage($result);
    }

    private function nativeMfaPage(array $result)
    {
        $this->setVar('mfa', $result);
        $this->setVar('mfaLabels', array(
            'title' => $this->text('mod_security_mfachallenge'),
            'scan' => $this->text('mod_security_mfaenrolment'),
            'qr_alt' => $this->text('mod_security_mfaqralt'),
            'manual_key' => $this->text('mod_security_mfamanualkey'),
            'code' => $this->text('mod_security_mfacode'),
            'confirm' => $this->text('word_confirm', 'system'),
            'verify' => $this->text('word_verify', 'system'),
            'invalid_code' => $this->text('mod_security_mfainvalidcode'),
            'recovery_code' => $this->text('mod_security_mfarecoverycode'),
            'use_recovery' => $this->text('mod_security_mfauserecovery'),
            'cancel' => $this->text('word_cancel', 'system'),
            'save_codes' => $this->text('mod_security_mfasavecodes'),
            'expired' => $this->text('mod_security_mfaexpired'),
        ));

        return 'mfa_web_tpl.php';
    }

    /**
     * Complete an authentication transaction at its validated local origin.
     */
    private function returnAfterAuthentication()
    {
        $returnTo = $this->getSession('native_auth_return_to');
        $this->unsetSession('native_auth_return_to');
        $returnTo = $this->validatedReturnTo($returnTo);
        if ($returnTo === null) {
            return $this->nativeLanding();
        }

        header('Location: ' . $returnTo, true, 303);
        exit;
    }

    /**
     * Accept only a local path within this Chisimba installation.
     */
    private function validatedReturnTo($candidate)
    {
        if (is_array($candidate) || is_object($candidate)) {
            return null;
        }
        $candidate = trim((string) $candidate);
        if ($candidate === '' || strlen($candidate) > 2048
            || preg_match('/[\x00-\x1F\x7F\\]/', $candidate)
            || strncmp($candidate, '//', 2) === 0) {
            return null;
        }
        $parts = parse_url($candidate);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['port']) || isset($parts['fragment'])) {
            return null;
        }
        $path = isset($parts['path']) ? $parts['path'] : '';
        $base = rtrim(str_replace('\\', '/', dirname(
            isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'
        )), '/');
        if ($base === '') {
            $base = '/';
        }
        $prefix = $base === '/' ? '/' : $base . '/';
        if ($path !== $base && strncmp($path, $prefix, strlen($prefix)) !== 0) {
            return null;
        }
        parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
        if (($query['module'] ?? '') === 'security'
            && in_array(($query['action'] ?? ''), array(
                'login', 'showlogin', 'authenticated', 'logout',
                'mfa_enrol_start', 'mfa_enrol_confirm', 'mfa_totp',
                'mfa_recovery', 'mfa_cancel',
            ), true)) {
            return null;
        }
        return $candidate;
    }

    private function nativeLanding()
    {
        $stack = $this->nativeAuthStack();
        $this->setVar('pageSuppressToolbar', null);
        if (!$stack['sessions']->isAuthenticated()) {
            return $this->nativeLoginPage();
        }

        $this->setVar(
            'nativeLogoutToken',
            $stack['csrf']->issueForSession(self::LOGOUT_CSRF_CONTEXT)
        );
        $this->setVar('nativeLandingLabels', array(
            'title' => $this->text('mod_security_nativeauthenticatedtitle'),
            'message' => $this->text(
                'mod_security_nativeauthenticatedmessage'
            ),
            'logout' => $this->text('word_logout', 'system'),
        ));

        return 'native_authenticated_tpl.php';
    }

    private function nativeLogout()
    {
        if (!$this->isPost()) {
            return $this->nativeFailure('mod_security_invalidrequest');
        }

        $stack = $this->nativeAuthStack();
        if (!$stack['csrf']->consume(
            self::LOGOUT_CSRF_CONTEXT,
            $this->getParam('native_auth_logout', '')
        )) {
            return $this->nativeFailure('mod_security_invalidrequest');
        }

        $userId = $stack['sessions']->getUserId();
        if ($userId !== null) {
            $stack['persistent']->revokeAllForUser($userId, time());
        } else {
            $stack['persistent']->clear();
        }
        $this->clearContextScope();
        if (!$stack['sessions']->destroy()) {
            throw new RuntimeException(
                'Canonical authenticated session could not be destroyed.'
            );
        }
        if (!$this->destroyApplicationSession()) {
            throw new RuntimeException(
                'Application session could not be destroyed.'
            );
        }

        header('Location: ' . $this->frontPagePath(), true, 303);
        exit;
    }

    /**
     * Destroy every module-scoped value and invalidate the browser cookie.
     *
     * Authentication keys alone are not a sufficient logout boundary because
     * Chisimba stores course, workflow and module state in the same PHP session.
     * A shared computer must never expose any of that state to the next login.
     *
     * @return bool True when no active PHP session remains.
     * @author Derek Keats
     */
    private function destroyApplicationSession()
    {
        $_SESSION = array();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        return session_destroy();
    }

    /**
     * Prevent the next authenticated identity inheriting the previous course.
     *
     * Context owns additional optional scope such as workgroups, so its
     * service performs the normal cleanup. The direct fallback keeps the
     * security boundary reliable if optional context cleanup cannot load.
     *
     * @return void
     */
    private function clearContextScope()
    {
        try {
            $this->getObject('dbcontext', 'context')->leaveContext();
            return;
        } catch (Throwable $failure) {
            foreach (array(
                'contextCode', 'contextId', 'contextTitle', 'contextmenuText',
                'contextabout', 'contextIsActive', 'contextIsClosed',
                'contextDateCreated', 'contextCreatorId',
            ) as $key) {
                $this->unsetSession($key, 'context');
            }
        }
    }

    /**
     * Return the local installation front page without consulting site URLs.
     */
    private function frontPagePath()
    {
        $scriptPath = str_replace(
            '\\',
            '/',
            isset($_SERVER['SCRIPT_NAME'])
                ? $_SERVER['SCRIPT_NAME']
                : '/index.php'
        );
        $basePath = rtrim(dirname($scriptPath), '/');

        return ($basePath === '' || $basePath === '.')
            ? '/'
            : $basePath . '/';
    }

    /**
     * Keep failed authentication on the public Security boundary. This is
     * essential while the ordinary front page is hidden by maintenance mode.
     */
    private function securityLoginPath()
    {
        $front = $this->frontPagePath();
        return $front . 'index.php?module=security';
    }

    /**
     * Return ordinary failures to the branded front page. During maintenance
     * that page is intentionally unavailable, so retain the public Security
     * boundary and its usable login form instead.
     */
    private function failedLoginPath()
    {
        $modules = $this->getObject('modules', 'modulecatalogue');
        if ($modules->checkIfRegistered('systemmanagement')) {
            $maintenance = $this->getObject(
                'systemmanagementservice',
                'systemmanagement'
            )->maintenance();
            if (!empty($maintenance['active'])) {
                return $this->securityLoginPath();
            }
        }
        return $this->frontPagePath();
    }

    private function nativeFailure($key)
    {
        return $this->nativeLoginPage($key);
    }

    private function isPost()
    {
        return strtoupper(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')
        ) === 'POST';
    }

    private function text($key, $module = 'security')
    {
        return $this->objLanguage->languageText($key, $module);
    }
}
