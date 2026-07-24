<?php
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class groupadmin extends controller
{
    public $objLanguage;
    public $objUser;
    public $objLog;

    public function init()
    {
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objLog = $this->newObject('logactivity', 'logger');
        $this->objLog->log();
    }

    public function dispatch($action)
    {
        $this->assertAdministrator();

        switch (strtolower((string) $action)) {
            case 'addmember':
                return $this->mutateMembership('add');
            case 'removemember':
                return $this->mutateMembership('remove');
            default:
                return $this->nativeInterface();
        }
    }

    public function nativeInterface()
    {
        $this->assertAdministrator();

        $service = $this->getObject('groupadminreadservice', 'groupadmin');
        $snapshot = $service->getSnapshot(
            $this->getParam('groupid'),
            $this->getParam('page', 1),
            $this->getParam('limit', 25),
            $this->getParam('q', ''),
            $this->getParam('sort', 'name'),
            $this->getParam('dir', 'asc')
        );

        $token = $this->getSession('membership_csrf', '');
        if (!is_string($token) || strlen($token) < 32) {
            try {
                $token = bin2hex(random_bytes(32));
            } catch (Exception $exception) {
                $token = hash('sha256', uniqid('', true) . mt_rand());
            }
            $this->setSession('membership_csrf', $token);
        }

        $this->setVar('groupAdminSnapshot', $snapshot);
        $this->setVar('groupAdminCsrfToken', $token);
        $this->setVar('groupAdminMessage', $this->getParam('message', ''));
        $this->setVar('groupAdminError', $this->getParam('error', ''));

        return 'native_readonly_tpl.php';
    }

    private function mutateMembership($operation)
    {
        $this->assertPost();
        $this->assertCsrfToken();

        $groupId = $this->getParam('groupid');
        $userId = $this->getParam('userid');

        $service = $this->getObject('groupservice', 'groupadmin');
        $result = $operation === 'add'
            ? $service->addMember($groupId, $userId)
            : $service->removeMember($groupId, $userId);

        $params = array(
            'groupid' => $groupId,
            'page' => $this->getParam('page', 1),
            'limit' => $this->getParam('limit', 25),
            'q' => $this->getParam('q', ''),
            'sort' => $this->getParam('sort', 'name'),
            'dir' => $this->getParam('dir', 'asc'),
        );

        if (!empty($result['ok'])) {
            $params['message'] = $result['code'];
        } else {
            $params['error'] = isset($result['code'])
                ? $result['code']
                : 'membership_update_failed';
        }

        return $this->nextAction('native', $params, 'groupadmin');
    }

    private function assertPost()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])
            || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'POST') {
            throw new customException('This action requires an HTTP POST request.');
        }
    }

    private function assertCsrfToken()
    {
        $expected = $this->getSession('membership_csrf', '');
        $provided = $this->getParam('csrf_token', '');

        if (!is_string($expected)
            || !is_string($provided)
            || $expected === ''
            || $provided === ''
            || !hash_equals($expected, $provided)) {
            throw new customException(
                'The security token is missing or invalid. Reload the page and try again.'
            );
        }
    }

    private function assertAdministrator()
    {
        if (!$this->objUser->isLoggedIn() || !$this->objUser->isAdmin()) {
            throw new customException(
                $this->objLanguage->languageText(
                    'mod_groupadmin_insufficientperms',
                    'groupadmin',
                    'You do not have sufficient permission to process this action.'
                )
            );
        }
    }
}
?>
