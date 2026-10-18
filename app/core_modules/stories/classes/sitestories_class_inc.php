<?php
/* -------------------- stories class ----------------*/

/**
* Class for providing a service to other modules that want to
* display stories a story
*
* @author Derek Keats
*
*/
class sitestories extends dbTable {

    public $objUser;
    public $objLanguage;
    public $objDbStories;
    public $objH;
    public $objParse;
    public $objWashout;
    public $objIcon;

    /**
    *
    * Constructor method to define the table
    *
    */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        parent::init('tbl_stories');
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objDbStories =  $this->getObject('dbstories');
        $this->objH = $this->getObject('htmlheading', 'htmlelements');
        //Get the smiley parser
        $this->objParse = $this->getObject('parse4display', 'strings');
        $this->objWashout = $this->getObject('washout', 'utilities');
        $this->objIcon = $this->getObject('iconservice', 'ui');
    }

    /**
    *
    * Method to fetch a story by story ID
    *
    * @param string $id: The id of the story to return
    *
    */
    function fetchStory($id) {
        $ar = $this->readableStory($id);
        if (!$ar) return '';
        $creatorId = $ar['creatorid'];
        $isActive = stripslashes($ar['isactive']);
        $title = htmlspecialchars(stripslashes($ar['title']), ENT_QUOTES, 'UTF-8');
        $abstract = $this->objWashout->parseText(stripslashes($ar['abstract']));
        $mainText = $this->objWashout->parseText(
                stripslashes($ar['maintext'])
          );
        $dateCreated = stripslashes($ar['datecreated']);
        $expirationDate = stripslashes($ar['expirationdate']);
        $notificationDate = stripslashes($ar['notificationdate']);
        //Add the heading
        $this->objH->type=3;
        $this->objH->str=$title;
        $ret= "<div class=\"storytitle\">". $this->objH->show() . "</div>";
        //Add the abstract
        $ret.="<div class=\"abstract\"><p class=\"minute\">".$abstract."</p></div>";
        //Add the main text
        $ret.="<p>".$mainText;
        if ($this->objUser->isAdmin()) {
            $editArray = array('action' => 'edit',
                    'id' => $id);
            $objGetIcon = $this->newObject('geticon', 'htmlelements');
            $ret .= "&nbsp;" . $objGetIcon->getEditIcon($this->uri($editArray, "stories"));
        }
        $ret .= "</p>";

        //PUT THE DISQUS COMMENT CODE HERE

        //Add the author and date
        $ret.="<p class=\"minute\">".$this->objLanguage->languageText("phrase_postedby");
        $ret.=" <b>".$this->objUser->fullname($creatorId)."</b> ".$this->objLanguage->languageText("word_on");
        $ret.=" <b>".$dateCreated."</b></p>";

        //DEPRECATED COMMENT CODE
        /*$this->objModule=$this->getObject('modules','modulecatalogue');
        if ($this->objModule->checkIfRegistered('comment', 'comment')){
            //Create an instance of the comment link
            $objComment =  $this->getObject('commentinterface', 'comment');
            //Set the table name
            $objComment->set('tableName', 'tbl_stories');
            $objComment->set('sourceId', $id);
            $ret .= $objComment->showAll();
        }*/

        return $ret;

    }
    
    /**
    *
    * Method to fetch a story by story category
    *
    * @param string $category The category of the stories to return
    * @param integer $limit The number of stories to return
    * @todo -cstories Implement remove hard coding of en and replace with site default.
    *
    */
    function fetchCategory($category, $limit=NULL, $showAuthor=TRUE, $language=NULL)
    {
        return $this->renderCategoryStories(
            $category, $limit, $showAuthor, $language, FALSE, FALSE
        );
    } #function fetchCategory


    /**
     * Render every category-list variant through one semantic producer.
     */
    private function renderCategoryStories(
        $category,
        $limit=NULL,
        $showAuthor=TRUE,
        $language=NULL,
        $summaries=FALSE,
        $archiveExpanded=FALSE,
        $archiveLimit=NULL
    ) {
        if (!$language) {
            $language = 'en';
        }
        $limit = $limit === null ? null : max(1, min(100, (int)$limit));
        $stories = $this->objDbStories->selectPrepared(
            'SELECT * FROM tbl_stories WHERE category = ? AND isActive = ? AND language = ? ORDER BY isSticky DESC, dateCreated DESC, id ASC',
            [(string)$category, '1', (string)$language]);
        if (!$summaries && $limit !== null) $stories = array_slice($stories, 0, $limit);
        $hasMore = $summaries && $limit !== null && count($stories) > $limit;
        $position = 0;

        $objExp = $this->getObject('dateandtime', 'utilities');
        $objLcode = $this->getObject('languagecode', 'language');
        $this->objModule = $this->getObject('modules', 'modulecatalogue');
        $comReg = $this->objModule->checkIfRegistered('comment', 'comment');
        if ($comReg) {
            $objComment = $this->getObject('commentinterface', 'comment');
            $objComment->set('tableName', 'tbl_stories');
            $objComment->set('moduleCode', 'stories');
            $this->loadClass('link', 'htmlelements');
        }
        $curModule = $this->getParam('module', NULL);
        $ret = $summaries ? '<div id="stories">' : '';
        $ret .= '<section class="allstories reading-list">';

        foreach ($stories as $line) {
            if ($hasMore && $position++ === $limit) {
                $ret .= '</section><details class="chisimba-form-disclosure"><summary>'
                    . $this->objLanguage->languageText('mod_stories_native_archives', 'stories')
                    . '</summary><section class="allstories reading-list">';
            }
            $id = $line['id'];
            $creatorId = $line['creatorid'];
            $title = htmlspecialchars(stripslashes($line['title']), ENT_QUOTES, 'UTF-8');
            $abstract = $this->objWashout->parseText(stripslashes($line['abstract']));
            $mainText = $this->objWashout->parseText(stripslashes($line['maintext']));
            $dateCreated = stripslashes($line['datecreated']);
            $isSticky = ((int) $line['issticky'] === 1);
            $isExpired = $objExp->hasExpired(stripslashes($line['expirationdate']));

            $classes = 'currentstory reading-surface';
            if ($isExpired) {
                $classes .= ' is-expired';
            }
            if ($isSticky) {
                $classes .= ' is-pinned';
            }
            $ret .= '<article class="' . $classes . '">';

            $edit = '';
            if ($this->objUser->isAdmin()) {
                $editArray = array('action' => 'edit', 'id' => $id, 'comefrom' => $curModule);
                $objGetIcon = $this->newObject('geticon', 'htmlelements');
                $edit = '&nbsp;&nbsp;' . $objGetIcon->getEditIcon(
                    $this->uri($editArray, 'stories')
                );
            }
            $this->objH->type = $summaries ? 4 : 3;
            $this->objH->str = $title . $edit;
            $ret .= '<div class="storytitle">' . $this->objH->show() . '</div>';
            $ret .= '<div class="abstract">' . $abstract . '</div>';

            if ($summaries) {
                $plain = html_entity_decode(strip_tags($mainText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $summary = mb_substr($plain, 0, 150, 'UTF-8');
                if (mb_strlen($plain, 'UTF-8') > 150) $summary .= '...';
                $ret .= '<div class="storycontent"><p>' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</p>';
                if (mb_strlen($plain, 'UTF-8') > 150) {
                    $ret .= '<details><summary>' . $this->objLanguage->languageText('mod_stories_native_more', 'stories')
                        . '</summary>' . $mainText . '</details>';
                }
                $ret .= '</div>';
            } else {
                $ret .= '<div class="storycontent">' . $mainText . '</div>';
            }

            $translations = $this->getTranslations($id);
            if ((is_countable($translations) ? count($translations) : 0) > 0) {
                $ret .= '<div class="storytranslations">'
                  . $this->objLanguage->languageText('mod_stories_alsoavailable', 'stories');
                foreach ($translations as $translation) {
                    $lcode = $translation['language'];
                    $translationId = $translation['id'];
                    $link = $this->uri(array(
                        'action' => $summaries ? 'viewstory' : 'viewtranslation',
                        'language' => $lcode,
                        'id' => $translationId,
                    ));
                    $ret .= ' <a href="' . $link . '" target="_blank">'
                      . $objLcode->getLanguage($lcode) . '</a>';
                }
                $ret .= '</div>';
            }

            if ($showAuthor || $isSticky || $isExpired) {
                $ret .= '<footer class="storyauthor"><p class="minute">';
                if ($showAuthor) {
                    $ret .= $this->objLanguage->languageText('phrase_postedby')
                      . ' <b>' . $this->objUser->fullname($creatorId) . '</b> '
                      . $this->objLanguage->languageText('word_on')
                      . ' <b>' . $dateCreated . '</b>';
                }
                if ($isSticky) {
                    $ret .= '<span class="story-status story-status--pinned">'
                      . $this->objIcon->render('pin', array('label' => 'Pinned story'))
                      . '</span>';
                }
                if ($isExpired) {
                    $ret .= '<span class="story-status story-status--expired">'
                      . $this->objIcon->render('clock', array('label' => 'Expired story'))
                      . '</span>';
                }
                $ret .= '</p></footer>';
            }

            if ($comReg && $this->objUser->isLoggedIn()) {
                $objComment->set('sourceId', $id);
                $ret .= $objComment->addCommentLink();
                $commentCount = $line['commentcount'];
                if ($commentCount > 0) {
                    $ccStr = $commentCount . ' '
                      . strtolower($this->objLanguage->languageText('word_comments'));
                    $ccLocation = $this->uri(array(
                        'action' => 'viewstory', 'id' => $id
                    ), 'stories');
                    $ret .= $objComment->addViewLink($ccLocation, $ccStr);
                }
            }
            $ret .= '</article>';
        }
        $ret .= '</section>';

        if ($hasMore) $ret .= '</details>';
        if ($summaries) $ret .= '</div>';
        return $ret;
    }

    /** Public fragments may only expose active prelogin stories. */
    public function readableStory($id, $publicOnly = false)
    {
        if (!is_string($id) || $id === '') return false;
        $row = $this->objDbStories->getRow('id', $id, 'tbl_stories');
        if (!$row) return false;
        if (!$publicOnly && $this->objUser->lookupAdmin($this->objUser->userId())) return $row;
        return (int)$row['isactive'] === 1 && ($row['category'] === 'prelogin' || (!$publicOnly && $this->objUser->isLoggedIn())) ? $row : false;
    }

    function getTranslations($id)
    {
        return $this->objDbStories->selectPrepared('SELECT id, parentId, language FROM tbl_stories WHERE parentId = ? AND isActive = ?', [$id, '1']);
    }

    function getTranslatedText($id, $language)
    {
        return $this->objDbStories->selectPrepared('SELECT title, abstract, maintext FROM tbl_stories WHERE parentId = ? AND language = ? AND isActive = ?', [$id, $language, '1']);
    }

    /**
    *
    * Method to put a dropdown list of categories
    *
    */
    function putCategoryChooser()
    {
        $objCat =  $this->getObject('dbstorycategory', 'storycategoryadmin');
        $ar = $objCat->getAll();
        //Load the form class that I need
        $this->loadClass('form','htmlelements');
        $this->loadClass('dropdown','htmlelements');
        //Instantiate the form class
        $objForm = new form('chCat');
        //Instantiate a dropdown
        $objCatDrd = new dropdown ('category_selector');
        $objCatDrd->extra=" onchange=\"document.location=document.forms['chCat'].category_selector.value;\"";
        //Add the categories
        $objCatDrd->addOption("", $this->objLanguage->languageText("mod_stories_anothercat",'stories'));
        foreach ($ar as $line) {
            $link = $this->uri(array(
              'action' => $this->getParam('action', NULL),
              'storycategory' => $line['category']), $this->getParam('module', '_default'));
            $objCatDrd->addOption($link, $line['title']);
        }
        $objForm->addToForm($objCatDrd->show());
        return $objForm->show();
    }

    /**
    *
    * Method to return the most recent stories
    *
    * @param string $where A SQL where clause made up elsewhere.
    * @param integer $num The number of stories to return
    *
    */
    function getMostRecent($where, $num=10)
    {
        $numOfStories=$this->objDbStories->getRecordCount($where);
        if ($numOfStories > $num) {
            $first=$numOfStories - $num;
        } else {
            $first = 0;
        }
        $sql="SELECT * FROM tbl_stories " . $where . " ";
        return $this->objDbStories->getArrayWithLimit($sql, $first, $num);
    } #function getMostRecent



     /**
    *
    * Method to fetch a story by story category
    *
    * @param string $category The category of the stories to return
    * @param integer $limit The number of stories to return
    * @todo -cstories Implement remove hard coding of en and replace with site default.
    *
    */
    function fetchPreLoginCategory($category, $limit=NULL, $showAuthor=TRUE, $language=NULL)
    {
        return $this->renderCategoryStories(
            $category, $limit, $showAuthor, $language, TRUE, FALSE
        );
    } #function fetchCategory


    /**
    * Method to recreate the stories in prelogin with no limits
    * @param string $id The id of the div.
    * @return string $ret The formatted div.
    */
    function createAllStories($limit)
    {
        return $this->renderCategoryStories(
            'prelogin', NULL, TRUE, 'en', TRUE, TRUE, $limit
        );
    } #function fetchCategory

}  #end of class
?>