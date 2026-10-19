<?php

/**
 * Context Admin Controller
 *
 * Controller class for the Context Creation/Management in Chisimba
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   contextadmin
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2008 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
// security check - must be included in all scripts
if (!
        /**
         * Description for $GLOBALS
         * @global entry point $GLOBALS['kewl_entry_point_run']
         * @name   $kewl_entry_point_run
         */
        $GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * Context Admin Controller
 *
 * Controller class for the Context Creation/Management in Chisimba
 *
 * @category  Chisimba
 * @package   contextadmin
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2008 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class contextadmin extends controller {
    public $objCourseCreation;
    public $objContextAuthors;
    public $objModules;
    public $csrf;

    const CSRF_CONTEXT = 'contextadmin_step1';
    const CSRF_AUTHORS = 'contextadmin_authors';

    /**
     * The user Object
     *
     * @var object $objUser
     */
    public $objUser;
    /**
     * The Context Object
     *
     * @var object $objContext
     */
    public $objContext;
    /**
     * The Learner Outcomes Object
     *
     * @var object $objDBLearnerOutcomes
     */
    public $objDBLearnerOutcomes;
    /**
     * The User Context Object
     *
     * @var object $objUserContext
     */
    public $objUserContext;
    /**
     * The Language Object
     *
     * @var object $objLanguage
     */
    public $objLanguage;

    /**
     * Constructor
     */
    public function init() {
        $this->objUser = $this->getObject('user', 'security');
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->objDBLearnerOutcomes = $this->getObject('dbcontext_learneroutcomes', 'context');
        $this->objUserContext = $this->getObject('usercontext', 'context');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objConfig = $this->getObject('altconfig', 'config');
        $this->objCourseCreation = $this->getObject('coursecreationservice', 'contextadmin');
        $this->objContextAuthors = $this->getObject('contextauthorservice', 'contextadmin');
        $this->objModules = $this->getObject('modules', 'modulecatalogue');
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $this->csrf = $stack['csrf'];
    }

    public function requiresLogin($action) {
        return TRUE;
    }

    /**
     * Standard Dispatch Function for Controller
     *
     * @access public
     * @param string $action Action being run
     * @return string Filename of template to be displayed
     */
    public function dispatch($action) {
        // Method to set the layout template for the given action
        $this->setLayoutTemplate('contextadmin_layout.php');

        /*
         * Convert the action into a method (alternative to
         * using case selections)
         */
        $method = $this->getMethod($action);
        /*
         * Return the template determined by the method resulting
         * from action
         */
        return $this->$method();
    }

    /**
     *
     * Method to convert the action parameter into the name of
     * a method of this class.
     *
     * @access private
     * @param string $action The action parameter passed byref
     * @return string the name of the method
     *
     */
    private function getMethod(& $action) {
        if ($this->validAction($action)) {
            return '__' . $action;
        } else {
            return '__home';
        }
    }

    /**
     *
     * Method to check if a given action is a valid method
     * of this class preceded by double underscore (__). If it __action
     * is not a valid method it returns FALSE, if it is a valid method
     * of this class it returns TRUE.
     *
     * @access private
     * @param string $action The action parameter passed byref
     * @return boolean TRUE|FALSE
     *
     */
    private function validAction(& $action) {
        if (method_exists($this, '__' . $action)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Context Admin Home
     */
    private function __home() {
        $contextCode = trim((string) $this->objContext->getContextCode());
        if ($contextCode !== '' && $contextCode !== 'root'
            && $this->canManageContext($contextCode)
        ) {
            return $this->nextAction(
                'controlpanel',
                array(),
                'context'
            );
        }

        $content = $this->objUserContext->getUserContextsFormatted($this->objUser->userId());

        $this->setVarByRef('content', $content);

        $title = $this->objLanguage->code2Txt('phrase_mycourses', 'system', NULL, 'My [-contexts-]');

        $this->setVarByRef('title', $title);

        return 'main.php';
    }

    /**
     * Method to create a new context
     */
    private function __add() {
        if (!$this->canCreateContext()) {
            return $this->nextAction(NULL, array('error' => 'forbidden'));
        }
        $this->setVar('mode', 'add');
        $this->prepareStep1Vars();
        return 'step1.php';
    }

    /**
     * Method to save step 1 of creating a context - context details
     */
    private function __savestep1() {
        if (!$this->isPost() || !$this->canCreateContext()
            || !$this->csrf->consume(self::CSRF_CONTEXT, (string) $this->getParam('csrf_token', ''))) {
            return $this->nextAction('add', array('error' => 'invalidrequest'));
        }
        $mode = $this->getParam('mode');
        $contextCode = $this->getParam('contextcode');
        $title = $this->getParam('title');
        $canvas = $this->getParam('canvas');
        $showcomment = $this->getParam('showcomment');

        $status = $this->getParam('status');
        $access = $this->getParam('access', 'Private');
        $about = '';

        $emailalert = $this->getParam('emailalertopt');
        $alerts = 0;
        if ($emailalert == 'on') {
            $alerts = '1';
        } else {
            $alerts = '0';
        }

        $result = $this->objCourseCreation->create(array(
            'contextCode' => $contextCode, 'title' => $title, 'status' => $status,
            'access' => $access, 'showComment' => $showcomment, 'alerts' => $alerts,
            'accessPolicy' => $this->getParam('access_policy', ''),
            'privateAdmissionMode' => $this->getParam('private_admission_mode', ''),
            'deliveryFormat' => $this->getParam('delivery_format', 'standard'),
            'navigationMode' => $this->getParam('navigation_mode', ''),
            'useSections' => $this->getParam('use_sections') === '1',
            'cpdEnabled' => $this->getParam('cpd_enabled') === '1',
            'cpdSchemeId' => $this->getParam('cpd_scheme_id'),
            'cpdCategoryId' => $this->getParam('cpd_category_id'),
            'cpdPoints' => $this->getParam('cpd_points'),
            'cpdValidFrom' => $this->canonicalDate($this->getParam('cpd_valid_from'), TRUE),
            'cpdValidUntil' => $this->canonicalDate($this->getParam('cpd_valid_until'), TRUE),
            'cpdReason' => $this->getParam('cpd_reason'),
            'actorUserId' => $this->objUser->userId()
        ));

        // If successfully created
        if (!empty($result['ok'])) {

            $this->setSession('fixup', NULL);
            $this->setSession('contextCode', $contextCode);

            $objContextBlocks = $this->getObject('dbcontextblocks', 'context');
            $objContextBlocks->addBlock('block|learningjourney|context', 'middle', $contextCode, 'context');
            $objContextBlocks->addBlock('block|aboutcontext|context', 'middle', $contextCode, 'context');

            return $this->nextAction('step2', array('mode' => 'add', 'contextcode' => $contextCode));
        } else { // Else fix up errors
            $fixup = array('contextcode' => $contextCode, 'title' => $title, 'status' => $status,
                'showcomment' => $showcomment, 'access' => $access, 'alerts' => $alerts,
                'delivery_format' => $this->getParam('delivery_format', 'standard'),
                'navigation_mode' => $this->getParam('navigation_mode', ''),
                'use_sections' => $this->getParam('use_sections'),
                'cpd_enabled' => $this->getParam('cpd_enabled'),
                'cpd_scheme_id' => $this->getParam('cpd_scheme_id'),
                'cpd_category_id' => $this->getParam('cpd_category_id'),
                'cpd_points' => $this->getParam('cpd_points'),
                'cpd_valid_from' => $this->getParam('cpd_valid_from'),
                'cpd_valid_until' => $this->getParam('cpd_valid_until'),
                'cpd_reason' => $this->getParam('cpd_reason'),
                'creation_error' => $result['code'] ?? 'create_failed');
            $this->setSession('fixup', $fixup);

            return $this->nextAction('add', array('mode' => 'fixup', 'contextcode' => $contextCode));
        }
    }

    /**
     * Step 2 of Creating a Context - Adding About Info and Image
     */
    private function informationPurpose($code) {
        return 'context_information_' . substr(hash('sha256', $code), 0, 32);
    }

    private function showInformation($contextCode, $context, $draft = array(), $error = '') {
        $this->setVar('mode', $this->getParam('mode') === 'edit' ? 'edit' : 'add');
        $this->setVar('contextCode', $contextCode);
        if (isset($draft['about'])) { $context['about'] = $draft['about']; }
        $this->setVar('context', $context);
        $this->setVar('informationDraft', $draft);
        $this->setVar('informationError', $error);
        $this->setVar('informationCsrf', $this->csrf->issue($this->informationPurpose($contextCode)));
        return 'step2.php';
    }

    private function __step2() {
        $contextCode = $this->getSession('contextCode');
        if (!$this->getObject('contextsettingsaccess', 'context')->canEdit($contextCode)) {
            http_response_code(403); return $this->nextAction(NULL);
        }
        $context = $this->objContext->getContext($contextCode);
        if (!$context) { return $this->nextAction(NULL); }
        return $this->showInformation($contextCode, $context);
    }

    /** Save the information form; failed/expired requests retain the draft. */
    private function __savestep2() {
        $contextCode = $this->getSession('contextCode');
        if (!$this->getObject('contextsettingsaccess', 'context')->canEdit($contextCode)
            || $this->getParam('contextCode') !== $contextCode) {
            http_response_code(403); return $this->nextAction(NULL);
        }
        if (!$this->isPost()) { http_response_code(405); return $this->nextAction('step2'); }
        $context = $this->objContext->getContext($contextCode);
        if (!$context) { http_response_code(404); return $this->nextAction(NULL); }
        $draft = array();
        foreach (array('about', 'imageselect', 'removeimage') as $field) {
            $value = $this->getParam($field, '');
            $draft[$field] = is_string($value) ? $value : '';
        }
        $token = $this->getParam('csrf_token', '');
        if (!is_string($token) || !$this->csrf->consume($this->informationPurpose($contextCode), $token)) {
            return $this->showInformation($contextCode, $context, $draft, 'expired');
        }
        $image = $draft['imageselect'];
        $objContextImage = $this->getObject('contextimage', 'context');
        // A selected replacement takes precedence over removal. Never remove first.
        if ($image !== '') {
            if (!$objContextImage->setContextImage($contextCode, $image)) {
                return $this->showInformation($contextCode, $context, $draft, 'image');
            }
        } elseif ($draft['removeimage'] === '1' && !$objContextImage->removeContextImage($contextCode)) {
            return $this->showInformation($contextCode, $context, $draft, 'image');
        }
        $about = $this->getObject('richtextsanitizer', 'utilities')->cleanHtml($draft['about']);
        if ($this->objContext->updateAbout($contextCode, $about) === false) {
            return $this->showInformation($contextCode, $context, $draft, 'save');
        }
        return $this->nextAction('step3', array('mode' => $this->getParam('mode') === 'edit' ? 'edit' : 'add', 'contextCode' => $contextCode));
    }

    private function showWizardStep($step, $draft = null, $error = '', $revision = null) {
        $code = $this->getSession('contextCode');
        $service = $this->getObject('coursewizardservice', 'contextadmin');
        if (!$service->canEdit($code)) { http_response_code(403); return $this->nextAction(NULL); }
        try { $state = $this->getObject('coursewizardstore', 'contextadmin')->snapshot($code, $step); }
        catch (Throwable $error) { http_response_code(404); return $this->nextAction(NULL); }
        $this->setVar('wizardStep', $step);
        $this->setVar('wizardState', $state);
        $this->setVar('wizardDraft', $draft);
        $this->setVar('wizardError', $error);
        $this->setVar('wizardSaved', $this->getParam('saved') === '1');
        $this->setVar('wizardRevision', is_string($revision) ? $revision : $state['revision']);
        $this->setVar('wizardCsrf', $service->csrf()->issue($service->purpose($code, $step)));
        $this->setVar('contextCode', $code);
        $this->setVar('mode', $this->getParam('mode') === 'add' ? 'add' : 'edit');
        $this->setVar('plugins', $step === 'plugins' ? $service->offeredPlugins() : []);
        return 'wizard_final.php';
    }

    private function __step3() { return $this->showWizardStep('outcomes'); }
    private function __step4() { return $this->showWizardStep('plugins'); }
    private function __savestep3() { return $this->saveWizardStep('outcomes'); }
    private function __savestep4() { return $this->saveWizardStep('plugins'); }

    private function saveWizardStep($step) {
        $code = $this->getSession('contextCode');
        $changes = $step === 'plugins' ? $this->getParam('plugins', []) : [
            'existing' => $this->getParam('outcomes', []),
            'remove' => $this->getParam('remove', []),
            'new' => $this->getParam('newoutcomes', [])
        ];
        $input = ['contextCode' => $this->getParam('contextCode'), 'csrf_token' => $this->getParam('csrf_token'),
            'revision' => $this->getParam('revision'), 'changes' => $changes,
            'operation' => $this->getParam('operation', 'save')];
        $result = $this->getObject('coursewizardservice', 'contextadmin')->submit($code, $step, $input, $_SERVER['REQUEST_METHOD'] ?? '');
        if (in_array($result, ['denied','method'], true)) {
            http_response_code($result === 'method' ? 405 : 403);
            $this->setLayoutTemplate(NULL); $this->setPageTemplate(NULL); echo 'forbidden'; return;
        }
        if ($result === 'add') {
            if (!is_array($changes['new'])) $changes['new'] = [];
            if (count($changes['new']) < 20) $changes['new'][] = '';
            return $this->showWizardStep($step, $changes, '', $input['revision']);
        }
        if ($result !== '') return $this->showWizardStep($step, is_array($changes) ? $changes : [], $result, $input['revision']);
        return $step === 'outcomes'
            ? $this->nextAction('step4', ['mode' => $this->getParam('mode') === 'add' ? 'add' : 'edit'])
            : $this->nextAction('step4', ['mode' => 'edit', 'saved' => '1']);
    }

    /**
     * Method to show a form to update a context
     */
    private function __edit() {
        $contextCode = $this->getParam('contextcode');
        $context = $this->objContext->getContext($contextCode);

        if ($context == FALSE) {
            return $this->nextAction(NULL);
        }

        if (!$this->canManageContext($contextCode)) {
            return $this->nextAction(NULL, array('error' => 'forbidden'));
        }
        $this->setVarByRef('context', $context);
        $this->setSession('contextCode', $contextCode);

        $this->setVar('mode', 'edit');
        $this->prepareStep1Vars();
        return 'step1.php';
    }

    /**
     * Method to update the details of a context
     */
    private function __updatecontext() {
        if (!$this->isPost()
            || !$this->csrf->consume(self::CSRF_CONTEXT, (string) $this->getParam('csrf_token', ''))) {
            return $this->nextAction(NULL, array('error' => 'invalidrequest'));
        }
        $contextCode = $this->getParam('editcontextcode');
        if (!$this->canManageContext($contextCode)) {
            return $this->nextAction(NULL, array('error' => 'forbidden'));
        }
        $title = $this->getParam('title');
        $canvas = $this->getParam('canvas');

        $status = $this->getParam('status');
        $showcomment = $this->getParam('showcomment');
        $access = $this->getParam('access');
        $accessPolicy = $this->objContext->normaliseAccessPolicy(
            $this->getParam('access_policy', ''),
            true
        );
        $privateAdmissionMode = $this->objContext->normalisePrivateAdmissionMode(
            $this->getParam('private_admission_mode', ''),
            true
        );
        if ($accessPolicy !== 'private') {
            $privateAdmissionMode = null;
        }
        $goals = $this->getParam('goals');
        $mode = $this->getParam('mode');
        $emailalert = $this->getParam('emailalertopt');
        $alerts = '0';
        if ($emailalert == 'on') {
            $alerts = '1';
        } else {
            $alerts = '0';
        }

        if (trim((string) $title) === ''
            || !in_array($status, array('Published', 'Unpublished'), TRUE)
            || !in_array($access, array('Public', 'Open', 'Private'), TRUE)
            || $accessPolicy === FALSE || $privateAdmissionMode === FALSE
            || !in_array((string) $showcomment, array('0', '1'), TRUE)) {
            return $this->nextAction('edit', array('contextcode' => $contextCode, 'error' => 'invalidsettings'));
        }

        if ($contextCode != $this->getSession('contextCode')) {
            $newContext = $this->getSession('contextCode');
            return $this->nextAction(NULL, array('message' => 'contextswitch', 'context1' => $contextCode, 'context2' => $newContext));
        } else {

            $context = $this->objContext->getContext($contextCode);

            if ($context == FALSE) {
                return $this->nextAction(NULL, array('message' => 'editnonexistingcontext'));
            } else {
                /**
                 * $contextCode,
            $title=FALSE,
            $status=FALSE,
            $access=FALSE,
            $about=FALSE,
            $goals=FALSE,
            $showcomment=FALSE,
            $alerts='',
            $lastaccessed=FALSE,
            $canvas=""
                 */
                $this->objContext->updateContext(
                        $contextCode,
                        $title,
                        $status,
                        $access,
                        $context['about'],
                        $goals,
                        $showcomment,
                        $alerts, null, $canvas,
                        $this->getParam('delivery_format', 'standard'),
                        $this->getParam('navigation_mode', 'free'),
                        $accessPolicy, $privateAdmissionMode,
                        $this->getParam('use_sections') === '1' ? 1 : 0);

                return $this->nextAction('step2', array('mode' => 'edit'));
            }
        }
    }

    /**
     * Method to delete a context
     */
    private function __delete() {
        $code=$this->getParam('contextcode');
        $repair=$this->getParam('repair')==='1';
        if (!is_string($code) || $code==='' || strlen($code)>32 || !$this->canManageAuthorRoster($code)
            || ($repair && !$this->objUser->isAdmin())) return $this->nextAction(NULL,['error'=>'forbidden']);
        $context=$this->objContext->getContext($code);
        if (($repair && $context) || (!$repair && !$context)) return $this->nextAction(NULL,['error'=>'forbidden']);
        $this->setVar('context',$context ?: ['contextcode'=>$code,'title'=>$code]);
        $this->setVar('repairDeletion',$repair);
        $this->setVar('deleteCsrf',$this->csrf->issue('context_delete_'.hash('sha256',$code.':'.(int)$repair)));
        return 'delete.php';
    }

    /** POST and a scope-bound CSRF token are required for deletion and repair. */
    private function __deleteconfirm() {
        $code=$this->getParam('contextcode');
        $repair=$this->getParam('repair')==='1';
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST' || !is_string($code)
            || !$this->canManageAuthorRoster($code) || ($repair && !$this->objUser->isAdmin())
            || !$this->csrf->consume('context_delete_'.hash('sha256',$code.':'.(int)$repair),(string)$this->getParam('csrf_token',''))) {
            return $this->nextAction('delete',['contextcode'=>$code,'repair'=>(int)$repair,'deletefailed'=>1]);
        }
        $context=$this->objContext->getContext($code);
        try {
            $result=$this->getObject('contextdeletion','context')->remove($code,$repair);
            if (!$result) throw new RuntimeException('Context not found');
        } catch (Throwable $error) {
            error_log('Context deletion failed: '.$error->getMessage());
            return $this->nextAction('delete',['contextcode'=>$code,'repair'=>(int)$repair,'deletefailed'=>1]);
        }
        return $this->nextAction(NULL,['message'=>'contextdeleted','context'=>$code,'title'=>$context['title']??$code,'result'=>'y']);
    }


    /**
     * Manage contextual authors and course ownership.
     */
    private function __authors() {
        $contextCode = trim((string) $this->getParam('contextcode', $this->getSession('contextCode', '')));
        if ($contextCode === '' || !$this->canManageContext($contextCode)) {
            return $this->nextAction(NULL, array('error' => 'forbidden'));
        }
        $snapshot = $this->objContextAuthors->snapshot($contextCode, $this->objUser->userId());
        if (empty($snapshot['ok'])) {
            return $this->nextAction(NULL, array('error' => 'forbidden'));
        }
        $this->setSession('contextCode', $contextCode);
        $this->setVar('contextAuthorSnapshot', $snapshot);
        $this->setVar('contextAuthorCsrf', $this->csrf->issue(self::CSRF_AUTHORS));
        $this->setVar('contextAuthorMessage', (string) $this->getParam('message', ''));
        $this->setVar('contextAuthorError', (string) $this->getParam('error', ''));
        return 'authors.php';
    }

    private function __addauthor() { return $this->mutateAuthors('add'); }
    private function __removeauthor() { return $this->mutateAuthors('remove'); }
    private function __transferowner() { return $this->mutateAuthors('transfer'); }

    private function mutateAuthors($operation) {
        $contextCode = trim((string) $this->getParam('contextcode', ''));
        if (!$this->isPost()
            || !$this->csrf->consume(self::CSRF_AUTHORS, (string) $this->getParam('csrf_token', ''))
            || !$this->canManageAuthorRoster($contextCode)) {
            return $this->nextAction('authors', array('contextcode' => $contextCode, 'error' => 'forbidden'));
        }
        $targetUserId = trim((string) $this->getParam('userid', ''));
        if ($operation === 'add') {
            $result = $this->objContextAuthors->addAuthor($contextCode, $targetUserId, $this->objUser->userId());
        } elseif ($operation === 'remove') {
            $result = $this->objContextAuthors->removeAuthor($contextCode, $targetUserId, $this->objUser->userId());
        } else {
            $result = $this->objContextAuthors->transferOwnership($contextCode, $targetUserId, $this->objUser->userId());
        }
        $key = !empty($result['ok']) ? 'message' : 'error';
        $code = isset($result['code']) ? (string) $result['code'] : 'operation_failed';
        return $this->nextAction('authors', array('contextcode' => $contextCode, $key => $code));
    }

    /**
     * Ajax function to detect whether a context code has been taken already or not
     */
    private function __checkcode() {
        $this->setPageTemplate(NULL);
        $this->setLayoutTemplate(NULL);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        if (!$this->canCreateContext()) {
            http_response_code(403);
            echo 'forbidden';
            return;
        }
        $code = $this->getParam('code');
        if (!is_string($code)) { http_response_code(400); echo 'invalid'; return; }
        $code = strtolower(trim($code));
        if ($code === 'root') { echo 'reserved'; return; }
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,254}$/', $code)) { echo 'invalid'; return; }
        echo $this->objContext->contextExists($code) ? 'exists' : 'ok';
    }

    private function isPost() {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST';
    }

    private function canCreateContext() {
        return $this->objContextAuthors->canCreateContext($this->objUser->userId());
    }

    private function canManageContext($contextCode) {
        return $this->objContextAuthors->canManageContext($contextCode, $this->objUser->userId());
    }

    private function canManageAuthorRoster($contextCode) {
        return $this->objContextAuthors->canManageAuthorRoster($contextCode, $this->objUser->userId());
    }

    private function prepareStep1Vars() {
        $this->setVar('contextAdminCsrf', $this->csrf->issue(self::CSRF_CONTEXT));
        $available = $this->objModules->checkIfRegistered('cpd', 'cpd');
        $schemes = array();
        $categories = array();
        if ($available) {
            $service = $this->getObject('cpdservice', 'cpd');
            $schemes = $service->listSchemes();
            foreach ($schemes as $scheme) {
                $categories[$scheme['id']] = $service->listCategories($scheme['id']);
            }
        }
        $this->setVar('contextAdminCpdAvailable', $available);
        $this->setVar('contextAdminCpdSchemes', $schemes);
        $this->setVar('contextAdminCpdCategories', $categories);
    }

    private function canonicalDate($value, $allowEmpty) {
        $value = trim((string) $value);
        if ($value === '' && $allowEmpty) { return ''; }
        if (!preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $value, $parts)) { return '__invalid_date__'; }
        $day = (int) $parts[1]; $month = (int) $parts[2]; $year = (int) $parts[3];
        if (!checkdate($month, $day, $year)) { return '__invalid_date__'; }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * Ajax function to remove a context image
     */
    private function __removeimage() {
        // Retired unsafe GET mutation. Image changes belong to the protected form.
        $this->setPageTemplate(NULL);
        $this->setLayoutTemplate(NULL);
        http_response_code(410);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'gone';
    }

    /**
     * Method to browse for contexts
     */
    private function __browseother() {
        $letter = $this->getParam('letter', 'A');

        $results = $this->objContext->getContextStartingWith($letter);

        if ((is_countable($results) ? count($results) : 0) == 0) {
            $this->setVar('content', 'No search results for ' . $letter);
        } else {
            $this->setVarByRef('contexts', $results);
        }
        $this->setVar('title', ucfirst($this->objLanguage->code2Txt('mod_contextadmin_contextsstartingwith', 'contextadmin', NULL, '[-contexts-] starting with')) . ' ' . $letter);

        return 'main.php';
    }

    /**
     * Method to search for contexts
     */
    private function __search() {
        $search = $this->getParam('search');
        $results = $this->objContext->searchContext($search);

        if ((is_countable($results) ? count($results) : 0) == 0) {
            $this->setVar('content', $this->objLanguage->languageText('mod_contextadmin_nosearchresultsfor', 'contextadmin', 'No search results for') . ' ' . $search);
        } else {
            $this->setVarByRef('contexts', $results);
        }
        $this->setVar('title', $this->objLanguage->languageText('phrase_searchresultsfor', 'system', 'Search Results for') . ' <em>' . $search . '</em>');

        return 'main.php';
    }

    /**
     * returns a template that allows uploading of themes
     * @return <type>
     */
    public function __uploadtheme() {
        return "uploadtheme_tpl.php";
    }

    /**
     * handles a theme uploaded from filemanager
     * @return <type>
     */
    public function __saveuploadedtheme() {

        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->contextCode = $this->objContext->getContextCode();
        $objFileUpload = $this->getObject('uploadinput', 'filemanager');
        $path = '/context/' . $this->contextCode . '/canvases';
        $objFileUpload->customuploadpath = $path;
        $objFileUpload->enableOverwriteIncrement = TRUE;
        $results = $objFileUpload->handleUpload('fileupload');
        $file = $_FILES[$objFileUpload->name];
        $filename = $file['name'];

        // Technically, FALSE can never be returned, this is just a precaution
        // FALSE means there is no fileinput with that name
        if ($results == FALSE) {
            return $this->nextAction('view', array('id' => $this->getParam('id'), 'error' => 'unabletoupload'));
        } else {
            // If successfully Uploaded
            if ($results['success']) {
                $objZip = $this->newObject('wzip', 'utilities');
                $objZip->unzip($this->objConfig->getcontentBasePath() . '/' . $path . '/' . $filename, $this->objConfig->getcontentBasePath() . '/' . $path );
                return $this->nextAction('edit', array('contextcode' => $this->contextCode));
            } else {
                // If not successfully uploaded
                return $this->nextAction('uploadtheme', array('error' => $results['reason']));
            }
        }
    }

    private function removeExtension($strName) {
        $ext = strrchr($strName, '.');

        if ($ext !== false) {
            $strName = substr($strName, 0, -strlen($ext));
        }
        return $strName;
    }

}

?>
