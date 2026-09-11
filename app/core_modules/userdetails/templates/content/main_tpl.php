<?php
/**
*  A main content template for userdetails
*  Author: Kevin Cyster kcyster@gmail.com
*  Date: April 17, 2012, 10:44 am
*
*/
ob_start();
$objFix = $this->getObject('cssfixlength', 'htmlelements');
$objFix->fixThree();
?>

<p><a class="button chisimba-button-secondary" href="<?php echo htmlspecialchars($this->uri(array('action'=>'biography'), 'userdetails'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $this->getObject('iconservice','ui')->render('contact', array('decorative'=>true)); ?><span><?php echo htmlspecialchars(ucfirst($this->getObject('language','language')->code2Txt('mod_userdetails_author_bio','userdetails')), ENT_QUOTES, 'UTF-8'); ?></span></a></p>
<div id="threecolumn">
    <div id="Canvas_Content_Body_Region1">
        {
            "display" : "block",
            "module" : "userdetails",
            "block" : "userdetailsleft"
        }
        <div id="leftdynamic_area" class="leftdynamic_area_layer"></div>
        <div id="leftfeedback_area" class="leftfeedback_area_layer"></div>
    </div>
    <div id="Canvas_Content_Body_Region3">
        {
            "display" : "block",
            "module" : "userdetails",
            "block" : "userdetailsimage"
        }
        <?php $objModules = $this->getObject('modules', 'modulecatalogue');
            $check = $objModules->checkIfRegistered('schoolusers');
            if ($check): ?>
            {
                "display" : "block",
                "module" : "userdetails",
                "block" : "userdetailsgrades"
            }
        <?php endif; ?>
        <div id="rightdynamic_area" class="rightdynamic_area_layer"></div>
        <div id="rightfeedback_area" class="rightfeedback_area_layer"></div>
    </div>
    <div id="Canvas_Content_Body_Region2">
        {
            "display" : "block",
            "module" : "userdetails",
            "block" : "userdetailsmiddle"
        }
        <div id="middledynamic_area" class="middledynamic_area_layer">&nbsp;</div>
        <div id="middlefeedback_area" class="middlefeedback_area_layer">&nbsp;</div>
    </div>
</div>
<?php
// Get the contents for the layout template
$pageContent = ob_get_contents();
ob_end_clean();
$this->setVar('pageContent', $pageContent);
?>