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
        $accessPolicy->addOption('', '<strong>Legacy course access</strong> - <span class="caption">Preserve the existing Public, Open or Private behaviour.</span>');
        $accessPolicy->addOption('public', '<strong>Public</strong> - <span class="caption">Anyone may enter.</span>');
        $accessPolicy->addOption('free', '<strong>Free</strong> - <span class="caption">Any signed-in user may enter.</span>');
        $accessPolicy->addOption('tier_1', '<strong>Tier 1</strong> - <span class="caption">Tier 1 and Tier 2 members may enter.</span>');
        $accessPolicy->addOption('tier_2', '<strong>Tier 2</strong> - <span class="caption">Only Tier 2 members may enter.</span>');
        $accessPolicy->addOption('private', '<strong>Private</strong> - <span class="caption">An explicit course entitlement is required.</span>');
        $accessPolicy->setSelected($context != NULL && isset($context['access_policy'])
            ? (string) $context['access_policy'] : '');


        $titleLabel = new label ($this->objLanguage->languageText('word_title', 'system', 'Title'), 'input_title');

        $status = new dropdown ('status');
        //$status->setBreakSpace('<br />');
        $status->addOption('Published', $this->objLanguage->languageText('word_published', 'system', 'Published'));
        $status->addOption('Unpublished', $this->objLanguage->languageText('word_unpublished', 'system', 'Unpublished'));

        if ($context != NULL) {
            $status->setSelected($context['status']);
        }

        if ($this->objSysConfig->getValue('context_access_private_only', 'context', 'false') == 'true') {
            if (is_null($context)) {
                $access_ = 'Private';
            } else {
                $access_ = $context['access'];
            }
            $access = new hiddeninput('access', $access_);
        } else {
            $access= new radio ('access');
            $access->setBreakSpace('<br />');
            $access->addOption('Public', '<strong>'.$this->objLanguage->languageText('word_public', 'system', 'Public').'</strong> - <span class="caption">'.ucfirst($this->objLanguage->code2Txt('mod_context_publiccontextdescription', 'context', NULL, '[-context-] can be accessed by all users, including anonymous users')).'</span>');
            $access->addOption('Open', '<strong>'.$this->objLanguage->languageText('word_open', 'system', 'Open').'</strong> - <span class="caption">'.ucfirst($this->objLanguage->code2Txt('mod_context_opencontextdescription', 'context', NULL, '[-context-] can be accessed by all users that are logged in')).'</span>');
            $access->addOption('Private', '<strong>'.$this->objLanguage->languageText('word_private', 'system', 'Private').'</strong> - <span class="caption">'.$this->objLanguage->code2Txt('mod_context_privatecontextdescription', 'context', NULL, 'Only [-context-] members can enter the [-context-]').'<span class="caption">');

            if ($context != NULL) {
                $access->setSelected($context['access']);
            } else {
                $access->setSelected('Public');
            }
        }



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

        if ($this->objSysConfig->getValue('context_access_private_only', 'context', 'false') == 'false') {
            $table->startRow();
            $table->addCell($this->objLanguage->languageText('word_access', 'system', 'Access').':');
            $table->addCell($access->show());
            $table->endRow();
        }
        $table->startRow();
        $table->addCell('Admission policy:');
        $table->addCell($accessPolicy->show() . '<span class="caption">Entry only; existing course membership and roles still govern permissions after admission.</span>');
        $table->endRow();


        $alerts=explode("|", $context['alerts']);
        $emailAlert = new checkbox('emailalertopt',$this->objLanguage->languageText('mod_contextadmin_emailalert', 'contextadmin', 'Email alerts'),$alerts[0] == 'e' || $alerts[0] == '1');  // this will checked

        //$alerts=array();




        //$emailchecked=;
        //$emailAlert->setChecked(FALSE);
        //if($emailchecked) {

        //    $emailAlert->setChecked($emailchecked);
        //}
        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_contextadmin_emailalert', 'contextadmin', 'Alerts'));
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

        $table2 = $this->newObject('htmltable', 'htmlelements');
        $table2->startRow();
        $table2->addCell($table->show(), 600, NULL, NULL, NULL, 'colspan="2"');
        $table2->addCell($objSelectImage->show());
        $table2->endRow();

        $table2->startRow();
        $table2->addCell('&nbsp;');
        $table2->addCell('&nbsp;');
        $table2->addCell('&nbsp;');
        $table2->endRow();

        $table2->startRow();
        $table2->addCell(ucwords($this->objLanguage->code2Txt('mod_context_aboutcontext', 'context', NULL, 'About [-context-]')).':', 100);

        $htmlEditor = $this->newObject('htmlarea', 'htmlelements');
        $htmlEditor->name = 'about';
        $htmlEditor->toolbarSet = 'advanced';

        if ($context != NULL) {
            $htmlEditor->value = $context['about'];
        }

        $table2->addCell($htmlEditor->show(), NULL, NULL, NULL, NULL, 'colspan="2"');
        $table2->endRow();

        $table2->startRow();
        $table2->addCell('&nbsp;');
        $table2->addCell('&nbsp;');
        $table2->addCell('&nbsp;');
        $table2->endRow();


        $table2->startRow();
        $table2->addCell('&nbsp;', 100);

        if ($context == NULL) {
            $button = new button ('savecontext', $formButton);
        } else {
            $button = new button ('savecontext', ucwords($this->objLanguage->code2Txt('mod_context_updatecontext', 'context', NULL, 'Update [-context-]')));
        }
        $button->setToSubmit();

        $table2->addCell($button->show(), NULL, NULL, NULL, NULL, 'colspan="2"');
        $table2->endRow();

        $form = new form ('createcontext', $this->uri(array('action'=>$this->formAction), $this->formModule));

        $form->addToForm($table2->show());

        if ($this->objSysConfig->getValue('context_access_private_only', 'context', 'false') == 'true') {
            $form->addToForm($access->show());
        }

        if ($context != NULL) {
            $hiddenInput = new hiddeninput('contextcode', $context['contextcode']);
            $form->addToForm($hiddenInput->show());
        }

        $form->addRule('title', $this->objLanguage->code2Txt('mod_context_entertitleofcontext', 'context', NULL, 'Please enter the title of your [-context-]'),'required');

        $str .= $form->show();

        return $str;
    }


}
?>
