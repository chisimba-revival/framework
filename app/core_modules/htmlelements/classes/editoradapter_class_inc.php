<?php

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Neutral server-side rendering adapter for Chisimba rich-text fields.
 *
 * The historical htmlarea class remains the public compatibility boundary.
 * Vendor-specific rendering is isolated here so that the editor library can
 * later be replaced without changing application modules.
 */
class editoradapter extends ChisimbaObject
{
    /**
     * Render an editor field and return its header assets and body markup.
     *
     * @param array $options
     * @return array{headers: array<int,string>, html: string}
     */
    public function render($options = array())
    {
        $name = isset($options['name']) ? (string) $options['name'] : '';
        $id = isset($options['id']) && $options['id'] !== ''
            ? (string) $options['id']
            : $name;
        $value = isset($options['value']) ? (string) $options['value'] : '';
        $cssClass = isset($options['cssClass']) && $options['cssClass'] !== ''
            ? (string) $options['cssClass']
            : 'textarea';
        $height = isset($options['height']) ? (string) $options['height'] : '400px';
        $width = isset($options['width']) ? (string) $options['width'] : '100%';
        $toolbar = isset($options['toolbar']) ? (string) $options['toolbar'] : 'advanced';
        $siteRoot = isset($options['siteRoot']) ? (string) $options['siteRoot'] : '';
        $sitePath = isset($options['sitePath']) ? (string) $options['sitePath'] : '';
        $ckeditorUri = isset($options['ckeditorUri']) ? (string) $options['ckeditorUri'] : '';
        $ckeditorAjaxUri = isset($options['ckeditorAjaxUri'])
            ? (string) $options['ckeditorAjaxUri']
            : '';
        $disableSpellChecker = !empty($options['disableSpellChecker']);

        $headers = array();

        // Neutral browser-side editor API. Application modules must use this
        // object rather than addressing CKEditor, FCKeditor, or TinyMCE.
        $headers[] = '<script type="text/javascript">'
            . '(function(w){'
            . 'if(w.ChisimbaEditor){return;}'
            . 'function instance(id){'
            . 'if(w.CKEDITOR&&w.CKEDITOR.instances){return w.CKEDITOR.instances[id]||null;}'
            . 'return null;'
            . '}'
            . 'w.ChisimbaEditor={'
            . 'get:function(id){return instance(id);},'
            . 'getData:function(id){var e=instance(id);if(e&&typeof e.getData==="function"){return e.getData();}var n=document.getElementById(id);return n&&typeof n.value!=="undefined"?n.value:"";},'
            . 'setData:function(id,value){var e=instance(id);if(e&&typeof e.setData==="function"){e.setData(value);return true;}var n=document.getElementById(id);if(n&&typeof n.value!=="undefined"){n.value=value;return true;}return false;},'
            . 'sync:function(id){var e=instance(id);if(e&&typeof e.updateElement==="function"){e.updateElement();return true;}return !!document.getElementById(id);},'
            . 'focus:function(id){var e=instance(id);if(e&&typeof e.focus==="function"){e.focus();return true;}var n=document.getElementById(id);if(n&&typeof n.focus==="function"){n.focus();return true;}return false;},'
            . 'preview:function(id){var e=instance(id);if(e&&typeof e.execCommand==="function"){try{e.execCommand("preview");return true;}catch(ignore){}}var p=w.open("","chisimba_editor_preview");if(!p){return false;}p.document.open();p.document.write("<!doctype html><html><head><title>Preview</title></head><body>"+this.getData(id)+"</body></html>");p.document.close();return true;}'
            . '};'
            . '}(window));'
            . '</script>';

        if ($ckeditorUri !== '') {
            $headers[] = '<script src="'
                . htmlspecialchars($ckeditorUri, ENT_QUOTES, 'UTF-8')
                . '" type="text/javascript"></script>';
        }

        // Retained temporarily for exact compatibility with the historical
        // CKEditor package. It can be removed when the vendor is replaced.
        if ($ckeditorAjaxUri !== '') {
            $headers[] = '<script src="'
                . htmlspecialchars($ckeditorAjaxUri, ENT_QUOTES, 'UTF-8')
                . '" type="text/javascript"></script>';
        }

        $textarea = '<textarea name="'
            . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
            . '" class="'
            . htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8')
            . '" id="'
            . htmlspecialchars($id, ENT_QUOTES, 'UTF-8')
            . '">'
            . htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            . '</textarea>';

        $config = array(
            'filebrowserBrowseUrl' => $siteRoot
                . '?module=filemanager&action=fcklink&context=yes&loadwindow=yes',
            'filebrowserImageBrowseUrl' => $siteRoot
                . '?module=filemanager&action=fckimage&context=yes&loadwindow=yes&scrollbars=yes',
            'filebrowserFlashBrowseUrl' => $siteRoot
                . '?module=filemanager&action=fckflash&context=yes&loadwindow=yes',
            'height' => $height,
            'width' => $width,
            'filebrowserWindowWidth' => '80%',
            'filebrowserWindowHeight' => '100%',
            'disableNativeSpellChecker' => $disableSpellChecker,
            'scayt_autoStartup' => !$disableSpellChecker,
            'contentsCss' => $sitePath
                . '/core_modules/ckeditor/resources/ckeditor/chisimba.css',
            'toolbar' => $toolbar,
        );

        $script = '<script type="text/javascript">'
            . '(function(){'
            . 'if(typeof CKEDITOR==="undefined"){return;}'
            . 'CKEDITOR.replace('
            . json_encode($name)
            . ','
            . json_encode($config)
            . ');'
            . '}());'
            . '</script>';

        return array(
            'headers' => $headers,
            'html' => '<style>'
                . '.chisimba-editor-adapter{display:block;width:100%;max-width:100%;box-sizing:border-box;}'
                . '.chisimba-editor-adapter textarea{width:100%;max-width:100%;box-sizing:border-box;}'
                . '.chisimba-editor-adapter .cke,.chisimba-editor-adapter .cke_chrome{width:100% !important;max-width:100%;box-sizing:border-box;}'
                . '</style>'
                . '<div class="chisimba-editor-adapter">'
                . $textarea
                . $script
                . '</div>',
        );
    }
}
?>
