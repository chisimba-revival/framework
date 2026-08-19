<?php
/**
 * Administrative dashboard for the shared Chisimba AI service.
 *
 * @category  Chisimba
 * @package   ai
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }

class ai extends controller
{
    const CSRF_CONTEXT = 'ai_admin_diagnostic';
    public $objUser;
    public $objLanguage;
    private $service;
    private $csrf;

    public function init()
    {
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->service = $this->getObject('aiservice', 'ai');
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $this->csrf = $stack['csrf'];
    }

    public function requiresLogin($action) { return true; }

    public function isValid($action, $default = true)
    {
        return $this->objUser->isAdmin()
            && in_array((string) $action, array('', 'default', 'diagnostic'), true);
    }

    public function dispatch($action)
    {
        $result = null;
        if ((string) $action === 'diagnostic') { $result = $this->runDiagnostic(); }
        $this->setVar('aiStatus', $this->service->providerStatus());
        $this->setVar('aiUsage', $this->service->usageSummary());
        $this->setVar('aiDiagnostic', $result);
        $this->setVar('aiToken', $this->csrf->issue(self::CSRF_CONTEXT));
        $this->setVar('aiText', $this->languageItems());
        return 'dashboard_tpl.php';
    }

    private function runDiagnostic()
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            return array('ok' => false, 'error' => 'post_required');
        }
        if (!$this->csrf->consume(self::CSRF_CONTEXT, (string) $this->getParam('csrf_token', ''))) {
            return array('ok' => false, 'error' => 'invalid_csrf');
        }
        return $this->service->execute(array(
            'consumer' => 'ai',
            'task' => 'diagnostic',
            'instructions' => $this->text('diagnosticinstruction'),
            'input' => $this->text('diagnosticinput'),
            'schemaName' => 'chisimba_ai_diagnostic',
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    'message' => array('type' => 'string'),
                    'confidence' => array('type' => 'number', 'minimum' => 0, 'maximum' => 1)
                ),
                'required' => array('message', 'confidence'),
                'additionalProperties' => false
            )
        ));
    }

    private function languageItems()
    {
        $keys = array('title', 'intro', 'provider', 'model', 'configured', 'yes', 'no',
            'requests', 'inputtokens', 'outputtokens', 'diagnostic', 'diagnosticintro',
            'run', 'result', 'success', 'failure', 'message', 'confidence', 'error', 'notrun');
        $items = array();
        foreach ($keys as $key) { $items[$key] = $this->text($key); }
        return $items;
    }

    private function text($key)
    {
        return $this->objLanguage->languageText('mod_ai_dashboard_' . $key, 'ai');
    }
}
?>
