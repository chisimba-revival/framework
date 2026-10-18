<?php
/** Real register-file discovery and native publication into a disposable catalogue. */
if(PHP_SAPI!=='cli')exit(2);
$app=realpath($argv[1]??dirname(__DIR__,2).'/app');
$packages=realpath($argv[2]??dirname(__DIR__,3).'/modules');
$GLOBALS['kewl_entry_point_run']=true;
#[AllowDynamicProperties]
class ChisimbaObject { public $objects=array(); public function getObject($name,$module=null){return $this->objects[$name];} }
class customException extends Exception{}
class Paths { public $root,$modules; public function getSiteRootPath(){return $this->root.'/';} public function getModulePath(){return $this->modules;} }
class Language {public function abstractText($s){return $s;}}
class Reconcile {public $ids;public function reconcileAvailableModules($ids){$this->ids=$ids;return array();}}
require $app.'/core_modules/modulecatalogue/classes/modulefile_class_inc.php';
require $app.'/core_modules/modulecatalogue/classes/catalogueconfig_class_inc.php';
set_error_handler(static function($severity,$message){throw new ErrorException($message,0,$severity);});
$base=sys_get_temp_dir().'/chisimba-catalogue-discovery-'.bin2hex(random_bytes(8));mkdir($base,0700);mkdir($base.'/config',0700);
try{
 $source=new Paths();$source->root=$app;$source->modules=$packages;
 $files=new modulefile();$files->objects=array('altconfig'=>$source);$files->init();
 $names=$files->getLocalModuleList();if(count($names)<10)throw new RuntimeException('Source discovery unexpectedly small');
 $destination=new Paths();$destination->root=$base;$destination->modules=$packages;
 $reconcile=new Reconcile();$cat=new catalogueconfig();$cat->objects=array('altconfig'=>$destination,'modulefile'=>$files,'modules'=>$reconcile,'language'=>new Language());$cat->objEngine=(object)array('version'=>'fixture');$cat->init();
 $result=$cat->writeCatalogue();
 if($result['discovered']!==count($names)||count($reconcile->ids)!==count($names)||count($cat->getModulelist('all'))!==count($names))throw new RuntimeException('Real discovery publication/reconciliation mismatch');
 $source->modules=$base.'/missing';$failed=false;try{$files->getLocalModuleList();}catch(RuntimeException $e){$failed=true;}if(!$failed)throw new RuntimeException('Missing module tree accepted');
 mkdir($base.'/packages');mkdir($base.'/packages/empty');file_put_contents($base.'/packages/empty/register.conf','');$source->modules=$base.'/packages';$failed=false;try{$files->getLocalModuleList();}catch(RuntimeException $e){$failed=true;}if(!$failed)throw new RuntimeException('Empty register accepted');
 echo 'PASS: '.count($names)." real source registrations published/read in disposable catalogue; missing/empty discovery rejected; database reconciliation doubled\n";
}finally{restore_error_handler();$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $e){if($e->isDir())rmdir($e->getPathname());else unlink($e->getPathname());}rmdir($base);}
