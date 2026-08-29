<?php

/**
 * Context Forms
 *
 * This class generates commonly used forms related to the context module
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
/* -------------------- dbTable class ----------------*/
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
 * Context Forms
 *
 * This class generates commonly used forms related to the context module
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
class contextforms extends ChisimbaObject {
    /**
     * @var string $formAction : Action URL to point forms to
     */
    public $formAction = 'updatecontext';

    /**
     * @var string $formModule : Module URL to point forms to
     */
    public $formModule = 'context';

    /**
     * Constructor
     */
    public function init() {
        $this->objSysConfig = $this->getObject ('dbsysconfig','sysconfig');
        $this->loadClass('htmlheading', 'htmlelements');
        $this->loadClass('form', 'htmlelements');
        $this->loadClass('textinput', 'htmlelements');
        $this->loadClass('button', 'htmlelements');
        $this->loadClass('radio', 'htmlelements');
        $this->loadClass('label', 'htmlelements');
        $this->loadClass('dropdown', 'htmlelements');
        $this->loadClass('hiddeninput', 'htmlelements');
        $this->loadClass('checkbox', 'htmlelements');
        $this->objLanguage = $this->getObject('language', 'language');
    }

    /**
     * Method to generate an edit context settings form
     * @param array $context Current Context Settings
     * @return str
     */
    public function editContextForm($context) {
        $header = new htmlheading();
        $header->type = 1;
        $header->str = $this->objLanguage->languageText('word_edit', 'system', 'Edit').': '.$context['title'];

        $str = $header->show();

        $title = new textinput('title');
        $title->size = 50;

        if ($context != NULL) {
            $title->value = htmlentities($context['title']);
        }

        $accessPolicy = new radio('access_policy');
        $accessPolicy->setBreakSpace('<br />');
        $accessPolicy->addOption('public', '<strong>Public</strong> - <span class="caption">Anyone may enter.</span>');
        $accessPolicy->addOption('free', '<strong>Free</strong> - <span class="caption">Any signed-in user may enter.</span>');
        $accessPolicy->addOption('tier_1', '<strong>Tier 1</strong> - <span class="caption">Tier 1 and Tier 2 members may enter.</span>');
        $accessPolicy->addOption('tier_2', '<strong>Tier 2</strong> - <span class="caption">Only Tier 2 members may enter.</span>');
        $accessPolicy->addOption('private', '<strong>Paid separately</strong>');
        $legacyPolicyMap = array('public'=>'public', 'open'=>'free', 'private'=>'private');
        $selectedPolicy = $context != NULL && isset($context['access_policy'])
            && trim((string) $context['access_policy']) !== ''
            ? (string) $context['access_policy']
            : ($legacyPolicyMap[strtolower((string) ($context['access'] ?? 'Private'))] ?? 'private');
        $accessPolicy->setSelected($selectedPolicy);
        $privateAdmissionMode = new radio('private_admission_mode');
        $privateAdmissionMode->setBreakSpace('<br />');
        $privateAdmissionMode->addOption('automatic_payment', '<strong>Admit after confirmed payment</strong> - <span class="caption">For courses with no approval requirement.</span>');
        $privateAdmissionMode->addOption('manual_review', '<strong>Manual admission required</strong> - <span class="caption">A manager reviews payment and eligibility before admitting the learner.</span>');
        $privateAdmissionMode->setSelected(!empty($context['private_admission_mode'])
            ? (string) $context['private_admission_mode'] : 'manual_review');
        $access = new hiddeninput(
            'access',
            $context != NULL && isset($context['access']) ? (string) $context['access'] : 'Public'
        );


        $titleLabel = new label ($this->objLanguage->languageText('word_title', 'system', 'Title'), 'input_title');

        $status = new radio('status');
        $status->setBreakSpace(' ');
        $status->addOption('Published', $this->objLanguage->languageText('word_published', 'system', 'Published'));
        $status->addOption('Unpublished', $this->objLanguage->languageText('word_unpublished', 'system', 'Unpublished'));

        if ($context != NULL) {
            $status->setSelected($context['status']);
        }

        $deliveryFormat = new dropdown('delivery_format');
        $deliveryFormat->addOption(
            'standard',
            $this->objLanguage->languageText(
                'mod_context_formatstandard',
                'context',
                'Standard course'
            )
        );
        $deliveryFormat->addOption(
            'microlearning',
            $this->objLanguage->languageText(
                'mod_context_formatmicrolearning',
                'context',
                'Microlearning course'
            )
        );
        $deliveryFormat->addOption(
            'masterclass',
            $this->objLanguage->languageText(
                'mod_context_formatmasterclass',
                'context',
                'Masterclass'
            )
        );
        $selectedFormat = !empty($context['delivery_format'])
            ? strtolower(trim((string) $context['delivery_format']))
            : 'standard';
        if (!in_array($selectedFormat, array('standard', 'microlearning', 'masterclass'), TRUE)) {
            $selectedFormat = 'standard';
        }
        $deliveryFormat->setSelected($selectedFormat);

        $navigation = new dropdown('navigation_mode');
        $navigation->addOption('sequential', $this->objLanguage->languageText('mod_context_navigationsequential', 'context', 'Sequential only'));
        $navigation->addOption('backward', $this->objLanguage->languageText('mod_context_navigationbackward', 'context', 'Backward allowed'));
        $navigation->addOption('free', $this->objLanguage->languageText('mod_context_navigationfree', 'context', 'Free navigation'));
        $navigation->addOption('gated', $this->objLanguage->languageText('mod_context_navigationgated', 'context', 'Gated progression'));
        $selectedNavigation = !empty($context['navigation_mode'])
            ? (string) $context['navigation_mode']
            : ($selectedFormat === 'microlearning' ? 'backward' : 'free');
        $navigation->setSelected($selectedNavigation);

        $canvas = new dropdown('canvas');
        $canvas->addOption('None', $this->objLanguage->languageText('word_none', 'system', 'None'));
        $contextCode = isset($context['contextcode']) ? (string) $context['contextcode'] : '';
        $validCanvases = $contextCode !== ''
            ? array_map('basename', glob('usrfiles/context/' . $contextCode . '/canvases/*', GLOB_ONLYDIR))
            : array();
        foreach ($validCanvases as $validCanvas) { $canvas->addOption($validCanvas, $validCanvas); }
        $canvas->setSelected(!empty($context['canvas']) ? (string) $context['canvas'] : 'None');

        $showComment = new dropdown('showcomment');
        $showComment->addOption('1', $this->objLanguage->languageText('word_yes', 'system', 'Yes'));
        $showComment->addOption('0', $this->objLanguage->languageText('word_no', 'system', 'No'));
        $showComment->setSelected(!empty($context['showcomment']) ? '1' : '0');

        $formatDescriptions = array(
            'standard' => $this->objLanguage->languageText(
                'mod_context_formatstandardhelp',
                'context',
                'A flexible course for varied content, activities and pacing.'
            ),
            'microlearning' => $this->objLanguage->languageText(
                'mod_context_formatmicrolearninghelp',
                'context',
                'Short, focused learning items designed for brief study sessions.'
            ),
            'masterclass' => $this->objLanguage->languageText(
                'mod_context_formatmasterclasshelp',
                'context',
                'A short, focused class on a specific topic, typically one to three hours in duration.'
            ),
        );
        $formatHelp = '<p id="context-delivery-format-help" class="contextadmin-field-help" data-format-descriptions="'
            . htmlspecialchars(
                json_encode($formatDescriptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ENT_QUOTES,
                'UTF-8'
            )
            . '">' . htmlspecialchars($formatDescriptions[$selectedFormat], ENT_QUOTES, 'UTF-8') . '</p>';

        $table = $this->newObject('htmltable', 'htmlelements');

        if ($context != NULL) {
            $table->startRow();
            $table->addCell(ucwords($this->objLanguage->code2Txt('mod_context_contextcode', 'context', NULL, '[-context-] Code')).':', 100);
            $table->addCell('<strong>'.$context['contextcode'].'</strong>');
            $table->endRow();
        } else {
            $code = new textinput('contextcode');
            $codeLabel = new label (ucwords($this->objLanguage->code2Txt('mod_context_contextcode', 'context', NULL, '[-context-] Code')), 'input_contextcode');

            $table->startRow();
            $table->addCell($codeLabel->show(), 100);
            $table->addCell($code->show().' <span id="contextcodemessage"></span>');
            $table->endRow();
        }


        $table->startRow();
        $table->addCell($titleLabel->show().':');
        $table->addCell($title->show());
        $table->endRow();

        $table->startRow();
        $table->addCell('&nbsp;');
        $table->addCell('&nbsp;');
        $table->endRow();

        $table->startRow();
        $table->addCell($this->objLanguage->languageText('word_status', 'system', 'Status').':');
        $table->addCell($status->show());
        $table->endRow();

        $table->startRow();
        $table->addCell($this->objLanguage->languageText(
            'mod_context_courseformat',
            'context',
            'Course format'
        ).':');
        $table->addCell($deliveryFormat->show() . $formatHelp);
        $table->endRow();

        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_context_learnernavigation', 'context', 'Learner navigation') . ':');
        $table->addCell($navigation->show());
        $table->endRow();

        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_context_theme', 'context', 'Theme') . ':');
        $table->addCell($canvas->show());
        $table->endRow();

        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_context_comments', 'context', 'Comments') . ':');
        $table->addCell($showComment->show() . '<p class="contextadmin-field-help">'
            . $this->objLanguage->languageText('mod_context_commentshelp', 'context', 'Allow members to comment on course pages.') . '</p>');
        $table->endRow();

        $table->startRow();
        $table->addCell($this->objLanguage->languageText('word_access', 'system', 'Access').':');
        $table->addCell($accessPolicy->show() . $access->show());
        $table->endRow();

        $table->startRow();
        $table->addCell('Admission policy');
        $table->addCell('<div id="context_private_admission">' . $privateAdmissionMode->show() . '</div>');
        $table->endRow();


        $alerts=explode("|", $context['alerts']);
        $emailAlert = new checkbox('emailalertopt',$this->objLanguage->languageText('mod_context_emailstudentscontent', 'context', 'Email students when course content is added or updated'),$alerts[0] == 'e' || $alerts[0] == '1');

        //$alerts=array();




        //$emailchecked=;
        //$emailAlert->setChecked(FALSE);
        //if($emailchecked) {

        //    $emailAlert->setChecked($emailchecked);
        //}
        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_context_emailalerts', 'context', 'Email alerts'));
        $table->addCell($emailAlert->show());
        $table->endRow();


        $objSelectImage = $this->getObject('selectimage', 'filemanager');
        $objSelectImage->context = TRUE;
        if ($context != NULL && !empty($context['contextcode'])) {
            $objContextImage = $this->getObject('contextimage', 'context');
            $currentImage = $objContextImage->getContextImage(
                $context['contextcode']
            );
            if ($currentImage !== false) {
                $objSelectImage->setDefaultPreviewUrl($currentImage);
            }
        }

        $table->cssClass = 'contextadmin-course-form';
        $htmlEditor = $this->newObject('htmlarea', 'htmlelements');
        $htmlEditor->name = 'about';
        $htmlEditor->toolbarSet = 'advanced';

        if ($context != NULL) {
            $htmlEditor->value = $context['about'];
        }

        if ($context == NULL) {
            $button = new button ('savecontext', $formButton);
        } else {
            $button = new button ('savecontext', ucwords($this->objLanguage->code2Txt('mod_context_updatecontext', 'context', NULL, 'Update [-context-]')));
        }
        $button->setToSubmit();

        $aboutLabel = ucwords($this->objLanguage->code2Txt(
            'mod_context_aboutcontext',
            'context',
            NULL,
            'About [-context-]'
        ));
        $settingsContent = '<div class="context-settings-primary">'
            . '<div class="context-settings-fields">' . $table->show() . '</div>'
            . '<aside class="context-settings-image">' . $objSelectImage->show() . '</aside>'
            . '</div>'
            . '<section class="context-settings-about">'
            . '<h3>' . $aboutLabel . '</h3>'
            . '<div class="context-settings-editor">' . $htmlEditor->show() . '</div>'
            . '</section>'
            . '<div class="contextadmin-course-form-actions">' . $button->show() . '</div>';

        $form = new form ('createcontext', $this->uri(array('action'=>$this->formAction), $this->formModule));

        $form->addToForm('<div class="contextadmin-course-form-wrap context-settings-form">' . $settingsContent . '</div>');

        if ($this->objSysConfig->getValue('context_access_private_only', 'context', 'false') == 'true') {
            $form->addToForm($access->show());
        }

        if ($context != NULL) {
            $hiddenInput = new hiddeninput('contextcode', $context['contextcode']);
            $form->addToForm($hiddenInput->show());
        }

        $form->addRule('title', $this->objLanguage->code2Txt('mod_context_entertitleofcontext', 'context', NULL, 'Please enter the title of your [-context-]'),'required');

        $this->appendArrayVar('headerParams', '<script type="text/javascript">jQuery(function(){function admissionPolicy(){var paid=jQuery("input[name=access_policy]:checked").val()==="private",box=jQuery("#context_private_admission");box.closest("tr").toggle(paid);box.find("input").prop("disabled",!paid);}function formatHelp(){var select=jQuery("#input_delivery_format"),help=jQuery("#context-delivery-format-help"),descriptions={};if(!select.length||!help.length){return;}try{descriptions=JSON.parse(help.attr("data-format-descriptions")||"{}");}catch(error){descriptions={};}help.text(descriptions[select.val()]||"");}jQuery("input[name=access_policy]").on("change",admissionPolicy);jQuery("#input_delivery_format").on("change",formatHelp);admissionPolicy();formatHelp();});</script>');
        $str .= $form->show();

        return $str;
    }


}
?>
