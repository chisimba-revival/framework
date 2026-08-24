<?php

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Render an image selector backed by the native File Manager picker.
 *
 * The component preserves the historical form contract: the named hidden
 * field contains the selected File Manager record ID. Existing consumers can
 * therefore adopt the native picker without changing their save handlers.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   filemanager
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
class selectimage extends ChisimbaObject
{
    /** @var string Name of the submitted File Manager record-ID field. */
    public $name;

    /** @var string Existing File Manager record ID. */
    public $defaultFile;

    /** @var string Existing image URL when no File Manager ID is retained. */
    public $defaultPreviewUrl;

    /** @var mixed Retained for compatibility with historical consumers. */
    public $context;

    /** @var bool Retained for compatibility with historical consumers. */
    public $workgroup;

    /** @var string Retained for compatibility with historical consumers. */
    public $widthOfInput;

    /** @var object File Manager database gateway. */
    private $objFile;

    /** @var object File Manager thumbnail helper. */
    private $objThumbnails;

    /** @var object Language service. */
    private $objLanguage;

    /**
     * Initialise the compatible image-selector component.
     *
     * @return void
     */
    public function init()
    {
        $this->name = 'imageselect';
        $this->defaultFile = '';
        $this->defaultPreviewUrl = '';
        $this->context = false;
        $this->workgroup = false;
        $this->widthOfInput = '80%';
        $this->objFile = $this->getObject('dbfile', 'filemanager');
        $this->objThumbnails = $this->getObject('thumbnails', 'filemanager');
        $this->objLanguage = $this->getObject('language', 'language');
    }

    /**
     * Set the initially selected File Manager record.
     *
     * @param string $fileId File Manager record ID.
     *
     * @return void
     */
    public function setDefaultFile($fileId)
    {
        $this->defaultFile = (string) $fileId;
    }

    /**
     * Set the saved image shown before a replacement is selected.
     *
     * @param string $url Public image URL.
     *
     * @return void
     */
    public function setDefaultPreviewUrl($url)
    {
        $this->defaultPreviewUrl = (string) $url;
    }

    /**
     * Return the historical clear helper for compatible callers.
     *
     * @return string JavaScript helper.
     */
    public function showClearInputJavaScript()
    {
        return '<script type="text/javascript">'
            . 'function clearFileInputJS(name){var field=document.getElementById("hidden_"+name),'
            . 'preview=document.getElementById("imagepreview_"+name);'
            . 'if(field){field.value="";}if(preview){preview.removeAttribute("src");preview.hidden=true;}}'
            . '</script>';
    }

    /**
     * Render the native image picker control.
     *
     * @param string $context Historical argument retained for compatibility.
     *
     * @return string Image selector markup.
     */
    public function show($context = 'no')
    {
        unset($context);
        $fieldId = 'hidden_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $this->name);
        $previewId = 'imagepreview_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $this->name);
        $chooseId = 'chooseimage_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $this->name);
        $clearId = 'clearimage_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $this->name);
        $defaultId = '';
        $previewUrl = '';

        if ($this->defaultFile !== '') {
            $file = $this->objFile->getFile($this->defaultFile);
            if (is_array($file) && !empty($file['id'])) {
                $defaultId = (string) $file['id'];
                $previewUrl = (string) $this->objThumbnails->getThumbnail(
                    $file['id'],
                    $file['filename'],
                    $file['path']
                );
            }
        }
        if ($previewUrl === '' && $this->defaultPreviewUrl !== '') {
            $previewUrl = $this->defaultPreviewUrl;
        }

        $pickerUrl = html_entity_decode(
            $this->uri(
                array(
                    'action' => 'filepicker',
                    'policy' => 'image',
                    'target' => $fieldId,
                ),
                'filemanager'
            ),
            ENT_QUOTES,
            'UTF-8'
        );
        $chooseLabel = $this->objLanguage->languageText(
            'mod_filemanager_picker_select_image',
            'filemanager'
        );
        $clearLabel = $this->objLanguage->languageText('word_reset');
        $previewLabel = $this->objLanguage->languageText(
            'mod_filemanager_picker_image_preview',
            'filemanager'
        );
        $escape = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $script = '<script type="text/javascript">(function(){"use strict";'
            . 'var target=' . json_encode($fieldId) . ',previewId=' . json_encode($previewId)
            . ',chooseId=' . json_encode($chooseId) . ',clearId=' . json_encode($clearId)
            . ',picker=' . json_encode($pickerUrl)
            . ',baseline=' . json_encode($previewUrl)
            . ',previous=window.ChisimbaFilePickerReceive;'
            . 'window.ChisimbaFilePickerReceive=function(requestedTarget,file){'
            . 'if(requestedTarget===target&&file&&file.id&&file.url){'
            . 'var field=document.getElementById(target),preview=document.getElementById(previewId),clear=document.getElementById(clearId);'
            . 'if(field){field.value=file.id;}if(preview){preview.src=file.url;preview.hidden=false;}if(clear){clear.disabled=false;}return;}'
            . 'if(typeof previous==="function"){previous(requestedTarget,file);}};'
            . 'document.addEventListener("DOMContentLoaded",function(){'
            . 'var choose=document.getElementById(chooseId),clear=document.getElementById(clearId);'
            . 'if(choose){choose.addEventListener("click",function(){window.open(picker,"chisimbaImagePicker","width=920,height=720,resizable=yes,scrollbars=yes");});}'
            . 'if(clear){clear.addEventListener("click",function(){var field=document.getElementById(target),preview=document.getElementById(previewId);'
            . 'if(field){field.value="";}if(preview){if(baseline){preview.src=baseline;preview.hidden=false;}else{preview.removeAttribute("src");preview.hidden=true;}}clear.disabled=true;});}});'
            . '}());</script>';
        $this->appendArrayVar('headerParams', $script);

        return '<div class="chisimba-image-selector">'
            . '<input type="hidden" name="' . $escape($this->name) . '" id="' . $escape($fieldId)
            . '" value="' . $escape($defaultId) . '">'
            . '<img class="chisimba-image-selector__preview" id="' . $escape($previewId)
            . '" alt="' . $escape($previewLabel) . '"'
            . ($previewUrl === '' ? ' hidden' : ' src="' . $escape($previewUrl) . '"') . '>'
            . '<div class="chisimba-image-selector__actions">'
            . '<button class="button" type="button" id="' . $escape($chooseId) . '">' . $escape($chooseLabel) . '</button>'
            . '<button class="button chisimba-button-danger" type="button" id="' . $escape($clearId) . '"'
            . ($defaultId === '' ? ' disabled' : '') . '>' . $escape($clearLabel) . '</button>'
            . '</div></div>';
    }
}
