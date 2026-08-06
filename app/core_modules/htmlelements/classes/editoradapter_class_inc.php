<?php

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Neutral server-side rendering adapter for Chisimba rich-text fields.
 *
 * htmlarea remains the application-facing compatibility boundary. TinyMCE is
 * isolated here so application modules do not depend on a vendor API.
 */
class editoradapter extends ChisimbaObject
{
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
        $tinymceUri = isset($options['tinymceUri'])
            ? (string) $options['tinymceUri']
            : '';

        $headers = array();

        $headers[] = '<script type="text/javascript">'
            . '(function(w){'
            . 'function instance(id){'
            . 'return w.tinymce&&typeof w.tinymce.get==="function"'
            . '?w.tinymce.get(id):null;'
            . '}'
            . 'w.ChisimbaEditor={'
            . 'activePicker:null,'
            . 'get:function(id){return instance(id);},'
            . 'getData:function(id){'
            . 'var e=instance(id);'
            . 'if(e&&typeof e.getContent==="function"){return e.getContent();}'
            . 'var n=document.getElementById(id);'
            . 'return n&&typeof n.value!=="undefined"?n.value:"";'
            . '},'
            . 'setData:function(id,value){'
            . 'var e=instance(id);'
            . 'if(e&&typeof e.setContent==="function"){e.setContent(value||"");return true;}'
            . 'var n=document.getElementById(id);'
            . 'if(n&&typeof n.value!=="undefined"){n.value=value||"";return true;}'
            . 'return false;'
            . '},'
            . 'getSelection:function(id){'
            . 'var e=instance(id);'
            . 'if(e&&e.selection&&typeof e.selection.getContent==="function")'
            . '{return e.selection.getContent({format:"html"});}'
            . 'var n=document.getElementById(id);'
            . 'if(n&&typeof n.value!=="undefined"&&typeof n.selectionStart==="number")'
            . '{return n.value.substring(n.selectionStart,n.selectionEnd);}'
            . 'return "";'
            . '},'
            . 'insertHtml:function(id,html){'
            . 'var value=html||"";'
            . 'var e=instance(id);'
            . 'if(e&&typeof e.insertContent==="function"){e.insertContent(value);return true;}'
            . 'var n=document.getElementById(id);'
            . 'if(n&&typeof n.value!=="undefined")'
            . '{var start=typeof n.selectionStart==="number"?n.selectionStart:n.value.length;'
            . 'var end=typeof n.selectionEnd==="number"?n.selectionEnd:start;'
            . 'n.value=n.value.substring(0,start)+value+n.value.substring(end);'
            . 'if(typeof n.setSelectionRange==="function")'
            . '{n.setSelectionRange(start+value.length,start+value.length);}'
            . 'return true;}'
            . 'return false;'
            . '},'
            . 'sync:function(id){'
            . 'var e=instance(id);'
            . 'if(e&&typeof e.save==="function"){e.save();return true;}'
            . 'return !!document.getElementById(id);'
            . '},'
            . 'focus:function(id){'
            . 'var e=instance(id);'
            . 'if(e&&typeof e.focus==="function"){e.focus();return true;}'
            . 'var n=document.getElementById(id);'
            . 'if(n&&typeof n.focus==="function"){n.focus();return true;}'
            . 'return false;'
            . '},'
            . 'preview:function(id){'
            . 'var p=w.open("","chisimba_editor_preview");'
            . 'if(!p){return false;}'
            . 'p.document.open();'
            . 'p.document.write("<!doctype html><html><head><title>Preview</title></head><body>"+this.getData(id)+"</body></html>");'
            . 'p.document.close();'
            . 'return true;'
            . '},'
            . 'beginFilePick:function(callback){this.activePicker=callback;},'
            . 'selectFile:function(url,width,height){'
            . 'if(typeof this.activePicker!=="function"){return false;}'
            . 'var callback=this.activePicker;'
            . 'this.activePicker=null;'
            . 'callback(url,{width:width||undefined,height:height||undefined});'
            . 'return true;'
            . '}'
            . '};'
            . '}(window));'
            . '</script>';

