<?php
/**
 * Native semantic user administration controller.
 *
 * Provides one server-rendered administration interface and delegates user
 * record creation and mutation to the canonical security services.
 *
 * @category  Chisimba
 * @package   useradmin
 * @author    Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class useradmin extends controller
{
    public $objLanguage;
    public $objUser;
    public $objUserService;
    public $objUserProvisioning;
    public $objBatchUserRegistration;

    public function init()
    {
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objUser = $this->getObject('user', 'security');
        $this->objUserService = $this->getObject('userservice', 'security');
        $this->objUserProvisioning = $this->getObject(
            'userprovisioningservice',
            'security'
        );
        $this->objBatchUserRegistration = $this->getObject(
            'batchuserregistrationservice',
            'useradmin'
        );
    }

    public function dispatch($action)
    {
        $this->assertAdministrator();

        switch (strtolower((string) $action)) {
            case 'create':
                return $this->createUser();
            case 'update':
                return $this->updateUser();
            case 'setstatus':
                return $this->setStatus();
            case 'batchimport':
                return $this->batchImport();
            case 'batchpreview':
                return $this->batchPreview();
            case 'batchconfirm':
                return $this->batchConfirm();
            case 'batchcancel':
                return $this->batchCancel();
            default:
                return $this->nativeInterface();
        }
    }

    private function nativeInterface()
    {
        $query = trim((string) $this->getParam('q', ''));
        $page = max(1, (int) $this->getParam('page', 1));
        $limit = (int) $this->getParam('limit', 25);
        if (!in_array($limit, array(10, 25, 50, 100), true)) {
            $limit = 25;
        }

        $records = $this->objUserService->listUsers($query, true);
        if (!is_array($records)) {
            $records = array();
        }
        $total = count($records);
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $records = array_slice($records, ($page - 1) * $limit, $limit);

        $selected = null;
        $selectedId = trim((string) $this->getParam('userid', ''));
        if ($selectedId !== '') {
            $selected = $this->objUserService->findByUserId($selectedId);
        }

        $this->setVar('userAdminRecords', $records);
        $this->setVar('userAdminSelected', $selected);
        $this->setVar('userAdminQuery', $query);
        $this->setVar('userAdminPage', $page);
        $this->setVar('userAdminPages', $pages);
        $this->setVar('userAdminLimit', $limit);
        $this->setVar('userAdminTotal', $total);
        $this->setVar('userAdminTitles', $this->titlePolicy());
        $countryPolicy = $this->getObject('countrypolicy', 'useradmin');
        $this->setVar(
            'userAdminCountries',
            $countryPolicy->getCountries()
        );
        $this->setVar(
            'userAdminDefaultCountry',
            $countryPolicy->getDefaultCountry()
        );
        $this->setVar('userAdminCsrfToken', $this->csrfToken());
        $this->setVar(
            'userAdminMessage',
            (string) $this->getParam('message', '')
        );
        $this->setVar(
            'userAdminError',
            (string) $this->getParam('error', '')
        );

        return 'native_admin_tpl.php';
    }

    private function createUser()
    {
        $this->assertMutationRequest();

        $password = (string) $this->getParam('password', '');
        if ($password !== (string) $this->getParam('repeat_password', '')) {
            return $this->redirectWithResult(false, 'passwords_do_not_match');
        }

        $userId = $this->objUserService->generateUserId();
        if ($userId === null) {
            return $this->redirectWithResult(false, 'userid_allocation_failed');
        }

        $result = $this->objUserProvisioning->createLocalUser(
            $this->userInput($userId, true),
            $password
        );

        return $this->redirectWithResult(
            !empty($result['ok']),
            isset($result['code']) ? $result['code'] : 'user_create_failed'
        );
    }

    private function updateUser()
    {
        $this->assertMutationRequest();

        $userId = trim((string) $this->getParam('userid', ''));
        $result = $this->objUserService->updateUser(
            $userId,
            $this->userInput($userId, false)
        );

        return $this->redirectWithResult(
            !empty($result['ok']),
            isset($result['code']) ? $result['code'] : 'user_update_failed',
            $userId
        );
    }

    private function setStatus()
    {
        $this->assertMutationRequest();

        $userId = trim((string) $this->getParam('userid', ''));
        $active = (string) $this->getParam('active', '0') === '1';
        $result = $this->objUserService->setActive($userId, $active);

        return $this->redirectWithResult(
            !empty($result['ok']),
            isset($result['code']) ? $result['code'] : 'status_update_failed',
            $userId
        );
    }

    private function batchImport()
    {
        $preview = $this->getSession('useradmin_batch_preview', array());
        $result = $this->getSession('useradmin_batch_result', array());
        $this->setSession('useradmin_batch_result', array());

        $this->setVar(
            'batchUserPreview',
            is_array($preview) ? $preview : array()
        );
        $this->setVar(
            'batchUserResult',
            is_array($result) ? $result : array()
        );
        $this->setVar('batchUserCsrfToken', $this->csrfToken());
        $this->setVar(
            'batchUserError',
            (string) $this->getParam('error', '')
        );
        return 'batch_import_tpl.php';
    }

    private function batchPreview()
    {
        $this->assertMutationRequest();
        $this->setSession('useradmin_batch_preview', array());
        $this->setSession('useradmin_batch_result', array());

        if (!isset($_FILES['userfile'])
            || !is_array($_FILES['userfile'])
            || !isset($_FILES['userfile']['error'])
            || (int) $_FILES['userfile']['error'] !== UPLOAD_ERR_OK
            || empty($_FILES['userfile']['tmp_name'])) {
            return $this->nextAction(
                'batchimport',
                array('error' => 'upload_failed'),
                'useradmin'
            );
        }

        $preview = $this->objBatchUserRegistration->previewCsv(
            (string) $_FILES['userfile']['tmp_name'],
            isset($_FILES['userfile']['name'])
                ? (string) $_FILES['userfile']['name'] : ''
        );
        if (empty($preview['ok'])) {
            return $this->nextAction(
                'batchimport',
                array(
                    'error' => isset($preview['code'])
                        ? $preview['code'] : 'preview_failed'
                ),
                'useradmin'
            );
        }

        $preview['createdAt'] = time();
        $this->setSession('useradmin_batch_preview', $preview);
        return $this->nextAction('batchimport', array(), 'useradmin');
    }

    private function batchConfirm()
    {
        $this->assertMutationRequest();
        $preview = $this->getSession('useradmin_batch_preview', array());
        if (!is_array($preview)
            || empty($preview['batchId'])
            || empty($preview['createdAt'])
            || time() - (int) $preview['createdAt'] > 600) {
            $this->setSession('useradmin_batch_preview', array());
            return $this->nextAction(
                'batchimport',
                array('error' => 'preview_expired'),
                'useradmin'
            );
        }

        $result = $this->objBatchUserRegistration->ingest($preview);
        $this->setSession('useradmin_batch_preview', array());
        $this->setSession('useradmin_batch_result', $result);
        return $this->nextAction('batchimport', array(), 'useradmin');
    }

    private function batchCancel()
    {
        $this->assertMutationRequest();
        $this->setSession('useradmin_batch_preview', array());
        $this->setSession('useradmin_batch_result', array());
        return $this->nextAction('batchimport', array(), 'useradmin');
    }

    private function userInput($userId, $creating)
    {
        $title = trim((string) $this->getParam('title', ''));
        if (!in_array($title, $this->titlePolicy(), true)) {
            $title = '';
        }

        $countryPolicy = $this->getObject(
            'countrypolicy',
            'useradmin'
        );
        $country = $countryPolicy->normalise(
            $this->getParam('country', ''),
            $creating
        );

        $input = array(
            'userId' => $userId,
            'username' => trim((string) $this->getParam('username', '')),
            'title' => $title,
            'firstName' => trim((string) $this->getParam('firstname', '')),
            'surname' => trim((string) $this->getParam('surname', '')),
            'emailAddress' => trim((string) $this->getParam('emailaddress', '')),
            'sex' => trim((string) $this->getParam('sex', '')),
            'country' => $country,
            'cellnumber' => trim((string) $this->getParam('cellnumber', '')),
            'staffnumber' => trim((string) $this->getParam('staffnumber', '')),
            'isActive' => (string) $this->getParam('isactive', '0') === '1',
        );
        if ($creating) {
            $input['howCreated'] = 'useradmin';
        }
        return $input;
    }

    /**
     * Return the locally configured honorific policy.
     *
     * Rendering remains in the semantic template; this method owns validation.
     *
     * @return array
     */
    private function titlePolicy()
    {
        $policyFile = dirname(__FILE__) . '/resources/config/title-policy.php';
        $titles = is_file($policyFile) ? require $policyFile : array('');
        if (!is_array($titles)) {
            return array('');
        }

        $clean = array();
        foreach ($titles as $title) {
            $title = trim((string) $title);
            if (!in_array($title, $clean, true)) {
                $clean[] = $title;
            }
        }
        if (!in_array('', $clean, true)) {
            array_unshift($clean, '');
        }
        return $clean;
    }

    private function redirectWithResult($ok, $code, $userId = '')
    {
        $params = array(
            $ok ? 'message' : 'error' => (string) $code,
            'q' => (string) $this->getParam('q', ''),
            'page' => max(1, (int) $this->getParam('page', 1)),
            'limit' => (int) $this->getParam('limit', 25),
        );
        if ($userId !== '') {
            $params['userid'] = $userId;
        }
        return $this->nextAction('native', $params, 'useradmin');
    }

    private function assertMutationRequest()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])
            || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'POST') {
            throw new customException('This action requires an HTTP POST request.');
        }

        $expected = $this->getSession('useradmin_csrf', '');
        $provided = (string) $this->getParam('csrf_token', '');
        if (!is_string($expected)
            || $expected === ''
            || $provided === ''
            || !hash_equals($expected, $provided)) {
            throw new customException(
                'The security token is missing or invalid. Reload the page and try again.'
            );
        }
    }

    private function csrfToken()
    {
        $token = $this->getSession('useradmin_csrf', '');
        if (!is_string($token) || strlen($token) < 32) {
            try {
                $token = bin2hex(random_bytes(32));
            } catch (Exception $exception) {
                $token = hash('sha256', uniqid('', true) . mt_rand());
            }
            $this->setSession('useradmin_csrf', $token);
        }
        return $token;
    }

    private function assertAdministrator()
    {
        if (!$this->objUser->isLoggedIn() || !$this->objUser->isAdmin()) {
            throw new customException(
                $this->objLanguage->languageText(
                    'mod_useradmin_insufficientperms',
                    'useradmin',
                    'You do not have sufficient permission to process this action.'
                )
            );
        }
    }
}
?>
