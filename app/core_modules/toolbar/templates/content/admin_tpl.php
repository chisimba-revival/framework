<?php
/**
* @package
*/

/**
* The admin page grouping all administrative tools together.
* @param array $modules The list of modules for display on the page.
*/
$this->setLayoutTemplate('admin_layout_tpl.php');

// set up html elements
$this->objLanguage = $this->getObject('language','language');
$tab = $this->newObject('tabber', 'htmlelements');
$tab->tabId = TRUE;
$objIcon = $this->getObject('iconservice', 'ui');
$objLink = $this->newObject('link', 'htmlelements');
$objTable = $this->newObject('htmltable', 'htmlelements');
$objHead = $this->newObject('htmlheading', 'htmlelements');
$objSkin = $this->newObject('skin', 'skin');

// set up language items
$head = $this->objLanguage->languageText('mod_toolbar_siteadmin','toolbar');

// set up icon folder
$this->iconFolder = $objSkin->getSkinLocation()."_common/icons/";
$this->iconModFolder = $this->iconFolder."modules/";

$objHead->type = 1;
$objHead->str = $head;
echo $objHead->show().'<br />';

$adminIconMap = array(
    'contextadmin' => 'book-open',
    'contextgroups' => 'users-round',
    'useradmin' => 'user-cog',
    'groupadmin' => 'users-round',
    'permissions' => 'shield-check',
    'moduleadmin' => 'boxes',
    'modulecatalogue' => 'boxes',
    'createlang' => 'languages',
    'language' => 'languages',
    'extensions' => 'puzzle',
    'languagetext' => 'text',
    'systext' => 'text',
    'serverstatus' => 'server-cog',
    'sysconfig' => 'server-cog',
    'rubric' => 'scroll-text',
    'viewsource' => 'code-2',
);

if(!empty($modules)){
    $langArray = array('context'=>'course', 'contexts'=>'courses', 'author'=>'lecturer', 'authors'=>'lecturers', 'readonly'=>'student', 'readonlys'=>'students');
    // Admin Page Categories:
    foreach($modules as $category=>$items){
        // set up table and column widths
        $objTable->init();
        $objTable->width='99%';
        $objTable->cssClass='adminmenu-table';

        $objTable->startRow();
        $objTable->addCell('', '33%');
        $objTable->addCell('', '33%');
        $objTable->addCell('', '33%');
        $objTable->endRow();

        $objTable->startRow();
        $i = 0;
        if(!empty($items)){
            // Items (modules) in each category
            foreach($items as $key=>$line){
                if($i++ % 3 == 0){
                    $objTable->endRow();
                    $objTable->addRow(array('&nbsp;'));
                    $objTable->startRow();
                }

                $iconKey = isset($line['icon']) && !empty($line['icon'])
                    ? $line['icon'] : $line['module'];
                if (isset($adminIconMap[$iconKey])) {
                    $iconName = $adminIconMap[$iconKey];
                } elseif (isset($adminIconMap[$line['module']])) {
                    $iconName = $adminIconMap[$line['module']];
                } else {
                    $iconName = 'puzzle';
                }
                $iconMarkup = $objIcon->render(
                    $iconName,
                    array('decorative' => true, 'class' => 'adminmenu-icon')
                );

                // if an action is specified for the link
                $action = array();
                if(isset($line['action']) && !empty($line['action'])){
                    $action = array('action'=>$line['action']);
                }

                $objLink->link($this->uri($action,$line['module']));

                // if the link text is specified
                if(isset($line['name']) && !empty($line['name'])){
                    $name = ucwords($this->objLanguage->code2Txt($line['name'],$line['module'], $langArray));
                }else{
                    $name = ucwords($this->objLanguage->code2Txt('mod_'.$line['module'].'_name',$line['module']));
                }

                $objLink->link = $iconMarkup.'<br />'.$name;
                $objTable->addCell($objLink->show(), '', 'bottom', 'center');
            }
            
        }
        $objTable->endRow();
        $tab->addTab(array('name'=> $this->objLanguage->languageText('mod_toolbar_'.$category,'toolbar'),'content' => $objTable->show()));
    }
}
echo  $tab->show();
?>
