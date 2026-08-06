<?php
/**
 * Administrator diagnostic and deliberate SendGrid test.
 *
 * @category  Chisimba
 * @package   communications
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }

class communications extends controller
{
    const CSRF_CONTEXT = 'communications_admin_send_test';
    public $objUser;
    public $objLanguage;
    private $service;
    private $worker;
    private $attempts;
    private $csrf;

    public function init()
    {
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->service = $this->getObject('communicationservice', 'communications');
        $this->worker = $this->getObject('communicationworker', 'communications');
        $this->attempts = $this->getObject('communicationattempts', 'communications');
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $this->csrf = $stack['csrf'];
    }

    public function requiresLogin($action) { return true; }

    public function isValid($action, $default = true)
    {
        return $this->objUser->isAdmin()
            && in_array((string) $action, array('', 'default', 'sendtest'), true);
    }

    public function dispatch($action)
    {
        $result = null;
        if ((string) $action === 'sendtest') { $result = $this->sendTest(); }
        $attempt = $this->attempts->latest();
        if (is_array($attempt) && isset($attempt['error_detail'])) {
            $attempt['error_detail'] = $this->redact((string) $attempt['error_detail']);
        }
        $this->setVar('diagnosticAttempt', $attempt);
        $this->setVar('sendResult', $result);
        $this->setVar('sendToken', $this->csrf->issue(self::CSRF_CONTEXT));
        $this->setVar('communicationsText', $this->languageItems());
        return 'admin_test_tpl.php';
    }

    private function sendTest()
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            return array('ok' => false, 'code' => 'post_required');
        }
        if (!$this->csrf->consume(self::CSRF_CONTEXT, (string) $this->getParam('csrf_token', ''))) {
            return array('ok' => false, 'code' => 'invalid_csrf');
        }
        $message = array(
            'to' => trim((string) $this->getParam('recipient', '')),
            'subject' => $this->text('subject'),
            'text' => $this->text('body'),
            'idempotencyKey' => 'admin-sendgrid-test-' . bin2hex(random_bytes(12)),
            'metadata' => array('purpose' => 'admin_sendgrid_test')
        );
        $queued = $this->service->queueEmail($message);
        if (empty($queued['ok']) || empty($queued['messageId'])) {
            return array('ok' => false, 'code' => (string) ($queued['code'] ?? 'queue_failed'));
        }
        $worker = $this->worker->runMessage($queued['messageId']);
        $status = $this->service->status($queued['messageId']);
        return array('ok' => true, 'queued' => $queued, 'worker' => $worker, 'status' => $status);
    }

    private function languageItems()
    {
        $keys = array('title', 'intro', 'noattempt', 'messageid', 'attemptnumber',
            'transport', 'outcome', 'responsecode', 'errordetail', 'created',
            'formintro', 'recipient', 'button', 'result', 'queuecode', 'finalstatus',
            'success', 'failure');
        $items = array();
        foreach ($keys as $key) { $items[$key] = $this->text($key); }
        return $items;
    }

    private function text($key)
    {
        return $this->objLanguage->languageText(
            'mod_communications_sendtest_v2_' . $key,
            'communications'
        );
    }

    private function redact($detail)
    {
        $detail = preg_replace('/Bearer\\s+[^\\s"\']+/i', 'Bearer ***', $detail);
        $detail = preg_replace('/SG\\.[A-Za-z0-9_-]+\\.[A-Za-z0-9_-]+/', '***', $detail);
        return preg_replace('/("?(?:api[_-]?key|authorization|token|secret)"?\\s*[:=]\\s*")([^"]+)(")/i', '$1***$3', $detail);
    }
}
?>
