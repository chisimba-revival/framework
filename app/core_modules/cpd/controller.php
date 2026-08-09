<?php
/**
 * Context CPD configuration, manual allocation and learner history.
 *
 * @category  Chisimba
 * @package   cpd
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class cpd extends controller
{
    const CSRF_CONTEXT = 'cpd_phase1_ui';
    private $user;
    private $language;
    private $service;
    private $context;
    private $users;
    private $csrf;

    public function init()
    {
        $this->user = $this->getObject('user', 'security');
        $this->language = $this->getObject('language', 'language');
        $this->service = $this->getObject('cpdservice', 'cpd');
        $this->context = $this->getObject('dbcontext', 'context');
        $this->users = $this->getObject('userservice', 'security');
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $this->csrf = $stack['csrf'];
    }

    public function requiresLogin($action) { return true; }

    public function isValid($action, $default = true)
    {
        return in_array((string) $action, array('', 'default', 'createscheme',
            'createcategory', 'recognise', 'allocate'), true);
    }

    public function dispatch($action)
    {
        $result = null;
        switch ((string) $action) {
            case 'createscheme': $result = $this->createScheme(); break;
            case 'createcategory': $result = $this->createCategory(); break;
            case 'recognise': $result = $this->recognise(); break;
            case 'allocate': $result = $this->allocate(); break;
        }
        return $this->page($result);
    }

    private function page($result)
    {
        $contextCode = trim((string) $this->context->getContextCode());
        $isAdmin = $this->user->isAdmin();
        $canManage = $contextCode !== '' && ($isAdmin || $this->user->isContextLecturer($this->user->userId(), $contextCode));
        $schemes = $this->service->listSchemes();
        $categories = array();
        foreach ($schemes as $scheme) {
            $categories[$scheme['id']] = $this->service->listCategories($scheme['id']);
        }
        $recognition = $contextCode === '' ? null : $this->service->currentRecognition($contextCode);
        $history = $canManage ? $this->service->historyForContext($contextCode)
            : $this->service->historyForIdentity($this->user->userId());
        $this->setVar('cpdText', $this->texts());
        $this->setVar('cpdResult', $this->localiseResult($result));
        $this->setVar('cpdCsrf', $this->csrf->issue(self::CSRF_CONTEXT));
        $this->setVar('cpdContextCode', $contextCode);
        $this->setVar('cpdContextTitle', $contextCode === '' ? '' : $this->context->getTitle($contextCode, false));
        $this->setVar('cpdIsAdmin', $isAdmin);
        $this->setVar('cpdCanManage', $canManage);
        $this->setVar('cpdSchemes', $schemes);
        $this->setVar('cpdCategories', $categories);
        $this->setVar('cpdRecognition', $recognition);
        $this->setVar('cpdUsers', $canManage ? $this->users->listUsers('', false) : array());
        $this->setVar('cpdHistory', $history);
        $this->setVar('cpdToday', date('d-m-Y'));
        return 'cpd_home_tpl.php';
    }

    private function createScheme()
    {
        if (($error = $this->mutationError(true, false)) !== null) { return $error; }
        return $this->service->createScheme(array('schemeKey' => $this->getParam('scheme_key'),
            'name' => $this->getParam('name'), 'description' => $this->getParam('description'),
            'actorUserId' => $this->user->userId()));
    }

    private function createCategory()
    {
        if (($error = $this->mutationError(true, false)) !== null) { return $error; }
        return $this->service->createCategory(array('schemeId' => $this->getParam('scheme_id'),
            'categoryKey' => $this->getParam('category_key'), 'name' => $this->getParam('name'),
            'description' => $this->getParam('description'), 'actorUserId' => $this->user->userId()));
    }

    private function recognise()
    {
        if (($error = $this->mutationError(true, true)) !== null) { return $error; }
        return $this->service->recogniseContext(array('contextCode' => $this->context->getContextCode(),
            'schemeId' => $this->getParam('scheme_id'), 'categoryId' => $this->getParam('category_id'),
            'points' => $this->getParam('points'), 'validFrom' => $this->canonicalDate($this->getParam('valid_from'), true),
            'validUntil' => $this->canonicalDate($this->getParam('valid_until'), true), 'reason' => $this->getParam('reason'),
            'actorUserId' => $this->user->userId()));
    }

    private function allocate()
    {
        if (($error = $this->mutationError(false, true)) !== null) { return $error; }
        $recognition = $this->service->currentRecognition($this->context->getContextCode(), $this->getParam('scheme_id'));
        return $this->service->allocateManual(array('identityUserId' => $this->getParam('identity_user_id'),
            'contextCode' => $this->context->getContextCode(), 'schemeId' => $this->getParam('scheme_id'),
            'categoryId' => $this->getParam('category_id'), 'recognitionId' => is_array($recognition) ? $recognition['id'] : '',
            'points' => $this->getParam('points'), 'completionBasis' => $this->getParam('completion_basis'),
            'reason' => $this->getParam('reason'), 'effectiveDate' => $this->canonicalDate($this->getParam('effective_date'), false),
            'idempotencyKey' => 'manual-ui-' . bin2hex(random_bytes(16)), 'actorUserId' => $this->user->userId()));
    }

    private function mutationError($adminOnly, $contextRequired)
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') { return array('ok' => false, 'code' => 'post_required'); }
        if (!$this->csrf->consume(self::CSRF_CONTEXT, (string) $this->getParam('csrf_token', ''))) { return array('ok' => false, 'code' => 'invalid_csrf'); }
        $contextCode = trim((string) $this->context->getContextCode());
        $allowed = $adminOnly ? $this->user->isAdmin() : ($this->user->isAdmin() || ($contextCode !== '' && $this->user->isContextLecturer($this->user->userId(), $contextCode)));
        if (!$allowed || ($contextRequired && $contextCode === '')) { return array('ok' => false, 'code' => 'forbidden'); }
        return null;
    }

    private function texts()
    {
        $keys = array('title','intro','schemeheading','createscheme','scheme_key','scheme_key_help','scheme_name','description','create_scheme_button','createcategory','category_intro','scheme','category_key','category_key_help','category_name','create_category_button','noschemes','nocategories','contextheading','recogniseheading','recognise_intro','category','points','points_help','validfrom','validuntil','date_help','reason','recognise_button','currentrecognition','norecognition','allocateheading','learner','completionbasis','effectivedate','allocate_button','historyheading','nohistory','date','type','identity','result_success','result_failure','postrequired','invalidcsrf','forbidden','select','ownhistory');
        $out = array();
        $contextWord = $this->language->languageText('word_course', 'system');
        foreach ($keys as $key) {
            $text = $this->language->languageText('mod_cpd_' . $key, 'cpd');
            $out[$key] = str_ireplace('[-CONTEXT-]', $contextWord, $text);
        }
        return $out;
    }

    private function canonicalDate($value, $allowEmpty)
    {
        $value = trim((string) $value);
        if ($value === '' && $allowEmpty) { return ''; }
        if (!preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $value, $parts)) { return '__invalid_date__'; }
        $day = (int) $parts[1];
        $month = (int) $parts[2];
        $year = (int) $parts[3];
        if (!checkdate($month, $day, $year)) { return '__invalid_date__'; }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function localiseResult($result)
    {
        if (!is_array($result) || !empty($result['ok'])) { return $result; }
        $code = (string) ($result['code'] ?? '');
        $direct = array('post_required' => 'postrequired', 'invalid_csrf' => 'invalidcsrf', 'forbidden' => 'forbidden');
        $key = isset($direct[$code]) ? $direct[$code] : 'error_' . $code;
        $message = $this->language->languageText('mod_cpd_' . $key, 'cpd');
        $result['message'] = $message === 'mod_cpd_' . $key ? $this->language->languageText('mod_cpd_result_failure', 'cpd') : $message;
        return $result;
    }
}
?>
