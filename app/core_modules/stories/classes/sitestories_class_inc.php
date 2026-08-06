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
        $ar=$this->objDbStories->getRow('id', $id, 'tbl_stories');
        $creatorId = $ar['creatorid'];
        $isActive = stripslashes($ar['isactive']);
        $title =stripslashes($ar['title']);
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
        $where = " WHERE category='" . $category
          . "' AND isActive='1' AND language='" . $language
          . "' ORDER BY isSticky DESC, dateCreated DESC ";

        if (!$summaries && $limit !== NULL) {
            $stories = $this->getMostRecent($where, $limit);
        } else {
            $stories = $this->objDbStories->getAll($where);
        }
        $hasMore = $summaries && $limit !== NULL
          && (is_countable($stories) ? count($stories) : 0) > $limit;
        if ($hasMore) {
            $stories = array_slice($stories, 0, $limit);
        }

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
            $id = $line['id'];
            $creatorId = $line['creatorid'];
            $title = stripslashes($line['title']);
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
                $summary = substr(strip_tags($mainText), 0, 150);
                if (strlen(strip_tags($mainText)) > 150) {
                    $summary .= '...';
                }
                $ret .= '<div class="storycontent" id="' . htmlspecialchars(
                    $id, ENT_QUOTES, 'UTF-8'
                ) . '">' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8')
                  . " <a href=\"javascript:getFullStory('" . addslashes($id)
                  . "');\">[Read More]</a></div>";
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

        if ($summaries && $hasMore) {
            $ret .= "<a href=\"javascript:getAllStories('" . $limit
              . "');\">View Archives</a>";
        } elseif ($summaries && $archiveExpanded) {
            $ret .= "<a href=\"javascript:getLessStories('" . $archiveLimit
              . "');\">View Less Archives</a>";
        }
        if ($summaries) {
            $ret .= '</div>';
        }
        return $ret;
    }

    /** Keep the existing archive/summary endpoints operational until jQuery cleanup. */
    private function registerSummaryScripts()
    {
        $js = <<<'JS'
<script type="text/javascript">
function getFullStory(id) {
    jQuery.get('index.php', 'module=stories&action=getfullstory&id=' + id, function(data) {
        jQuery('#' + id).html(data);
        if (typeof window.adjustLayout === 'function') { window.adjustLayout(); }
    });
}
function getTrimStory(id) {
    jQuery.get('index.php', 'module=stories&action=gettrunctstory&id=' + id, function(data) {
        jQuery('#' + id).html(data);
        if (typeof window.adjustLayout === 'function') { window.adjustLayout(); }
    });
}
function getAllStories(limit) {
    jQuery.get('index.php', 'module=stories&action=getallstories&limit=' + limit, function(data) {
        jQuery('#stories').html(data);
        if (typeof window.adjustLayout === 'function') { window.adjustLayout(); }
    });
}
function getLessStories(limit) {
    jQuery.get('index.php', 'module=stories&action=getlessstories&limit=' + limit, function(data) {
        jQuery('#stories').html(data);
        if (typeof window.adjustLayout === 'function') { window.adjustLayout(); }
    });
}
</script>
JS;
        $this->appendArrayVar('headerParams', $js);
    }


    function getTranslations($id)
    {
        $sql = "SELECT id, parentId, language FROM tbl_stories WHERE parentId='" . $id . "'";
        return $this->objDbStories->getArray($sql);
    }

    function getTranslatedText($id, $language)
    {
        $sql = "SELECT title, abstract, maintext FROM tbl_stories WHERE parentId='"
          . $id . "' AND language='" . $language . "'";
        return $this->objDbStories->getArray($sql);
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
        $this->registerSummaryScripts();
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