<?php
/** Isolated filesystem contracts; no account or live file records are modified. Run with GD. */
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($severity,$message){throw new ErrorException($message,0,$severity);});
class ChisimbaObject {
    public static array $services=[];
    public function getObject($name,$module=''){return self::$services[$name];}
    public function newObject($name,$module=''){return new RasterFixture();}
}
class RasterFixture {
    public bool $canCreateFromSouce=true;
    public static int $stores=0;
    public static int $failAt=0;
    private $image;
    public function setImg($path){$this->image=imagecreatefrompng($path);return true;}
    public function resize($w,$h,$crop){$this->image=imagescale($this->image,$w,$h);}
    public function store($path){return ++self::$stores===self::$failAt?false:imagepng($this->image,$path);}
}
require dirname(__DIR__).'/classes/profilephoto_class_inc.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$base=sys_get_temp_dir().'/chisimba-profile-test-'.bin2hex(random_bytes(6));
mkdir($base.'/users/fixture',0700,true);mkdir($base.'/user_images');
$source=$base.'/users/fixture/source.png';
$im=imagecreatetruecolor(150,120);imagepng($im,$source);
$registry=&ChisimbaObject::$services;
$registry['user']=new class {public bool $logged=true;function userId(){return 'fixture';}function isLoggedIn(){return $this->logged;}};
$registry['altconfig']=new class($base) {function __construct(private string $base){}function getsiteRootPath(){return $this->base;}function getcontentBasePath(){return $this->base.'/';}};
$registry['dbfile']=new class($source) {
    public array $record=['userid'=>'fixture','filefolder'=>'users/fixture'];
    function __construct(public $source){}function getFileInfo($id){return $this->record;}function getFullFilePath($id){return $this->source;}
};
$registry['filereadpolicy']=new class {public bool $allow=true;function mayRead($file){return $this->allow;}};
$registry['useradmin_model2']=new class($base) {function __construct(private string $base){}function removeUserImage($uid){foreach(['.png','_small.png','.jpg','_small.jpg'] as $suffix){$p=$this->base.'/user_images/'.$uid.$suffix;if(is_file($p))unlink($p);}}};
$photo=new profilephoto();$full=$base.'/user_images/fixture.png';$small=$base.'/user_images/fixture_small.png';
function denied($photo,$code){try{$photo->change('id');throw new RuntimeException('unexpected success');}catch(RuntimeException $e){check($e->getMessage()===$code,'Expected '.$code.' got '.$e->getMessage());}}
try {
    $photo->change('id');check(getimagesize($full)[0]===100 && getimagesize($small)[0]===35,'derivatives');
    $before=[hash_file('sha256',$full),hash_file('sha256',$small)];
    RasterFixture::$failAt=RasterFixture::$stores+2;
    denied($photo,'profile_failed');check($before===[hash_file('sha256',$full),hash_file('sha256',$small)],'failed second derivative preserves both originals');
    check(count(glob($base.'/user_images/.profile-*'))===0,'staging cleaned');
    $registry['dbfile']->record['userid']='outsider';denied($photo,'profile_image_access');
    $registry['dbfile']->record['userid']='fixture';$registry['dbfile']->record['filefolder']='contexts/private';denied($photo,'profile_image_access');
    $registry['dbfile']->record['filefolder']='users/fixture';$registry['filereadpolicy']->allow=false;denied($photo,'profile_image_access');$registry['filereadpolicy']->allow=true;
    $registry['dbfile']->source=false;denied($photo,'profile_image_access');
    $registry['dbfile']->source=$full;denied($photo,'profile_image_access');
    file_put_contents($base.'/users/fixture/bad.png','not an image');$registry['dbfile']->source=$base.'/users/fixture/bad.png';denied($photo,'profile_image_invalid');
    $registry['user']->logged=false;denied($photo,'profile_denied');$registry['user']->logged=true;
    $photo->reset();check(!is_file($full)&&!is_file($small),'reset removes both derivatives');
    echo "PASS photo ownership, read policy, path confinement, malformed files, atomic staging failure, cleanup and reset\n";
} finally {
    foreach(glob($base.'/user_images/*') as $p)unlink($p);
    foreach(glob($base.'/user_images/.*') as $p)if(is_file($p))unlink($p);
    foreach(glob($base.'/users/fixture/*') as $p)unlink($p);
    rmdir($base.'/users/fixture');rmdir($base.'/users');rmdir($base.'/user_images');rmdir($base);
}
