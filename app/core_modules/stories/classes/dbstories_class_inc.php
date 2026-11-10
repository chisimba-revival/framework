<?php
/* -------------------- stories class ----------------*/

/**
* Class for the stories table in the database
*/
class dbStories extends dbTable
{
    public $objUser;
    public $objLanguage;

    /**
    * Constructor method to define the table
    */
    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback') {
        parent::init('tbl_stories');
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
    }

    /**
    * Save method for groups admin
    * @param string $mode: edit if coming from edit, add if coming from add
    */
    function saveStories($mode) {
        if (!in_array($mode, ['edit','add','translate'], true)) throw new InvalidArgumentException('Invalid story mode');
        foreach (['category','language','title','mainText','isActive','isSticky','expirationDate','id','parentId','abstract'] as $field) {
            if (isset($_POST[$field]) && !is_string($_POST[$field])) throw new InvalidArgumentException('Invalid story field');
        }
        if (!in_array($this->getParam('isActive'), ['0','1'], true)
            || !in_array($this->getParam('isSticky'), ['0','1'], true)
            || trim((string)$this->getParam('title', '')) === ''
            || !preg_match('/^[a-z]{2}$/iD', (string)$this->getParam('language', '')))
            throw new InvalidArgumentException('Invalid story values');
        if ($mode === 'edit' && !$this->getRow('id', $this->getParam('id', '')))
            throw new InvalidArgumentException('Unknown story');
        //The current user
        $userId = $this->objUser->userId();
        //Get data from form
        $category = trim((string)$this->getParam('category', ''));
        $isActive = trim((string)$this->getParam('isActive', ''));
        $parentId = TRIM($this->getParam('parentId', ''));
        //Set Id to default if missing
        if ($parentId=='') {
            $parentId='base';
        }
        $language = trim((string)$this->getParam('language', ''));
        $title = trim((string)$this->getParam('title', ''));
        $abstractParam = $this->getParam('abstract', NULL);
        $abstract = ($abstractParam === NULL) ? NULL : TRIM($abstractParam);
        $mainText = trim((string)$this->getParam('mainText', ''));
        $isSticky = trim((string)$this->getParam('isSticky', ''));
        $forceOrder = TRIM($this->getParam('forceOrder', ''));
        $expirationDate = trim((string)$this->getParam('expirationDate', ''));
        // if edit use update
        if ($mode=="edit") {
            $rsArray=array(
                'category'=>$category,
                'parentId'=>$parentId,
                'language'=>$language,
                'isActive'=>$isActive,
                'title'=>$title,
                'mainText'=>$mainText,
                'isSticky'=>$isSticky,
                'modifierId'=>$userId,
                'dateModified'=>date('Y-m-d H:i:s'),
                'expirationDate'=>$expirationDate);
            // Missing input must not erase stored content. A submitted empty
            // value remains deliberate and therefore still clears it.
            if ($abstract !== NULL) {
                $rsArray['abstract'] = $abstract;
            }
            $id = TRIM($this->getParam('id', ''));
            return $this->update("id", $id, $rsArray);
        } elseif ($mode=="add" || $mode=="translate") {
            return $this->insert(array(
              'category'=>$category,
              'parentId'=>$parentId,
              'language'=>$language,
              'isActive'=>$isActive,
              'title'=>$title,
              'abstract'=>$abstract,
              'mainText'=>$mainText,
              'isSticky'=>$isSticky,
              'creatorId'=>$userId,
              'dateCreated'=>date('Y-m-d H:i:s'),
              'expirationDate'=>$expirationDate));
        }
    }


    /**
    * Method to ask for confirmation to delete a group
    * @param string $keyvalue The group to be deleted
    */
    function deleteStoriesConfirm($key, $keyvalue) {
        global  $objButtons, $objLanguage, $objSkin, $objUser;
        include ('templates/confirmdelete_tpl.php');          # includes the form template
    }


    /**
    * Method to delete a group
    * @param string $keyvalue The group to be deleted
    */
    function deleteStories($key, $keyvalue) {
        $this->delete($key, $keyvalue);
    }

    /**
    * Method to retrieve the data for edit and prepare the vars for
    * the edit template.
    *
    * @param string $mode The mode should be edit or add
    */
    function getForEdit()
    {
        $order = $this->getParam("order", NULL);
        // retrieve the group ID from the querystring
        $keyvalue=$this->getParam("id", NULL);
        if (!$keyvalue) {
          die($this->objLanguage->languageText("modules_badkey",'stories').": ".$keyvalue);
        }
        // Get the data for edit
        $key="id";
        return $this->getRow($key, $keyvalue);
    }

    /**
    *
    * Method to return an array of all stories without the abstract and text
    * @param string $filter.
    *
    */
    function fetchStories($filter = NULL)
    {
        $sql="SELECT id, category, creatorId, isActive,
           parentId, language, title, dateCreated,
           expirationDate, notificationDate, isSticky
           FROM tbl_stories ";

        if($filter){
            $sql .= "WHERE $filter";
        }

        $sql .= "ORDER BY isSticky DESC, dateCreated DESC";
        return $this->getArray($sql);
    }

}  #end of class
?>