<?php
/** Compact, aspect-preserving email images using shared resizing and protected delivery. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class emailthumbnail extends ChisimbaObject
{
 public function init(){$this->loadClass('filedelivery','filemanager');}
 public function forFile($id){
  if(!is_string($id)||!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id))return null;
  $file=$this->getObject('dbfile','filemanager')->getFile($id);
  if(!$file||!$this->getObject('filereadpolicy','filemanager')->mayRead($file))return null;
  $config=$this->getObject('altconfig','config');$base=$config->getcontentBasePath();
  $source=filedelivery::resolve($base,$file['path']??'');
  if(!$source)return null;
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($source);
  if(!in_array($mime,['image/jpeg','image/png','image/gif','image/webp'],true))return null;
  $size=getimagesize($source);
  if(!$size||$size[0]*$size[1]>20000000)return null;
  $scale=min(1,600/$size[0],600/$size[1]);$width=max(1,(int)round($size[0]*$scale));$height=max(1,(int)round($size[1]*$scale));
  // The standard_ namespace is already supported by the file-derivative guard.
  $relative='filemanager_thumbnails/large/standard_'.$id.'.jpg';$target=rtrim($base,'/').'/'.$relative;
  $existing=is_file($target)?getimagesize($target):false;
  if(!$existing||$existing[0]!==$width||$existing[1]!==$height||filemtime($target)<filemtime($source)){
   if(!$this->getObject('mkdir','files')->mkdirs(dirname($target)))return null;
   $resize=$this->getObject('imageresize','files');
   if(!$resize->setImg($source))return null;
   // Explicit dimensions avoid the legacy resize method's broken aspect-ratio option.
   $resize->resize($width,$height,false);
   $temporary=tempnam(dirname($target),'email-');if($temporary===false)return null;
   $jpeg=$temporary.'.jpg';
   try{if(!$resize->store($jpeg)||!rename($jpeg,$target))return null;}
   finally{if(is_file($temporary))unlink($temporary);if(is_file($jpeg))unlink($jpeg);}
  }
  return ['url'=>rtrim($config->getSiteRoot(),'/').'/index.php?'.http_build_query(['module'=>'filemanager','action'=>'filederivative','path'=>$relative]),'width'=>$width,'height'=>$height];
 }
}
