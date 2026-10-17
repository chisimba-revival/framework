<?php
/* -------------------- stories class extends controller ----------------*/
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
/**
 * Module class to handle administration of KEWL.NextGen stories
 * Stories are text elements that can be placed in a KEWL page
 * by code or in sequence by location. It is meant for site-level
 * display of text, such as on the post login screen for example.
 *
 * @author Derek Keats
 *
 */
class stories extends controller
{
    /*
    Variables for creating the user, language object, etc
    */
    var $objUser;
    var $objButtons; #DEPRECATED
    var $objConfig;

    /**
     * Intialiser for the adminGroups object
     *
     * @param byref $ string $engine the engine object
     */
    function init()
    {
        $this->objButtons =  $this->getObject('navbuttons', 'navigation');
        $this->objUser =  $this->getObject('user', 'security');
        $this->objLanguage =  $this->getObject('language', 'language');
        // Create the configuration object
        $this->objConfig =  $this->getObject('altconfig', 'config');
        // Create an instance of the database language class
        $this->objDbStories =  $this->getObject('dbstories');
        // Create an instance of the interface supporting class
        $this->objInterface =  $this->getObject('storyinterface');
        $this->objDate = $this->getObject('dateandtime','utilities');
        //Get the activity logger class
        $this->objLog=$this->newObject('logactivity', 'logger');
        //Log this module call
        $this->objLog->log();
    }
    /**
     * *The standard dispatch method for the module. The dispatch() method must
     * return the name of a page body template which will render the module
     * output (for more details see Modules and templating)
     */
    function dispatch($action)
    {
        if (in_array($action, [null, 'view', 'edit', 'add', 'translate', 'save', 'delete'], true)
            && !$this->objUser->lookupAdmin($this->objUser->userId())) {
            http_response_code(403);
            $this->setVar('str', '');
            return 'dump_tpl.php';
        }
        if (in_array($action, ['save', 'delete'], true)) {
            $csrf = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf'];
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
                || !is_string($_POST['csrf_token'] ?? null)
                || !$csrf->consume('stories_manage', $_POST['csrf_token'])) {
                http_response_code(403);
                if ($action === 'save' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                    $this->setVar('storyDraft', $_POST);
                    $this->setVar('mode', in_array($this->getParam('mode'), ['add','edit','translate'], true) ? $this->getParam('mode') : 'add');
                    return 'editform_tpl.php';
                }
                $this->setVar('str', '');
                return 'dump_tpl.php';
            }
        }
        // retrieve the mode from the querystring
        $mode = $this->getParam("mode", null);
        // retrieve the sort order from the querystring
        $order = $this->getParam("order", null);
        $ret ="";
        switch ($action) {
            case null:
            case "view":
                $filter = NULL;
                $ar = $this->objDbStories->fetchStories($filter);
                $this->setVarByRef('ar', $ar);
                return "main_tpl.php";
                break;
            case "edit":
                $editId = $this->getParam('id');
                if (!is_string($editId) || !$this->objDbStories->getRow('id', $editId)) {
                    http_response_code(404); $this->setVar('str', ''); return 'dump_tpl.php';
                }
                $this->setvar('mode', "edit");
                return 'editform_tpl.php';
                break;
            case "add":
                $this->setvar('mode', 'add');
                return 'editform_tpl.php';
                break;
            case "translate":
                $this->setvar('mode', 'translate');
                return 'editform_tpl.php';
                break;
            case "save":
                $mode = $this->getParam("mode", NULL);
                try {
                    if (!$this->objDbStories->saveStories($mode)) throw new RuntimeException('Story save failed');
                } catch (Throwable $error) {
                    http_response_code(422);
                    $this->setVar('storyDraft', $_POST);
                    $this->setVar('mode', in_array($mode, ['add','edit','translate'], true) ? $mode : 'add');
                    return 'editform_tpl.php';
                }
                $this->setForShow();
                //return "main_tpl.php";
                return $this->nextAction(null);
                break;
            case "delete":
                // retrieve the confirmation code from the querystring
                $confirm=$this->getParam("confirm", "no");
                if ($confirm=="yes") {
                    $this->deleteItem();
                    return $this->nextAction(NULL);
                }
                break;
            case "viewstory":
                $id = $this->getParam('id', NULL);
                $objStories =  $this->getObject('sitestories');
                $this->setVar('str', $objStories->fetchStory($id));
                return 'dump_tpl.php';
            case "viewtranslation":
                $id = $this->getParam('id', NULL);
                $postLanguage =  $this->getParam('language', NULL);
                $objStories =  $this->getObject('sitestories');
                $this->setVar('str', $objStories->fetchStory($id));
                return 'dump_tpl.php';
            case "readmore":
                return 'showstories_tpl.php';
            case "getfullstory":
            case "gettrunctstory":
                $objStories = $this->getObject('sitestories');
                $id = $this->getParam('id');
                if (!$objStories->readableStory($id, true)) {
                    http_response_code(404);
                    $this->setVar('str', '');
                } else $this->setVar('str', $objStories->fetchStory($id));
                $this->setLayoutTemplate(null);
                $this->setPageTemplate(null);
                return 'dump_tpl.php';
            case "getallstories":
                $objStories =  $this->getObject('sitestories');
                $limit = $this->getParam('limit', null);
                $ret = $objStories->createAllStories($limit);
                echo $ret;
                break;
            case "getlessstories":
                $objStories =  $this->getObject('sitestories');
                $limit = $this->getParam('limit', null);
                $ret = $objStories->fetchPreLoginCategory('prelogin', $limit);
                echo $ret;
                break;
            default:
                $this->setVar('str', "<h3>"
                  . $this->objLanguage->languageText("phrase_unrecognizedaction",'stories')
                  .": " . $action . "</h3>");
                return 'dump_tpl.php';
                break;
        }
    }

    /**
    * Method to prepare and set the vars for the output template
    */

    function setForShow()
    {
        $order = $this->getParam("order", null);
        // Define a variable to print the list
        $this->setvar('outStr', $this->listStories($order));
    }

    /**
    * Method to list all atories with descriptions
    */
    function listStories($order=NULL)
    {

        $sql="SELECT id, category, parentId, language, title, isActive,  expirationDate FROM tbl_stories ";
        $filter=NULL;
        if ($order) {
            $filter=" ORDER BY ".$order;
        }
        $rs=$this->objDbStories->getArray($sql.$filter);
        return $this->objInterface->displayData($rs, "stories", TRUE, "id");
    }



    /**
    * Method to prepare the data for the delete confirm templage
    */
    function prepForDelete()
    {
        $this->setvar('nobutton', $this->objButtons->button("confirm", "delete_no.png"));
        $this->setvar('yesbutton', $this->objButtons->button("confirm", "delete_yes.png"));
        $this->setvar('id',$this->getParam("id", NULL));
    }



        /**
    * Method to delete a group
    * @param string $keyvalue The group to be deleted
    */
    function deleteItem()
    {
        $id=$this->getParam("id", NULL);
        if (!$id) {
             die($this->objLanguage->languageText("modules_badkey",'stories').": ".$keyvalue);
        } else {
            $this->objDbStories->delete("id", $id);
        }
    }

    /**
    * Method to take a datetime string and reformat it as text.
    * @param string $date The date in datetime format.
    * @return string $ret The formatted date.
    */
    function formatDate($date)
    {
        $ret = substr($date,8,2);
        $ret .= ' '.$this->objDate->monthFull(substr($date,5,2));
        $ret .= ' '.substr($date,0,4);

        $time = substr($date,11,5);
        if(!empty($time) && $time!=0){
            $ret .= ' '.$time;
        }
        return $ret;
    }

    function requiresLogin($action)
    {
        $notrequiredAction = array('getfullstory', 'readmore', 'gettrunctstory', 'getallstories', 'getlessstories');
        if (in_array($action, $notrequiredAction)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}
?>
