<?php

/**
 * Context controller
 *
 * Controller class for the context in Chisimba
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
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2008 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
// security check - must be included in all scripts
if (!/**
         * Description for $GLOBALS
         * @global entry point $GLOBALS['kewl_entry_point_run']
         * @name   $kewl_entry_point_run
         */
        $GLOBALS ['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * Context controller
 *
 * Controller class for the context in Chisimba
 *
 * @category  Chisimba
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2008 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class context extends controller {

    const COURSE_LAUNCH_CSRF = 'context_course_activity_launch';

    /**
     * Public context object
     *
     * @var object $objContext
     */
    public $objContext;

    /**
     * Current Context Code
     *
     * @var string $contextCode
     */
    private $contextCode;

    /**
     * Constructor
     */
    public function init() {
        try {
            $this->objContext = $this->getObject('dbcontext');

            $this->contextCode = $this->objContext->getContextCode();
            $this->setVarByRef('contextCode', $this->contextCode);

            $this->contextTitle = $this->objContext->getTitle();
            $this->setVarByRef('contextTitle', $this->contextTitle);

            $this->objLanguage = $this->getObject('language', 'language');
            $this->objUser = $this->getObject('user', 'security');
            $this->objUserContext = $this->getObject('usercontext', 'context');
            $this->objContextModules = $this->getObject('dbcontextmodules', 'context');
            $this->courseLauncher = $this->getObject('courseawarelaunchservice', 'context');
            $nativeAuth = $this->getObject('nativeauthwebcomposition', 'security')->build();
            $this->csrf = $nativeAuth['csrf'];

            $this->objContextBlocks = $this->getObject('dbcontextblocks');
            $this->objDynamicBlocks = $this->getObject('dynamicblocks', 'blocks');
            $this->objBlocks = $this->getObject('blocks', 'blocks');

            //Load Module Catalogue Class
            $this->objModuleCatalogue = $this->getObject('modules', 'modulecatalogue');

            $this->objContextGroups = $this->getObject('managegroups', 'contextgroups');

            if ($this->objModuleCatalogue->checkIfRegistered('activitystreamer')) {
                $this->objActivityStreamer = $this->getObject('activityops', 'activitystreamer');
                $this->eventDispatcher->addObserver(array($this->objActivityStreamer, 'postmade'));
                $this->eventsEnabled = TRUE;
            } else {
                $this->eventsEnabled = FALSE;
            }

            //Check if contentblocks is installed
            $this->cbExists = $this->objModuleCatalogue->checkIfRegistered("contentblocks");
            if ($this->cbExists) {
                $this->objBlocksContent = $this->getObject('dbcontentblocks', 'contentblocks');
                $this->objTxtBlockBase = $this->getObject("contentblockbase", "contentblocks");
            }

            $this->dbSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
            $disableActivityStreamer = $this->dbSysConfig->getValue('DISABLE_ACTIVITYSTREAMER', 'context');
            if ($disableActivityStreamer == 'TRUE' || $disableActivityStreamer == 'true') {
                $this->eventsEnabled = FALSE;
            }
        } catch (customException $e) {
            customException::cleanUp();

            //Load Module Catalogue Class
            //$this->objModuleCatalogue = $this->getObject('modules', 'modulecatalogue');
        }
    }

    /**
     * Method to turn off login requirement for certain actions
     */
    public function requiresLogin($action) {
        $requiresLogin = array('controlpanel', 'manageplugins', 'updateplugins', 'renderblock', 'addblock', 'removeblock', 'moveblock', 'updatesettings', 'updatecontext', 'viewuseractivitybyid', 'showuseractivitybymodule', 'selectuseractivitybymodulesdates', 'selectcontextactivitydates', 'selecttoolsactivitydates', 'showcontextactivity', 'showtoolsactivity', 'joincontextrequirelogin', 'launchcourseactivity', 'entercourseactivity');
        if (in_array($action, $requiresLogin)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to override isValid to enable administrators to perform certain action
     *
     * @param $action Action to be taken
     * @return boolean
     */
    public function isValid($action, $default = true) {
        if (in_array($action, array('launchcourseactivity', 'entercourseactivity'), true)) {
            return $this->objUser->isLoggedIn();
        }
        if ($this->objUser->isAdmin() || $this->objContextGroups->isContextLecturer()) {
            return TRUE;
        } else {
            return FALSE;
        }
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
        $this->setLayoutTemplate('contextlayout_tpl.php');
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
     */
    protected function getMethod(& $action) {
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
    protected function validAction($action) {
        if (method_exists($this, '__' . $action)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to show the context home page
     *
     */
    protected function __home() {
        if ($this->contextCode == 'root') {
            return $this->nextAction('catalogue');
        }

        $this->_preventRootAccess();

        $this->setLayoutTemplate(NULL);

        $leftBlocks = $this->objContextBlocks->getContextBlocks($this->contextCode, 'left');
        $this->setVarByRef('leftBlocksStr', $leftBlocks);

        $rightBlocks = $this->objContextBlocks->getContextBlocks($this->contextCode, 'right');
        $this->setVarByRef('rightBlocksStr', $rightBlocks);

        $middleBlocks = $this->objContextBlocks->getContextBlocks($this->contextCode, 'middle');
        $this->setVarByRef('middleBlocksStr', $middleBlocks);

        $allContextBlocks = $this->objContextBlocks->getContextBlocksArray($this->contextCode);
        $this->setVarByRef('allContextBlocks', $allContextBlocks);

        $smallDynamicBlocks = $this->objDynamicBlocks->getSmallContextBlocks($this->contextCode);
        $this->setVarByRef('smallDynamicBlocks', $smallDynamicBlocks);

        $wideDynamicBlocks = $this->objDynamicBlocks->getWideContextBlocks($this->contextCode);
        $this->setVarByRef('wideDynamicBlocks', $wideDynamicBlocks);

        $objBlocks = $this->getObject('dbmoduleblocks', 'modulecatalogue');
        $smallBlocks = $objBlocks->getBlocks('normal', 'context|site');
        $this->setVarByRef('smallBlocks', $smallBlocks);

        $wideBlocks = $objBlocks->getBlocks('wide', 'context|site');
        $this->setVarByRef('wideBlocks', $wideBlocks);

        $contentSmallBlocks = "";
        $contentWideBlocks = "";
        if ($this->cbExists) {
            $contentSmallBlocks = $this->objBlocksContent->getBlocksArr('content_text');
            $this->setVarByRef('contentSmallBlocks', $contentSmallBlocks);

            $contentWideBlocks = $this->objBlocksContent->getBlocksArr('content_widetext');
            $this->setVarByRef('contentWideBlocks', $contentWideBlocks);
        }
        return 'context_home_tpl.php';
    }

    /**
     * Method to show a list of contexts user can join
     */
    protected function __join() {
        if (trim((string) $this->getParam('error', '')) === ''
            && trim((string) $this->getParam('contextcode', '')) === '') {
            return $this->nextAction('catalogue');
        }
        $this->setLayoutTemplate(NULL);
        return 'needtojoin_tpl.php';
    }

    /**
     * Display the published course catalogue.
     *
     * @return string Catalogue template filename
     * @access protected
     */
    protected function __catalogue() {
        $this->setLayoutTemplate(NULL);
        $access=strtolower(trim((string)$this->getParam('access','')));
        if(!in_array($access,array('public','free','tier_1','tier_2','private'),true))$access='';
        $this->setVar(
            'catalogueTitle',
            $this->objLanguage->code2Txt(
                'mod_context_coursecatalogue',
                'context',
                NULL,
                'Course catalogue'
            )
        );
        $this->setVar(
            'catalogueContent',
            $access===''?$this->getObject('coursecatalogue', 'context')->renderCatalogue(60):$this->getObject('coursecatalogue', 'context')->renderCatalogueByPolicy($access,60)
        );
        return 'catalogue_tpl.php';
    }

    /**
     * Display the temporary application-information page for a private course.
     *
     * @return string Application-information template filename
     * @access protected
     */
    protected function __apply() {
        $contextCode = trim((string) $this->getParam('contextcode', ''));
        $context = $contextCode === ''
            ? false
            : $this->objContext->getContext($contextCode);
        if (!is_array($context)
            || strtolower((string) $context['access']) !== 'private'
            || strtolower((string) $context['status']) === 'unpublished') {
            return $this->nextAction('catalogue', array(), 'context');
        }

        $this->setLayoutTemplate(NULL);
        $this->setVar('applicationCourseTitle', $context['title']);
        $this->setVar(
            'applicationPageTitle',
            $this->objLanguage->code2Txt(
                'mod_context_applyforcourse',
                'context',
                NULL,
                'Apply for course'
            )
        );
        $this->setVar(
            'applicationMessage',
            $this->objLanguage->code2Txt(
                'mod_context_applicationscomingsoon',
                'context',
                NULL,
                'Online course applications will be available soon.'
            )
        );
        $this->setVar(
            'applicationBackLabel',
            $this->objLanguage->code2Txt(
                'mod_context_backtocatalogue',
                'context',
                NULL,
                'Back to course catalogue'
            )
        );
        return 'apply_tpl.php';
    }

    /**
     * Method to join a context requiring a login
     */
    protected function __joincontextrequirelogin() {
        return $this->__joincontext();
    }

    /**
     * Method to join a context
     */
    protected function __joincontext() {
        $contextCode = $this->getParam('contextcode');

        if ($contextCode == '') {
            return $this->nextAction('join', array('error' => 'nocontext'));
        } else {
            if ($this->objContext->joinContext($contextCode)) {
                //add to activity log

                if ($this->eventsEnabled) {
                    $message = $this->objUser->fullname() . ' ' .
                            $this->objLanguage->languageText('mod_context_hasentered', 'context') .
                            ' ' . $this->contextCode;
                    $this->eventDispatcher->post($this->objActivityStreamer, "context", array('title' => $message,
                        'link' => $this->uri(array()),
                        'contextcode' => $this->objContext->getContextCode(),
                        'author' => $this->objUser->fullname(),
                        'description' => $message));
                }
                $contextRedirectURI = $this->getParam('contextredirecturi', NULL);
                if ((!is_null($contextRedirectURI)) && (strlen($contextRedirectURI) > 0)) {
                    $contextRedirectURI_ = urldecode($contextRedirectURI);
                    header('Location: ' . $contextRedirectURI_);
                    return NULL;
                }
                //--
                $contextModule = $this->getParam('contextmodule'); //--
                if ($contextModule != '') {
                    $contextAction = $this->getParam('contextaction');
                    return $this->nextAction($contextAction, array('id' => $this->getParam('contextdata')), $contextModule);
                } //--
                return $this->nextAction('home');
            } else {
                return $this->nextAction('join', $this->joinFailure($contextCode));
            }
        }
    }

    /**
     * Resolve a deep link without allowing it to inherit root or stale scope.
     *
     * @return string|null Recovery template or destination dispatch.
     */
    protected function __launchcourseactivity()
    {
        $target = $this->courseLaunchRequest();
        if (!$this->mayLaunchCourseTarget($target)) {
            return $this->courseLaunchDenied($target);
        }
        if ((string) $this->contextCode === (string) $target['coursecode']) {
            return $this->dispatchCourseTarget($target);
        }
        $details = $this->objContext->getContextDetails($target['coursecode']);
        $this->setVar('courseLaunchTarget', $target);
        $this->setVar('courseLaunchTitle', is_array($details) ? (string) ($details['title'] ?? '') : '');
        $this->setVar('courseLaunchCsrf', $this->csrf->issue(self::COURSE_LAUNCH_CSRF));
        $this->setLayoutTemplate(NULL);
        return 'course_activity_launch_tpl.php';
    }

    /**
     * Confirm course entry using POST and a one-time CSRF token.
     *
     * @return string|null Denial template or destination dispatch.
     */
    protected function __entercourseactivity()
    {
        $target = $this->courseLaunchRequest();
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST'
            || !$this->csrf->consume(self::COURSE_LAUNCH_CSRF, (string) $this->getParam('csrf_token', ''))
            || !$this->mayLaunchCourseTarget($target)) {
            return $this->courseLaunchDenied($target);
        }
        if (!$this->objContext->joinContext($target['coursecode'])) {
            return $this->courseLaunchDenied($target);
        }
        return $this->dispatchCourseTarget($target);
    }

    /** Read and validate the shared course-launch request fields. */
    private function courseLaunchRequest()
    {
        return $this->courseLauncher->request(
            $this->getParam('coursecode', ''),
            $this->getParam('targetmodule', ''),
            $this->getParam('targetaction', ''),
            $this->getParam('targetparams', '')
        );
    }

    /** Require genuine course membership and a valid internal destination. */
    private function mayLaunchCourseTarget(array $target)
    {
        return $target['coursecode'] !== '' && $target['module'] !== ''
            && $this->objUserContext->isContextMember($this->objUser->userId(), $target['coursecode'])
            && $this->objContextModules->isVisible($target['module'], $target['coursecode']);
    }

    /** Dispatch only after course scope has been proven active. */
    private function dispatchCourseTarget(array $target)
    {
        return $this->nextAction($target['action'], $target['params'], $target['module']);
    }

    /** Show a safe recovery rather than executing a destination out of scope. */
    private function courseLaunchDenied(array $target)
    {
        $this->setVar('courseLaunchTarget', $target);
        $this->setLayoutTemplate(NULL);
        return 'course_activity_denied_tpl.php';
    }

    /**
     * Method to join a context
     */
    protected function __gotomodule() {
        $contextCode = $this->getParam('contextcode');
        $module = $this->getParam('moduleid', 'context');

        if ($contextCode == '') {
            return $this->nextAction('join', array('error' => 'nocontext'));
        } else {
            if ($this->objContext->joinContext($contextCode)) {
                return $this->nextAction(NULL, NULL, $module);
            } else {
                return $this->nextAction('join', $this->joinFailure($contextCode));
            }
        }
    }

    private function joinFailure($contextCode)
    {
        $params = array('error' => 'unabletoenter', 'contextcode' => (string) $contextCode);
        $details = $this->objContext->getContextDetails($contextCode);
        $policy = is_array($details) && isset($details['access_policy'])
            ? strtolower(trim((string) $details['access_policy'])) : '';
        if (in_array($policy, array('free', 'tier_1', 'tier_2', 'private'), true)) {
            $params['error'] = 'accessrequired';
            $params['admissionpolicy'] = $policy;
            $params['privateadmissionmode'] = is_array($details)
                ? (string) ($details['private_admission_mode'] ?? '') : '';
            if ($policy === 'private'
                && $params['privateadmissionmode'] === 'automatic_payment') {
                try {
                    $product = $this->getObject('paymentcatalogservice', 'payment-service')
                        ->privateCourseProduct((string) $contextCode);
                    if (is_array($product) && is_array($product['current_price'] ?? NULL)) {
                        $params['purchaseproduct'] = (string) $product['code'];
                        $params['purchaseamount'] = (string) $product['current_price']['amount_minor'];
                        $params['purchasecurrency'] = (string) $product['current_price']['currency'];
                    }
                } catch (Throwable $failure) {
                    // Admission still fails closed when optional payment support is absent.
                }
            }
        }
        return $params;
    }

    /**
     * Method to prevent access to certain portions without being logged into a context
     */
    private function _preventRootAccess() {
        if ($this->contextCode == 'root' || $this->contextCode == '') {
            return $this->nextAction('error', array('error' => 'cantaccessrootcontrolpanel'));
        }
    }

    /**
     * Method to show the context control panel
     */
    protected function __controlpanel() {
        $this->_preventRootAccess();

        $this->setLayoutTemplate('contextlayout_tpl.php');

        return 'controlpanel_tpl.php';
    }

    /**
     * Method to show the form for users to add/remove context plugins
     */
    protected function __manageplugins() {
        $this->_preventRootAccess();

        $objContextModules = $this->getObject('dbcontextmodules');
        $objModules = $this->getObject('modules', 'modulecatalogue');

        $contextModules = $objContextModules->getContextModules($this->contextCode);
        $plugins = $objModules->getListContextPlugins();

        $this->setVarByRef('contextModules', $contextModules);
        $this->setVarByRef('plugins', $plugins);

        return 'manageplugins_tpl.php';
    }

    /**
     * Method to update the list of context plugins
     */
    protected function __updateplugins() {
        $this->_preventRootAccess();

        $plugins = $this->getParam('plugins');

        $objContextModules = $this->getObject('dbcontextmodules');
        $objContextModules->deleteModulesForContext($this->contextCode);

        if (is_array($plugins) && (is_countable($plugins) ? count($plugins) : 0) > 0) {
            foreach ($plugins as $plugin) {
                $objContextModules->addModule($this->contextCode, $plugin);
            }
        }

        return $this->nextAction('controlpanel', array('message' => 'pluginsupdated'));
    }

    /**
     * Method to display error messages
     */
    protected function __error() {
        return $this->nextAction(NULL, NULL, '_default');
    }

    /**
     * Method to render a block
     */
    protected function __renderblock() {
        $blockId = $this->getParam('blockid');
        $side = $this->getParam('side');

        $block = explode('|', $blockId);

        $blockId = $side . '___' . str_replace('|', '___', $blockId);

        if ($block [0] == 'block') {
            $objBlocks = $this->getObject('blocks', 'blocks');
            echo '<div id="' . $blockId . '" class="block highlightblock">' . $objBlocks->showBlock($block [1], $block [2], NULL, 20, TRUE, FALSE) . '</div>';
        }
        if ($block [0] == 'dynamicblock') {
            echo '<div id="' . $blockId . '" class="block highlightblock">' . $this->objDynamicBlocks->showBlock($block [1]) . '</div>';
        } else {
            echo '';
        }
    }

    /**
     * Method to add a block
     */
    protected function __addblock() {
        $blockId = $this->getParam('blockid');
        $side = $this->getParam('side');

        $block = explode('|', $blockId);

        if ($block [0] == 'block' || $block [0] == 'dynamicblock') {
            // Add Block
            $result = $this->objContextBlocks->addBlock($blockId, $side, $this->contextCode, $block [2]);

            if ($result == FALSE) {
                echo '';
            } else {
                echo $result;
            }
        } else {
            echo '';
        }
    }

    /**
     * Method to remove a context block
     */
    protected function __removeblock() {
        $blockId = $this->getParam('blockid');

        $result = $this->objContextBlocks->removeBlock($blockId);

        if ($result) {
            echo 'ok';
        } else {
            echo 'notok';
        }
    }

    /**
     * Method to move a context block
     */
    protected function __moveblock() {
        $blockId = $this->getParam('blockid');
        $direction = $this->getParam('direction');

        if ($direction == 'up') {
            $result = $this->objContextBlocks->moveBlockUp($blockId, $this->contextCode);
        } else {
            $result = $this->objContextBlocks->moveBlockDown($blockId, $this->contextCode);
        }

        if ($result) {
            echo 'ok';
        } else {
            echo 'notok';
        }
    }

    /**
     * Method to show a form to update context settings
     */
    protected function __updatesettings() {
        $context = $this->objContext->getContextDetails($this->contextCode);
        $objContextForms = $this->getObject('contextforms');
        $form = $objContextForms->editContextForm($context);
        $this->setVarByRef('form', $form);
        return 'editcontextsettings_tpl.php';
    }

    /**
     * Method to Update a Context Settings
     */
    protected function __updatecontext() {
        $contextCode = $this->getParam('contextcode');
        $title = $this->getParam('title');
        $status = $this->getParam('status');
        $access = $this->getParam('access');
        $accessPolicy = $this->objContext->normaliseAccessPolicy(
            $this->getParam('access_policy', ''),
            TRUE
        );
        $privateAdmissionMode = $this->objContext->normalisePrivateAdmissionMode(
            $this->getParam('private_admission_mode', ''),
            TRUE
        );
        if ($accessPolicy !== 'private') {
            $privateAdmissionMode = NULL;
        }
        $about = $this->getParam('about');
        $image = $this->getParam('imageselect');
        $deliveryFormat = $this->getParam('delivery_format', '');
        $learningDesign = $this->objContext->validateLearningDesign(
            $deliveryFormat,
            $this->getParam('navigation_mode', '')
        );
        $showComment = (string) $this->getParam('showcomment', '0');
        $canvas = (string) $this->getParam('canvas', 'None');


        //$emailalert =
        //$alerts = '';
        //if ($emailalert == 'on') {
        //$alerts.='e';
        //}
        $alerts = $this->getParam('emailalertopt') == 'on' ? '1' : '0';
        if ($contextCode == $this->contextCode && $title != ''
            && $accessPolicy !== FALSE && $privateAdmissionMode !== FALSE
            && $learningDesign !== FALSE
            && in_array($showComment, array('0', '1'), TRUE)) {
            $result = $this->objContext->updateContext(
                    $contextCode, $title, $status, $access, $about, FALSE, $showComment,
                    $alerts, FALSE, $canvas, $learningDesign['delivery_format'],
                    $learningDesign['navigation_mode'], $accessPolicy,
                    $privateAdmissionMode);

            if ($image != '') {
                $objContextImage = $this->getObject('contextimage', 'context');
                $objContextImage->setContextImage($contextCode, $image);
            }

            return $this->nextAction('controlpanel');
        } else {
            return $this->nextAction('updatesettings', array('error' => 'inccompletefields'));
        }
    }

    /**
     * Add Context Search
     */
    protected function __search() {
        $search = $this->getParam('search');

        $objSearchResults = $this->getObject('searchresults', 'search');
        $searchResults = $objSearchResults->displaySearchResults($search, NULL, $this->contextCode);

        $this->setVarByRef('searchResults', $searchResults);
        $this->setVarByRef('searchText', $search);

        return 'searchresults_tpl.php';
    }

    /**
     * Method to display a context created message
     *
     * @access protected
     */
    protected function __contextcreatedmessage() {
        /*
         * CHISIMBA DESIGN-SYSTEM SUCCESS NOTIFICATION
         *
         * The language system continues to own the translated message and
         * placeholder substitution. The controller supplies semantic markup;
         * the active skin owns visual presentation.
         */
        $createdMessage = $this->objLanguage->code2Txt(
            'mod_context_congratscontextcreated',
            'context',
            NULL,
            'Congratulations! Your [-context-] has been created'
        );

        echo '<div class="chisimba-notification '
          . 'chisimba-notification--success" '
          . 'role="status" aria-live="polite">'
          . '<div class="chisimba-notification__icon" '
          . 'aria-hidden="true">&#10003;</div>'
          . '<div class="chisimba-notification__content">'
          . '<p class="chisimba-notification__message">'
          . htmlspecialchars($createdMessage . '.', ENT_QUOTES, 'UTF-8')
          . '</p>'
          . '</div>'
          . '</div>'
          . '<div class="chisimba-notification-followup">'
          . '<p>'
          . $this->objLanguage->code2Txt(
                'mod_context_contextcreatedmessage1',
                'context',
                NULL,
                'This is the home page of your [-context-] You can modify the contents of the page, by clicking "Turn Editing On"'
            )
          . '. '
          . $this->objLanguage->languageText(
                'mod_context_contextcreatedmessage2',
                'context',
                'This will allow you to add different types of content blocks to this page'
            )
          . '.</p>'
          . '<p>'
          . $this->objLanguage->code2Txt(
                'mod_context_contextcreatedmessage3',
                'context',
                NULL,
                'To add [-readonlys-] to your [-context-], or to add/remove [-context-] plugins, go to the [-context-] control panel'
            )
          . '.</p>'
          . '</div>';
    }

    /**
     * Method to get contexts via ajax
     */
    protected function __ajaxgetcontexts() {
        $letter = $this->getParam('letter');

        $contexts = $this->objContext->getContextStartingWith($letter);

        if ((is_countable($contexts) ? count($contexts) : 0) == 0) {

        } else {
            $objDisplayContext = $this->getObject('displaycontext', 'context');

            foreach ($contexts as $context) {
                echo $objDisplayContext->formatContextDisplayBlock($context, FALSE, FALSE) . '<br />';
            }
        }
    }

    /**
     * Method to get user contexts via ajax
     */
    protected function __ajaxgetusercontexts() {
        $objUserContext = $this->getObject('usercontext', 'context');
        $contexts = $objUserContext->getUserContext($this->objUser->userId());

        $con = array();
        if ((is_countable($contexts) ? count($contexts) : 0) > 0) {
            foreach ($contexts as $context) {
                $con[] = $this->objContext->getContext($context);
            }
        }
        $contexts = $con;
        if ((is_countable($contexts) ? count($contexts) : 0) == 0) {

        } else {
            $objDisplayContext = $this->getObject('displaycontext', 'context');

            foreach ($contexts as $context) {
                echo $objDisplayContext->formatContextDisplayBlock($context, FALSE, FALSE) . '<br />';
            }
        }
    }

    /**
     * Added by Paul Mungai
     * Method to list all user contexts
     * @access protected
     */
    protected function __jsonusercontexts() {
        $ctstart = $this->getParam('start');
        if (empty($ctstart)) {
            $ctstart = 0;
        }
        $ctlimit = $this->getParam('limit');
        if (empty($ctlimit)) {
            $ctlimit = 50;
        }
        $objUserContext = $this->getObject('usercontext', 'context');
        $objDisplayContext = $this->getObject('displaycontext', 'context');
        $userContexts = $objUserContext->jsonUserCourses($this->objUser->userId(), $ctstart, $ctlimit);
        if ((is_countable($userContexts) ? count($userContexts) : 0) > 0) {
            echo $objDisplayContext->jsonContextOutput($userContexts);
            exit(0);
        }
    }

    /**
     * Method to get all contexts via ajax
     */
    protected function __ajaxgetallcontexts() {
        $objUtils = $this->getObject('utilities');
        echo $objUtils->searchBlock();
    }

    /**
     * Method to leave a context
     *
     * @access protected
     */
    protected function __searchcontext() {
        $objUtils = $this->getObject('utilities');
        $items = $objUtils->getContextList();

        $q = $this->getParam('q');
        foreach ($items as $key => $value) {
            if (strpos(strtolower($key), $q) !== false) {
                echo "$key|$value\n";
            }
        }
        exit(0);
    }

    /**
     * Method to leave a context
     *
     * @access protected
     */
    protected function __searchusers() {
        $objUtils = $this->getObject('utilities');
        $items = $objUtils->getUserList();

        $q = $this->getParam('q');
        foreach ($items as $key => $value) {
            if (strpos(strtolower($key), $q) !== false) {
                echo "$key|$value\n";
            }
        }
        exit(0);
    }

    /**
     * Method to leave a context
     *
     * @access protected
     */
    protected function __leavecontext() {
        $leaveDestination = $this->getObject('landingresolver', 'postlogin')
            ->leaveCourseModule($this->contextCode, '_default');
        if ($this->eventsEnabled) {
            $message = $this->objUser->fullname() . ' ' .
                    $this->objLanguage->languageText('mod_context_hasleft', 'context') .
                    ' ' . $this->contextCode;
            $this->eventDispatcher->post($this->objActivityStreamer, "context", array('title' => $message,
                'link' => $this->uri(array()),
                'contextcode' => $this->objContext->getContextCode(),
                'author' => $this->objUser->fullname(),
                'description' => $message));
        }
        $this->objContext->leaveContext();
        if ($this->objUser->isLoggedIn()) {
            return $this->nextAction(NULL, NULL, $leaveDestination);
        } else {
            // Workaround for bug in the engine class - it should really be unfucked in the engine class
            $objConfig = $this->getObject('altconfig', 'config');
            $goToPlace = $objConfig->getPrelogin('KEWL_PRELOGIN_MODULE');
            return $this->nextAction(NULL, NULL, $goToPlace);
        }
    }

    /**
     * Method to format a context
     *
     * @access protected
     */
    protected function __ajaxgetselectedcontext() {
        $objUtils = $this->getObject('utilities');
        echo $objUtils->formatSelectedContext($this->getParam('contextcode'));
        exit(0);
    }

    /**
     * Method to format the user context list
     *
     * @access protected
     */
    protected function __ajaxgetselectedusercontext() {
        $objUtils = $this->getObject('utilities');
        echo $objUtils->formatUserContext($this->getParam('username'));
        exit(0);
    }

    /**
     * Method to list all he context
     *
     * @access protected
     */
    protected function __ajaxlistcontext() {
        $objUtils = $this->getObject('utilities');
        echo $objUtils->listContexts();
        exit(0);
    }

    /**
     * Method to list all the context
     *
     * @access protected
     */
    protected function __jsonlistcontext() {
        $objUtils = $this->getObject('utilities');
        echo $objUtils->jsonListContext($this->getParam('start'), $this->getParam('limit'));
        exit(0);
    }

    /**
     * Method to list all the context
     *
     * @access protected
     */
    protected function __jsonlistallcontext() {
        $objUtils = $this->getObject('utilities');
        echo $objUtils->jsonListAllContext();
        exit(0);
    }

    protected function __jsongetcontexts() {
        $objUtils = $this->getObject('utilities');
        echo $objUtils->getContext($this->getParam('start'), $this->getParam('limit'));
        exit(0);
    }

    public function __transfercontextusers() {
        $start = '0';
        $limit = '100';
        $this->objGroups = $this->getObject('managegroups', 'contextgroups');
        $data = $this->objGroups->usercontextcodeslimited($this->objUser->userId(), $start, $limit);
        $this->setVarByRef('data', $data);
        return "transfercontextusers_tpl.php";
    }

    public function __savetransfercontextusers() {
        $context1 = $this->getParam('context1');
        $context2 = $this->getParam('context2');
        if ($context1 == $context2) {
            $message = ucwords($this->objLanguage->code2Txt('mod_context_transferfail', 'context', null, 'Transfer failed. You selected same [-context-] twice.'));
            $this->setVarByRef("message", $message);
            return "confirmusertransfer_tpl.php";
        }
        $objUtils = $this->getObject('utilities');
        $objUtils->copyStudentsFromOneCourseToNext($context1, $context2);
        $message = ucwords($this->objLanguage->code2Txt('mod_context_complete', 'context', null, 'Transfer complete.'));
        $this->setVarByRef("message", $message);
        return "confirmusertransfer_tpl.php";
    }

    /**
     * for displaying user activity
     * @return <type>
     */
    function __showuseractivitybymodule() {
        $startDate = $this->getParam('startdate');
        $endDate = $this->getParam('enddate');
        $studentsonly = $this->getParam('studentsonly');
        $module = $this->getParam('moduleid');
        $objUserActivity = $this->getObject('dbuseractivity');
        $contextcode = $this->getParam("contextcode");
        if ($contextcode == null) {
            $contextcode = $this->contextCode;
        }

        $objGroups = $this->getObject('groupservice', 'groupadmin');
        $contextGroupId = $objGroups->groupIdForName($contextcode . '^Students');
        $usersInContext = $objGroups->getMembers($contextGroupId);

        $data = $objUserActivity->getUserActivityByModule($startDate, $endDate, $module, $studentsonly, $usersInContext, $contextcode);
        $this->setVarByRef("data", $data);
        $this->setVarByRef("startdate", $startDate);
        $this->setVarByRef("enddate", $endDate);
        $this->setVarbyRef("modulename", $module);
        return "useractivitybymodule_tpl.php";
    }

    /**
     * for displaying user activity
     * @return <type>
     */
    function __viewUserActivityById() {
        $startDate = $this->getParam('startdate');
        $endDate = $this->getParam('enddate');
        $module = $this->getParam('moduleid');
        $userid = $this->getParam('userid');
        $objUserActivity = $this->getObject('dbuseractivity');
        $data = $objUserActivity->getUserActivityById($startDate, $endDate, $module, $userid, $this->contextCode);
        $this->setVarByRef("data", $data);
        $this->setVarByRef("startdate", $startDate);
        $this->setVarByRef("enddate", $endDate);
        $this->setVarbyRef("modulename", $module);
        $this->setVarbyRef("userid", $userid);
        return "useractivitybyid_tpl.php";
    }

    /**
     *  returns a date template
     * @return <type>
     */
    function __selectUserActivityByModuleDates() {
        $action = "showuseractivitybymodule";
        $title = $this->objLanguage->languageText('mod_context_useractivity', 'context', 'User activity');
        $this->setVarByRef("action", $action);
        $this->setVarByRef("title", $title);
        return "selectdates_tpl.php";
    }

    function __selecttoolsactivitydates() {
        $action = "showtoolsactivity";
        $title = $this->objLanguage->languageText('mod_context_toolsactivity', 'context', 'Tools activity');
        $this->setVarByRef("action", $action);
        $this->setVarByRef("title", $title);
        return "selectdates_tpl.php";
    }

    function __showtoolsactivity() {
        $startDate = $this->getParam('startdate');
        $endDate = $this->getParam('enddate');

        $objModules = $this->getObject('modules', 'modulecatalogue');
        $plugins = $objModules->getListContextPlugins();

        $contextcode = $this->getParam("contextcode");
        if ($contextcode == null) {
            $contextcode = $this->contextCode;
        }
        $context = $this->objContext->getContext($contextcode);
        $objUserActivity = $this->getObject('dbuseractivity');
        $data = $objUserActivity->getToolsActivity($startDate, $endDate, $contextcode, $plugins);
        $this->setVarByRef("data", $data);
        $this->setVarByRef("startdate", $startDate);
        $this->setVarByRef("enddate", $endDate);
        $this->setVarByRef("coursetitle", $context['title']);
        $this->setVarByRef("contextcode", $context['contextcode']);

        return "toolsactivity_tpl.php";
    }

    function __selectcontextsactivitydates() {
        $action = "showcontextactivity";
        $title = $this->objLanguage->code2Txt('mod_context_allcoursesacitivity', 'context', NULL, 'All [-contexts-] activity');
        $this->setVarByRef("action", $action);
        $this->setVarByRef("title", $title);
        return "selectdates_tpl.php";
    }

    function __showcontextactivity() {
        $startDate = $this->getParam('startdate');
        $endDate = $this->getParam('enddate');
        $contexts = $this->objContext->getListOfContext();

        $objUserActivity = $this->getObject('dbuseractivity');
        $data = $objUserActivity->getContextsActivity($startDate, $endDate, $contexts);
        $this->setVarByRef("data", $data);
        $this->setVarByRef("startdate", $startDate);
        $this->setVarByRef("enddate", $endDate);
        return "contextsactivity_tpl.php";
    }

}

?>
