<?php
/** Conservative rich-text boundary for authored and imported HTML. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class richtextsanitizer extends ChisimbaObject
{
    public function init() {}
    public function cleanHtml($html)
    {
        if (!is_string($html) || $html==='') return '';
        $doc=new DOMDocument('1.0','UTF-8');
        $previous=libxml_use_internal_errors(true);
        try { $doc->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>',LIBXML_NONET); }
        finally { libxml_clear_errors();libxml_use_internal_errors($previous); }
        $body=$doc->getElementsByTagName('body')->item(0);
        return $body ? $this->children($body) : '';
    }
    private function children($node)
    {
        $out='';
        foreach($node->childNodes as $child) $out.=$this->render($child);
        return $out;
    }
    private function render($node)
    {
        if($node instanceof DOMText) return htmlspecialchars($node->data,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        if(!($node instanceof DOMElement))return '';
        $tag=strtolower($node->tagName);
        if(in_array($tag,array('script','style','iframe','object','embed','svg','math','template','form','input','button','textarea','select','link','meta','base'),true))return '';
        if(!in_array($tag,array('p','br','hr','h2','h3','h4','h5','h6','strong','b','em','i','u','s','blockquote','ul','ol','li','pre','code','a','img','figure','figcaption','sub','sup'),true))return $this->children($node);
        $attrs='';
        foreach(array('title','href','src','width','height','alt') as $name) {
            if(!$node->hasAttribute($name))continue;
            $v=$node->getAttribute($name);
            if($name==='href' && ($tag!=='a'||!self::safeUrl($v,true)))continue;
            if($name==='src' && ($tag!=='img'||!self::safeUrl($v,false)))continue;
            if($name==='alt' && $tag!=='img')continue;
            if(in_array($name,array('width','height'),true) && ($tag!=='img'||!preg_match('/^[1-9][0-9]{0,3}$/',$v)))continue;
            $attrs.=' '.$name.'="'.htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'"';
        }
        if($tag==='img') {
            if(!$node->hasAttribute('src')||!self::safeUrl($node->getAttribute('src'),false))return '';
            if(!$node->hasAttribute('alt'))$attrs.=' alt=""';
        }
        if(in_array($tag,array('br','hr','img'),true))return '<'.$tag.$attrs.'>';
        return '<'.$tag.$attrs.'>'.$this->children($node).'</'.$tag.'>';
    }
    public static function safeUrl($url,$link=false)
    {
        if($url===''||preg_match('/[\x00-\x20\x7f\\\\]/',$url))return false;
        if(str_starts_with($url,'//'))return false;
        if(preg_match('~^https?://~i',$url))return filter_var($url,FILTER_VALIDATE_URL)!==false && !isset(parse_url($url)['user']);
        if($link && preg_match('~^mailto:[^?]+$~i',$url))return filter_var(substr($url,7),FILTER_VALIDATE_EMAIL)!==false;
        return !preg_match('~^[^/?#]*:~',$url);
    }
}
