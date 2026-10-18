<?php
/** Configurable site links; labels are administrator-authored content. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class sitefooter extends ChisimbaObject
{
    public function init(){}
    public function show()
    {
        $raw=$this->getObject('dbsysconfig','sysconfig')->getValue('SITE_FOOTER_LINKS','toolbar','[]');$items=json_decode((string)$raw,true);if(!is_array($items))return '';
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$html='';$root=$this->getObject('altconfig','config')->getSiteRoot();
        foreach(array_slice($items,0,20) as $item){
            if(!is_array($item)||!is_string($item['label']??null)||!is_string($item['url']??null))continue;$url=$item['url'];
            if(str_starts_with($url,'index.php?'))$url=$root.$url;
            $parts=parse_url($url);if(!$parts||!in_array($parts['scheme']??'',['http','https'],true)||empty($parts['host'])||isset($parts['user'])||isset($parts['pass']))continue;
            $html.='<a href="'.$e($url).'" rel="noopener noreferrer">'.$this->getObject('iconservice','ui')->render($item['icon']??'external-link',['decorative'=>true]).'<span>'.$e($item['label']).'</span></a>';
        }
        return $html===''?'':'<nav class="chisimba-site-footer__links" aria-label="'.$e($this->getObject('language','language')->code2Txt('mod_toolbar_footer_links','toolbar')).'">'.$html.'</nav>';
    }
}
