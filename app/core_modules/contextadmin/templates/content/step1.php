<?php
$objSysConfig = $this->getObject ('dbsysconfig','sysconfig');

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('radio', 'htmlelements');
$this->loadClass('label', 'htmlelements');
$this->loadClass('dropdown', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('checkbox', 'htmlelements');
$this->loadClass('fieldset', 'htmlelements');
$this->loadClass('link', 'htmlelements');
$objIcon = $this->newObject('geticon', 'htmlelements');
$objIcon->setIcon('loader');

$contextexists = $this->objLanguage->code2Txt('mod_contextadmin_contextcodeexists', 'contextadmin');
if ($mode == 'edit') {
    $fixup = NULL;

    $formAction = 'updatecontext';
    $headerTitle = ucwords($this->objLanguage->code2Txt('mod_context_updatecontext', 'context', NULL, 'Update [-context-]')) . ': ' . $context['title'];
} else {
    $formAction = 'savestep1';
    $headerTitle = $this->objLanguage->code2Txt('mod_contextadmin_createnewcontext', 'contextadmin', NULL, 'Create New [-context-]');
    $fixup = $this->getSession('fixup', NULL);

    $this->appendArrayVar('headerParams', '
    <script type="text/javascript">

        // Flag Variable - Update message or not
        var doUpdateMessage = false;

        // Var Current Entered Code
        var currentCode;

        // Action to be taken once page has loaded
        jQuery(document).ready(function(){
            jQuery("#input_contextcode").bind(\'keyup\', function() {
                checkCode(jQuery("#input_contextcode").attr(\'value\'));
            });
        });

        // Function to check whether context code is taken
        function checkCode(code)
        {
            // Messages can be updated
            doUpdateMessage = true;

            // If code is null
            if (code == null) {
                // Remove existing stuff
                jQuery("#contextcodemessage").html("");
                jQuery("#contextcodemessage").removeClass("error");
                jQuery("#input_contextcode").removeClass("inputerror");
                jQuery("#contextcodemessage").removeClass("success");
                doUpdateMessage = false;

            // If code is root - Reserved. Saves Ajax Call
            } else if (code.toLowerCase() == "root") {

                currentCode = code;

                jQuery("#contextcodemessage").html("This code has been reserved and cannot be used as a context code.");
                jQuery("#contextcodemessage").addClass("error");
                jQuery("#input_contextcode").addClass("inputerror");
                jQuery("#contextcodemessage").removeClass("success");
                doUpdateMessage = false;

            // Else Need to do Ajax Call
            } else {



                // Check that existing code is not in use
                if (currentCode != code) {

                    // Set message to checking
                    jQuery("#contextcodemessage").removeClass("success");
                    jQuery("#contextcodemessage").html("<span id=\"contextcodecheck\">' . addslashes($objIcon->show()) . ' Checking ...</span>");


                    // Set current Code
                    currentCode = code;

                    // DO Ajax
                    jQuery.ajax({
                        type: "GET",
                        url: "index.php",
                        data: "module=contextadmin&action=checkcode&code="+code,
                        success: function(msg){

                            // Check if messages can be updated and code remains the same
                            if (doUpdateMessage == true && currentCode == code) {

                                // IF code exists
                                if (msg == "exists") {
                                    jQuery("#contextcodemessage").html("' . $contextexists . '");
                                    jQuery("#contextcodemessage").addClass("error");
                                    jQuery("#input_contextcode").addClass("inputerror");
                                    jQuery("#contextcodemessage").removeClass("success");
                                    jQuery("#savebutton").attr("disabled", "disabled");

                                // Else
                                } else {
                                    jQuery("#contextcodemessage").html("Available");
                                    jQuery("#contextcodemessage").addClass("success");
                                    jQuery("#contextcodemessage").removeClass("error");
                                    jQuery("#input_contextcode").removeClass("inputerror");
                                    jQuery("#savebutton").removeAttr("disabled");
                                }

                            }
                        }
                    });
                }
            }
        }
    </script>');
}


$objStepMenu = $this->newObject('stepmenu', 'navigation');
if ($mode == 'edit') {
    $objStepMenu->addStep(str_replace('[-num-]', 1, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')) . ' - ' . ucwords($this->objLanguage->code2Txt('mod_context_contextsettings', 'context', NULL, '[-context-] Settings')), ucwords($this->objLanguage->code2Txt('mod_contextadmin_updatecontextitlesettings', 'contextadmin', NULL, 'Update [-context-] Title and Settings')));
} else {
    $objStepMenu->addStep(str_replace('[-num-]', 1, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')) . ' - ' . ucwords($this->objLanguage->code2Txt('mod_context_contextsettings', 'context', NULL, '[-context-] Settings')), $this->objLanguage->code2Txt('mod_contextadmin_checkcontextcodeavailable', 'contextadmin', NULL, 'Enter [-context-] settings and check whether [-context-] code is available'));
}
$objStepMenu->addStep(str_replace('[-num-]', 2, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')) . ' - ' . ucwords($this->objLanguage->code2Txt('mod_contextadmin_contextinformation', 'contextadmin', NULL, '[-context-] Information')), $this->objLanguage->code2Txt('mod_contextadmin_enterinfoaboutcontext', 'contextadmin', NULL, 'Enter more information about your [-context-] and select a [-context-] image'));

$objStepMenu->addStep(str_replace('[-num-]', 3, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')) . ' - ' . ucwords($this->objLanguage->code2Txt('mod_contextadmin_courseoutcome', 'contextadmin', NULL, '[-context-] Outcomes')), $this->objLanguage->code2Txt('mod_context_enteroutcomecontext', 'contextadmin', NULL, 'Enter the main Outcomes / Goals of the [-context-]'));


$objStepMenu->addStep(str_replace('[-num-]', 4, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')) . ' - ' . ucwords($this->objLanguage->code2Txt('mod_context_contextpluginsabs', 'context', array('plugins' => 'plugins'), '[-context-] [-plugins-]')), $this->objLanguage->code2Txt('mod_contextadmin_selectpluginsforcontextabs', 'contextadmin', array('plugins' => 'plugins'), 'Select the [-plugins-] you would like to use in this [-context-]'));
$objStepMenu->current = 1;
echo $objStepMenu->show();


$header = new htmlheading();
$header->type = 1;
$header->str = ucwords($headerTitle);

echo '<div class="contextadmin-course-creation"><br />' . $header->show();

if ($mode == 'add' && is_array($fixup) && !empty($fixup['creation_error'])) {
    $isCpdFailure = strpos((string) $fixup['creation_error'], 'cpd_') === 0
        || strpos((string) $fixup['creation_error'], 'invalid_cpd_') === 0;
    $message = $isCpdFailure
        ? $this->objLanguage->languageText('mod_contextadmin_cpdcreatefailed', 'contextadmin', 'The course was not created because its CPD settings could not be saved. Check the CPD fields and try again.')
        : $this->objLanguage->languageText('mod_contextadmin_coursecreatefailed', 'contextadmin', 'The course could not be created. Check the fields and try again.');
    echo '<div class="error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
}


// CREATE FORM
$form = new form('createcontext', $this->uri(array('action' => $formAction)));


$code = new textinput('contextcode');

if ($mode == 'add' && is_array($fixup)) {
    if ($fixup['contextcode'] == '') {
        $contextCodeMessage = '<span class="warning">' . $this->objLanguage->code2Txt('mod_contextadmin_didnotentercontextcode', 'contextadmin', NULL, 'You did not enter a [-context-] code') . '</span>';
    } else {
        $contextCodeMessage = '<span class="warning">' . $this->objLanguage->languageText('mod_contextadmin_youentered', 'contextadmin', 'You entered') . ' <strong><u>' . $fixup['contextcode'] . '</u></strong> ' . $this->objLanguage->languageText('mod_contextadmin_buthasalreadybeentaken', 'contextadmin', 'but that has been taken already') . '</span>';
    }
} else {
    $contextCodeMessage = '';
}

$codeLabel = new label(ucwords($this->objLanguage->code2Txt('mod_context_contextcode', 'context', NULL, '[-context-] Code')), 'input_contextcode');


$title = new textinput('title');
$title->size = 50;

if ($mode == 'add' && is_array($fixup)) {
    $title->value = $fixup['title'];
} else if ($mode == 'edit') {
    $title->value = $context['title'];
}

$titleLabel = new label($this->objLanguage->languageText('word_title', 'system', 'Title'), 'input_title');
$objConfig = $this->getObject('altconfig', 'config');
$skinName = $objConfig->getdefaultSkin();

$validCanvases = array_map('basename', glob('usrfiles/context/' . $this->objContext->getContextCode() . '/canvases/*', GLOB_ONLYDIR));


$canvas = new dropdown('canvas');
//$status->setBreakSpace('<br />');

$canvas->addOption('None', 'None');
foreach ($validCanvases as $validCanvas) {
    $canvas->addOption($validCanvas, $validCanvas);
}

//$canvas->size = 50;

if ($mode == 'add' && is_array($fixup)) {
    $canvas->setSelected($fixup['canvas']);
} else if ($mode == 'edit') {
    $canvas->setSelected($context['canvas']);
}

$canvasLabel = new label($this->objLanguage->languageText('mod_contextadmin_theme', 'contextadmin', 'Theme'), 'input_canvas');


$status = new radio('status');
$status->setBreakSpace(' ');
$status->addOption('Published', $this->objLanguage->languageText('word_published', 'system', 'Published'));
$status->addOption('Unpublished', $this->objLanguage->languageText('word_unpublished', 'system', 'Unpublished'));

if ($mode == 'add' && is_array($fixup)) {
    $status->setSelected($fixup['status']);
} else if ($mode == 'edit') {
    $status->setSelected($context['status']);
}

    //$access = new hiddeninput('access', 'Private');
//} else {
$legacyAccess = $mode == 'edit' && isset($context['access'])
    ? (string) $context['access']
    : (($mode == 'add' && is_array($fixup) && isset($fixup['access']))
        ? (string) $fixup['access'] : 'Public');
$access = new hiddeninput('access', $legacyAccess);

$accessPolicy = new radio('access_policy');
$accessPolicy->setBreakSpace('<br />');
$accessPolicy->addOption('public', '<strong>Public</strong> - <span class="caption">Anyone may enter, including anonymous visitors.</span>');
$accessPolicy->addOption('free', '<strong>Free</strong> - <span class="caption">Any signed-in user may enter.</span>');
$accessPolicy->addOption('tier_1', '<strong>Tier 1</strong> - <span class="caption">Members with Tier 1 or Tier 2 may enter.</span>');
$accessPolicy->addOption('tier_2', '<strong>Tier 2</strong> - <span class="caption">Only Tier 2 members may enter.</span>');
$accessPolicy->addOption('private', '<strong>Paid separately</strong>');
$legacyPolicyMap = array('public'=>'public', 'open'=>'free', 'private'=>'private');
$selectedAccessPolicy = $legacyPolicyMap[strtolower($legacyAccess)] ?? 'private';
if ($mode == 'add' && is_array($fixup) && isset($fixup['access_policy'])) {
    $selectedAccessPolicy = (string) $fixup['access_policy'];
} elseif ($mode == 'edit' && isset($context['access_policy'])) {
    $selectedAccessPolicy = (string) $context['access_policy'];
}
$accessPolicy->setSelected($selectedAccessPolicy);
$privateAdmissionMode = new radio('private_admission_mode');
$privateAdmissionMode->setBreakSpace('<br />');
$privateAdmissionMode->addOption('automatic_payment', '<strong>Admit after confirmed payment</strong> - <span class="contextadmin-field-help">For courses with no approval requirement.</span>');
$privateAdmissionMode->addOption('manual_review', '<strong>Manual admission required</strong> - <span class="contextadmin-field-help">A manager reviews payment and eligibility before admitting the learner.</span>');
$selectedPrivateAdmissionMode = 'manual_review';
if ($mode == 'add' && is_array($fixup) && !empty($fixup['private_admission_mode'])) {
    $selectedPrivateAdmissionMode = (string) $fixup['private_admission_mode'];
} elseif ($mode == 'edit' && !empty($context['private_admission_mode'])) {
    $selectedPrivateAdmissionMode = (string) $context['private_admission_mode'];
}
$privateAdmissionMode->setSelected($selectedPrivateAdmissionMode);

$table = $this->newObject('htmltable', 'htmlelements');
$table->cssClass = 'contextadmin-course-form';
$table->startRow();
$table->addCell('<h2 class="contextadmin-form-section-heading">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_coursedetails', 'contextadmin', 'Course details'), ENT_QUOTES, 'UTF-8') . '</h2>', NULL, NULL, 2);
$table->endRow();

if ($mode == 'edit') {
    $table->startRow();
    $table->addCell($this->objLanguage->code2Txt('mod_context_contextcode', 'context', NULL, '[-context-] Code'), 100);
    $table->addCell('<strong title="' . $this->objLanguage->code2Txt('mod_contextadmin_contextcodecannotbechanged', 'contextadmin', NULL, '[-context-] code can not be changed') . '">' . strtoupper($context['contextcode']) . '</strong>');
    $table->endRow();
    $hiddenInput = new hiddeninput('editcontextcode', $context['contextcode']);
    $form->addToForm($hiddenInput->show());
} else {
    $table->startRow();
    $table->addCell($codeLabel->show(), 100);
    $table->addCell($code->show() . ' <span id="contextcodemessage">' . $contextCodeMessage . '</span>');
    $table->endRow();
}

$table->startRow();
$table->addCell($titleLabel->show());
$table->addCell($title->show());
$table->endRow();

$selectedFormat = 'standard';
$selectedNavigation = 'free';
if ($mode == 'add' && is_array($fixup)) {
    $selectedFormat = isset($fixup['delivery_format']) ? $fixup['delivery_format'] : 'standard';
    $selectedNavigation = isset($fixup['navigation_mode']) && $fixup['navigation_mode'] !== ''
        ? $fixup['navigation_mode'] : ($selectedFormat === 'microlearning' ? 'backward' : 'free');
} elseif ($mode == 'edit') {
    $selectedFormat = !empty($context['delivery_format']) ? $context['delivery_format'] : 'standard';
    $selectedNavigation = !empty($context['navigation_mode']) ? $context['navigation_mode'] : 'free';
}

$format = new dropdown('delivery_format');
$format->addOption('standard', $this->objLanguage->languageText('mod_contextadmin_format_standard', 'contextadmin', 'Standard course'));
$format->addOption('microlearning', $this->objLanguage->languageText('mod_contextadmin_format_microlearning', 'contextadmin', 'Microlearning course'));
$format->addOption('masterclass', $this->objLanguage->languageText('mod_contextadmin_format_masterclass', 'contextadmin', 'Masterclass'));
$format->setSelected($selectedFormat);
$formatDescriptions = array(
    'standard' => $this->objLanguage->languageText(
        'mod_contextadmin_format_standard_help',
        'contextadmin',
        'A flexible course for varied content, activities and pacing.'
    ),
    'microlearning' => $this->objLanguage->languageText(
        'mod_contextadmin_format_microlearning_help',
        'Short, focused learning items designed for brief study sessions.'
    ),
    'masterclass' => $this->objLanguage->languageText(
        'mod_contextadmin_format_masterclass_help',
        'A short, focused class on a specific topic, typically one to three hours in duration.'
    ),
);
$formatHelp = '<p id="delivery-format-help" class="contextadmin-field-help" data-format-descriptions="'
    . htmlspecialchars(json_encode($formatDescriptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')
    . '">' . htmlspecialchars($formatDescriptions[$selectedFormat] ?? $formatDescriptions['standard'], ENT_QUOTES, 'UTF-8') . '</p>';
$this->appendArrayVar('headerParams', '<script type="text/javascript">document.addEventListener("DOMContentLoaded",function(){var select=document.getElementById("input_delivery_format"),help=document.getElementById("delivery-format-help"),descriptions={};if(!select||!help){return;}try{descriptions=JSON.parse(help.dataset.formatDescriptions||"{}");}catch(error){descriptions={};}select.addEventListener("change",function(){help.textContent=descriptions[select.value]||"";});});</script>');
$navigation = new dropdown('navigation_mode');
$navigation->addOption('sequential', $this->objLanguage->languageText('mod_contextadmin_navigation_sequential', 'contextadmin', 'Sequential only'));
$navigation->addOption('backward', $this->objLanguage->languageText('mod_contextadmin_navigation_backward', 'contextadmin', 'Backward allowed'));
$navigation->addOption('free', $this->objLanguage->languageText('mod_contextadmin_navigation_free', 'contextadmin', 'Free navigation'));
$navigation->addOption('gated', $this->objLanguage->languageText('mod_contextadmin_navigation_gated', 'contextadmin', 'Gated progression'));
$navigation->setSelected($selectedNavigation);

$table->startRow();
$table->addCell('<h2 class="contextadmin-form-section-heading">' . $this->objLanguage->languageText('mod_contextadmin_learningdesign', 'contextadmin', 'Learning design') . '</h2>');
$table->addCell($this->objLanguage->languageText('mod_contextadmin_learningdesign_help', 'contextadmin', 'Choose the course format and how learners may move through its content. The format supplies a default, which you may change.'));
$table->endRow();
$table->startRow();
$table->addCell($this->objLanguage->languageText('mod_contextadmin_deliveryformat', 'contextadmin', 'Course format'));
$table->addCell($format->show() . $formatHelp);
$table->endRow();
$table->startRow();
$table->addCell($this->objLanguage->languageText('mod_contextadmin_navigationmode', 'contextadmin', 'Learner navigation'));
$table->addCell($navigation->show());
$table->endRow();

if ($mode == 'add' && !empty($contextAdminCpdAvailable)) {
    $cpdChecked = is_array($fixup) && !empty($fixup['cpd_enabled']);
    $cpdScheme = is_array($fixup) ? ($fixup['cpd_scheme_id'] ?? '') : '';
    $cpdCategory = is_array($fixup) ? ($fixup['cpd_category_id'] ?? '') : '';
    $cpdHtml = '<label><input type="checkbox" name="cpd_enabled" id="cpd_enabled" value="1"' . ($cpdChecked ? ' checked="checked"' : '') . ' /> '
        . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_enablecpd', 'contextadmin', 'Enable CPD recognition for this course'), ENT_QUOTES, 'UTF-8') . '</label>';
    $cpdHtml .= '<div id="cpd_creation_settings"><p><label for="cpd_scheme_id">'
        . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdscheme', 'contextadmin', 'CPD scheme'), ENT_QUOTES, 'UTF-8') . '</label> <select name="cpd_scheme_id" id="cpd_scheme_id"><option value="">--</option>';
    foreach ($contextAdminCpdSchemes as $scheme) {
        $cpdHtml .= '<option value="' . htmlspecialchars($scheme['id'], ENT_QUOTES, 'UTF-8') . '"' . ($cpdScheme === $scheme['id'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($scheme['name'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $cpdHtml .= '</select></p><p><label for="cpd_category_id">'
        . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdcategory', 'contextadmin', 'CPD category'), ENT_QUOTES, 'UTF-8') . '</label> <select name="cpd_category_id" id="cpd_category_id"><option value="">--</option>';
    foreach ($contextAdminCpdCategories as $schemeId => $schemeCategories) {
        foreach ($schemeCategories as $category) {
            $cpdHtml .= '<option data-scheme="' . htmlspecialchars($schemeId, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8') . '"' . ($cpdCategory === $category['id'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') . '</option>';
        }
    }
    $cpdHtml .= '</select></p><p><label for="cpd_points">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdpoints', 'contextadmin', 'Points on completion'), ENT_QUOTES, 'UTF-8') . '</label> <input type="number" min="0.01" step="0.01" name="cpd_points" id="cpd_points" value="' . htmlspecialchars(is_array($fixup) ? ($fixup['cpd_points'] ?? '') : '', ENT_QUOTES, 'UTF-8') . '" /></p>';
    $cpdHtml .= '<p><label for="cpd_valid_from">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdvalidfrom', 'contextadmin', 'CPD recognition starts (DD-MM-YYYY)'), ENT_QUOTES, 'UTF-8') . '</label> <input type="text" name="cpd_valid_from" id="cpd_valid_from" value="' . htmlspecialchars(is_array($fixup) ? ($fixup['cpd_valid_from'] ?? '') : '', ENT_QUOTES, 'UTF-8') . '" /><br /><span class="caption">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdvalidfromhelp', 'contextadmin', 'The first date on which completing this course can earn CPD points. Leave blank if recognition starts immediately.'), ENT_QUOTES, 'UTF-8') . '</span></p>';
    $cpdHtml .= '<p><label for="cpd_valid_until">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdvaliduntil', 'contextadmin', 'CPD recognition ends (DD-MM-YYYY)'), ENT_QUOTES, 'UTF-8') . '</label> <input type="text" name="cpd_valid_until" id="cpd_valid_until" value="' . htmlspecialchars(is_array($fixup) ? ($fixup['cpd_valid_until'] ?? '') : '', ENT_QUOTES, 'UTF-8') . '" /><br /><span class="caption">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdvaliduntilhelp', 'contextadmin', 'The final date on which completing this course can earn CPD points. Leave blank if recognition has no end date.'), ENT_QUOTES, 'UTF-8') . '</span></p>';
    $cpdHtml .= '<p><label for="cpd_reason">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdreference', 'contextadmin', 'Administrative reason or reference'), ENT_QUOTES, 'UTF-8') . '</label> <input type="text" name="cpd_reason" id="cpd_reason" value="' . htmlspecialchars(is_array($fixup) ? ($fixup['cpd_reason'] ?? '') : '', ENT_QUOTES, 'UTF-8') . '" /></p><p class="caption">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_cpdprivatehelp', 'contextadmin', 'CPD courses are private so learner identity, enrolment and completion can be verified.'), ENT_QUOTES, 'UTF-8') . '</p></div>';
    $table->startRow();
    $table->addCell('<h2 class="contextadmin-form-section-heading">' . $this->objLanguage->languageText('mod_contextadmin_cpdrecognition', 'contextadmin', 'CPD recognition') . '</h2>');
    $table->addCell($cpdHtml);
    $table->endRow();
    $this->appendArrayVar('headerParams', '<script type="text/javascript">jQuery(function(){var previousAccess=null;function cpdToggle(){var on=jQuery("#cpd_enabled").is(":checked"),access=jQuery("input[name=access]");jQuery("#cpd_creation_settings").toggle(on);jQuery("#cpd_scheme_id,#cpd_category_id,#cpd_points,#cpd_reason").prop("required",on);if(on){if(previousAccess===null){previousAccess=access.filter(":checked").val()||null;}access.filter("[value=Private]").prop("checked",true);}else if(previousAccess!==null){access.filter("[value="+previousAccess+"]").prop("checked",true);previousAccess=null;}}function categoryFilter(){var scheme=jQuery("#cpd_scheme_id").val();jQuery("#cpd_category_id option[data-scheme]").each(function(){jQuery(this).prop("hidden",jQuery(this).data("scheme")!==scheme);});if(jQuery("#cpd_category_id option:selected").data("scheme")!==scheme){jQuery("#cpd_category_id").val("");}}jQuery("#cpd_enabled").on("change",cpdToggle);jQuery("#cpd_scheme_id").on("change",categoryFilter);cpdToggle();categoryFilter();jQuery("#input_delivery_format").on("change",function(){jQuery("#input_navigation_mode").val(jQuery(this).val()==="microlearning"?"backward":"free");});});</script>');
}


$uploadlink = new link($this->uri(array("action" => "uploadtheme")));
$uploadlink->link = '<strong>' . $this->objLanguage->languageText('mod_contextadmin_upload', 'contextadmin', 'Upload') . '</strong>';

if ($mode == 'edit') {
    $table->startRow();
    $table->addCell($canvasLabel->show());
    $table->addCell($canvas->show() . $uploadlink->show());
}
$table->endRow();

$table->startRow();
$table->addCell('<h2 class="contextadmin-form-section-heading">' . htmlspecialchars($this->objLanguage->languageText('mod_contextadmin_availability', 'contextadmin', 'Availability and participation'), ENT_QUOTES, 'UTF-8') . '</h2>', NULL, NULL, 2);
$table->endRow();

$table->startRow();
$table->addCell($this->objLanguage->languageText('word_status', 'system', 'Status'));
$table->addCell($status->show());
$table->endRow();

$showcomment = new dropdown('showcomment');
//$status->setBreakSpace('<br />');
$showcomment->addOption('1', $this->objLanguage->languageText('word_yes', 'system', 'Yes') . " ");
$showcomment->addOption('0', $this->objLanguage->languageText('word_no', 'system', 'No') . " ");

if ($mode == 'add' && is_array($fixup)) {
    $showcomment->setSelected($fixup['showcomment']);
} else if ($mode == 'edit') {
    $showcomment->setSelected($context['showcomment']);
}

$table->startRow();
$table->addCell($this->objLanguage->languageText('mod_contextadmin_comment', 'contextadmin', 'Comment'));
$table->addCell($showcomment->show() . '<span class="contextadmin-field-help">' . $this->objLanguage->languageText('mod_contextadmin_comments', 'contextadmin', 'Enable or Disable users to post comments on page content') . '</span>');
$table->endRow();

$table->startRow();
$table->addCell($this->objLanguage->languageText('word_access', 'system', 'Access'));
$table->addCell($accessPolicy->show() . $access->show());
$table->endRow();

$table->startRow();
$table->addCell('Admission policy');
$table->addCell('<div id="context_private_admission">' . $privateAdmissionMode->show() . '</div>');
$table->endRow();

$this->appendArrayVar('headerParams', '<script type="text/javascript">jQuery(function(){function admissionPolicy(){var paid=jQuery("input[name=access_policy]:checked").val()==="private",box=jQuery("#context_private_admission");box.closest("tr").toggle(paid);box.find("input").prop("disabled",!paid);}jQuery("input[name=access_policy]").on("change",admissionPolicy);admissionPolicy();});</script>');

$button = new button('savecontext', $this->objLanguage->languageText('mod_contextadmin_gotonextstep', 'contextadmin', 'Go to Next Step'));
$button->cssId = 'savebutton';
$button->cssClass = 'contextadmin-wizard-button contextadmin-wizard-button-primary';
$button->sexyButtons = FALSE;
$button->setToSubmit();

//$table_ = $table->show();
//if ($objSysConfig->getValue('context_access_private_only', 'context', 'false') == 'true') {
//    $table_ .= $access->show();
//}
$form->addToForm('<div class="contextadmin-course-form-wrap">' . $table->show() . '<div class="contextadmin-course-form-actions">' . $button->show() . '</div></div>');

$hiddenInput = new hiddeninput('mode', $mode);
$form->addToForm($hiddenInput->show());
$csrfInput = new hiddeninput('csrf_token', $contextAdminCsrf);
$form->addToForm($csrfInput->show());

if ($mode == 'add') {
    $form->addRule('contextcode', $this->objLanguage->code2Txt('mod_contextadmin_pleaseentercontextcode', 'contextadmin', NULL, 'Please enter a [-context-] code'), 'required');
}
$form->addRule('title', $this->objLanguage->languageText('mod_contextadmin_pleaseentertitle', 'contextadmin', 'Please enter a title'), 'required');

echo $form->show();
echo '</div>';
?>
