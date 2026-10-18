<?php
/** Run from the installed root with CHISIMBA_PROFILE_TEST=1. Synthetic identity, real File Manager/PDO/GD. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_PROFILE_TEST')!=='1')exit("Set CHISIMBA_PROFILE_TEST=1 locally.\n");
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(static function($n,$s){if(error_reporting()&$n)throw new RuntimeException($s);return false;});
$engine->getObject('profilephoto','userdetails');
class SyntheticProfilePhoto extends profilephoto {
    public $fixtureUser;
    public $fixtureEngine;
    public function getObject($name,$moduleName=''){return $name==='user'?$this->fixtureUser:$this->fixtureEngine->getObject($name,$moduleName);}
    public function newObject($name,$moduleName=''){return $this->fixtureEngine->newObject($name,$moduleName);}
}
$uid='qa-profile-'.bin2hex(random_bytes(6));
$photo=new SyntheticProfilePhoto();$photo->fixtureEngine=$engine;$photo->fixtureUser=new class($uid){function __construct(private string $uid){}function userId(){return $this->uid;}function isLoggedIn(){return true;}};
$config=$engine->getObject('altconfig','config');$files=$engine->getObject('dbfile','filemanager');$folders=$engine->getObject('dbfolder','filemanager');
$folder='users/'.$uid;$directory=$config->getcontentBasePath().$folder;$destination=rtrim($config->getsiteRootPath(),'/').'/user_images/'.$uid;
mkdir($directory,0700,true);$image=imagecreatetruecolor(140,120);imagepng($image,$directory.'/photo.png');
try {
    try {$files->transaction(function()use($files,$folders,$folder,$uid,$photo,$destination){
        $folders->insert(['folderpath'=>$folder,'folderlevel'=>2,'access'=>'public']);
        $id=$files->insert(['userid'=>$uid,'creatorid'=>$uid,'modifierid'=>$uid,'filename'=>'photo.png','datatype'=>'png','path'=>$folder.'/photo.png','filefolder'=>$folder,'mimetype'=>'image/png','filesize'=>100,'category'=>'images','access'=>'public','visibility'=>'visible']);
        $photo->change($id);
        foreach([''=>100,'_small'=>35] as $suffix=>$size){$info=getimagesize($destination.$suffix.'.png');if($info[0]!==$size||$info[1]!==$size||$info[2]!==IMAGETYPE_PNG)throw new RuntimeException('Real raster derivative failed');}
        $photo->reset();if(is_file($destination.'.png')||is_file($destination.'_small.png'))throw new RuntimeException('Real reset failed');
        throw new LogicException('rollback-fixtures');
    });}catch(LogicException $e){if($e->getMessage()!=='rollback-fixtures')throw $e;}
    if($files->selectPrepared('SELECT id FROM tbl_files WHERE userid=?',[$uid])||$folders->getFolderByPath($folder))throw new RuntimeException('Rollback failed');
    echo "PASS real PDO file lookup, read policy, shared GD derivatives, shared reset and fixture rollback\n";
}finally{
    foreach(['.png','_small.png'] as $suffix)if(is_file($destination.$suffix))unlink($destination.$suffix);
    unlink($directory.'/photo.png');rmdir($directory);
}
