<?php
/** Metadata derived from an authorised page and the configured site root, never request Host. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class pagemetadata extends ChisimbaObject
{
    public function init(){}
    public function build($title,$description,array $destination=[],$image='')
    {
        $config=$this->getObject('altconfig','config');$root=$config->getSiteRoot();
        $plain=static fn($text)=>trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags((string)$text),ENT_QUOTES,'UTF-8')));
        $title=$plain($title);$description=mb_substr($plain($description),0,240);
        $result=['pageTitle'=>$title.' — '.$config->getSiteName(),'og_title'=>$title,'og_content'=>$description,'pageCanonical'=>$root.($destination?'index.php?'.http_build_query($destination,'','&',PHP_QUERY_RFC3986):'')];
        if($image===''){$image=(string)$this->getObject('dbsysconfig','sysconfig')->getValue('SITE_SHARE_IMAGE','ui','');if($image!==''&&!str_contains($image,':')&&!str_starts_with($image,'/')&&!str_contains($image,'..'))$image=$root.$image;}
        if($image!==''&&preg_match('~^https?://~i',$image))$result['og_image']=$image;
        return $result;
    }
}