        if ($tinymceUri !== '') {
            $headers[] = '<script src="'
                . htmlspecialchars($tinymceUri, ENT_QUOTES, 'UTF-8')
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

        $heightNumber = (int) preg_replace('/[^0-9]/', '', $height);
        if ($heightNumber < 200) {
            $heightNumber = 400;
        }

        $toolbarValue = strtolower($toolbar) === 'simple'
            ? 'undo redo | bold italic | bullist numlist | link image | removeformat'
            : 'undo redo | blocks | bold italic underline | '
                . 'alignleft aligncenter alignright | bullist numlist | '
                . 'link image table | code preview | removeformat';

        $pickerUrl = $siteRoot
            . '?module=filemanager&action=imagepicker'
            . '&context=yes&loadwindow=yes&scrollbars=yes';

        $config = array(
            'selector' => '#' . $id,
            'height' => $heightNumber,
            'width' => $width,
            'license_key' => 'gpl',
            'menubar' => false,
            'promotion' => false,
            'branding' => false,
            'plugins' => 'link image lists table code preview',
            'toolbar' => $toolbarValue,
            'browser_spellcheck' => true,
            'convert_urls' => false,
            'relative_urls' => false,
            'remove_script_host' => false,
            'file_picker_types' => 'image',
        );

        $script = '<script type="text/javascript">'
            . '(function(){'
            . 'function start(){'
            . 'if(typeof tinymce==="undefined"){'
            . 'console.error("Chisimba editor: TinyMCE failed to load.");'
            . 'return;'
            . '}'
            . 'var config=' . json_encode($config) . ';'
            . '/* CHISIMBA_TINYMCE_FULLSCREEN */'
            . 'if(Array.isArray(config.plugins)){' 
            . 'if(config.plugins.indexOf("fullscreen")===-1){config.plugins.push("fullscreen");}'
            . '}else if(!/(^|\\s)fullscreen(\\s|$)/.test(config.plugins||"")){' 
            . 'config.plugins=((config.plugins||"")+" fullscreen").trim();}'
            . 'if(Array.isArray(config.toolbar)){' 
            . 'if(config.toolbar.indexOf("fullscreen")===-1){config.toolbar.push("fullscreen");}'
            . '}else if(!/(^|\\s)fullscreen(\\s|$)/.test(config.toolbar||"")){' 
            . 'config.toolbar=((config.toolbar||"")+" | fullscreen").trim();}'
            . 'config.file_picker_callback=function(callback,value,meta){'
            . 'if(!window.ChisimbaEditor){return;}'
            . 'window.ChisimbaEditor.beginFilePick(callback);'
            . 'window.open('
            . json_encode($pickerUrl)
            . ',"chisimba_image_picker",'
            . '"width=1000,height=720,resizable=yes,scrollbars=yes");'
            . '};'
            . 'tinymce.init(config).catch(function(error){'
            . 'console.error("Chisimba editor initialization failed.",error);'
            . '});'
            . '}'
            . 'if(document.readyState==="loading"){'
            . 'document.addEventListener("DOMContentLoaded",start,{once:true});'
            . '}else{start();}'
            . '}());'
            . '</script>';

        return array(
            'headers' => $headers,
            'html' => '<style>'
                . '.chisimba-editor-adapter{display:block;width:100%;max-width:100%;box-sizing:border-box;}'
                . '.chisimba-editor-adapter textarea{width:100%;max-width:100%;box-sizing:border-box;}'
                . '.chisimba-editor-adapter .tox-tinymce{width:100% !important;max-width:100%;box-sizing:border-box;}'
                . '</style>'
                . '<div class="chisimba-editor-adapter">'
                . $textarea
                . $script
                . '</div>',
        );
    }
}
?>
