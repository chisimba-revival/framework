<?php

/* -------------------- postlogin class extends controller ---------------- */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

// end security check
/**
 * Default class to handle what happens after the user logs in.
 * The post login module can be set by changing the constant
 * KEWL_POSTLOGIN_MODULE from 'postlogin' to the name of any other
 * module.
 *
 * @author Derek Keats, Tohir Solomons
 */
class postlogin extends controller {
    /** Dependencies and state initialised by this component. */
    public $cbExists;
    public $contextCode;
    public $dbSysConfig;
    public $objBlocks;
    public $objBlocksContent;
    public $objContextBlocks;
    public $objDynamicBlocks;
    public $objLanguage;
    public $objModule;


    /**
     * init method to instantiate the class
     */
    function init() {
        // Create an instance of the module object
        $this->objModule = $this->getObject('modules', 'modulecatalogue');
        //Check if contentblocks is installed
        $this->cbExists = $this->objModule->checkIfRegistered("contentblocks");
        if ($this->cbExists) {
            $this->objBlocksContent = $this->getObject('dbcontentblocks', 'contentblocks');
        }
        $this->objContextBlocks = $this->getObject('dbcontextblocks', 'context');

        $this->objDynamicBlocks = $this->getObject('dynamicblocks', 'blocks');
        $this->objBlocks = $this->getObject('blocks', 'blocks');

        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');

        $this->contextCode = 'root';
    }

    /**
     * Standard Dispatch Function for Controller
     *
     * @access public
     * @param string $action Action being run
     * @return string Filename of template to be displayed
     */
    public function dispatch($action) {

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
    protected function validAction(& $action) {
        if (method_exists($this, '__' . $action)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Dispatch method to return the template populated with
     * the output
     */
    protected function __home() {
        $leftBlocks = $this->objContextBlocks->getContextBlocks($this->contextCode, 'left');
        $this->setVarByRef('leftBlocksStr', $leftBlocks);

        $rightBlocks = $this->objContextBlocks->getContextBlocks($this->contextCode, 'right');
        $this->setVarByRef('rightBlocksStr', $rightBlocks);

        $middleBlocks = $this->objContextBlocks->getContextBlocks($this->contextCode, 'middle');
        $this->setVarByRef('middleBlocksStr', $middleBlocks);

        $smallDynamicBlocks = $this->objDynamicBlocks->getSmallSiteBlocks();
        $this->setVarByRef('smallDynamicBlocks', $smallDynamicBlocks);

        $wideDynamicBlocks = $this->objDynamicBlocks->getWideSiteBlocks();
        $this->setVarByRef('wideDynamicBlocks', $wideDynamicBlocks);

        $objBlocks = $this->getObject('dbmoduleblocks', 'modulecatalogue');
        $smallBlocks = $objBlocks->getBlocks('normal', 'site|postlogin');
        
        $this->setVarByRef('smallBlocks', $smallBlocks);

        $wideBlocks = $objBlocks->getBlocks('wide', 'site|postlogin');
        $this->setVarByRef('wideBlocks', $wideBlocks);
        //Add content blocks if any
        $contentSmallBlocks = "";
        $contentWideBlocks = "";
        if ($this->cbExists) {
            $contentSmallBlocks = $this->objBlocksContent->getBlocksArr('content_text');
            $this->setVarByRef('contentSmallBlocks', $contentSmallBlocks);

            $contentWideBlocks = $this->objBlocksContent->getBlocksArr('content_widetext');
            $this->setVarByRef('contentWideBlocks', $contentWideBlocks);
        }
        return 'main_tpl.php';
    }

    /** All site-home layout actions use the administrator-only shared editor. */
    protected function __renderblock() { $this->blockEditorResponse('renderblock'); }
    protected function __addblock() { $this->blockEditorResponse('addblock'); }
    protected function __removeblock() { $this->blockEditorResponse('removeblock'); }
    protected function __moveblock() { $this->blockEditorResponse('moveblock'); }

    private function blockEditorResponse($action) {
        $input=[];
        foreach (['contextcode','blockid','side','direction','revision','csrf_token'] as $key) $input[$key]=$this->getParam($key);
        $result=$this->getObject('postloginblockeditor')->handle($action,$input,$_SERVER['REQUEST_METHOD']??'');
        http_response_code($result['status']);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($result);
        exit;
    }
}
