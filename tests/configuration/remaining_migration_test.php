<?php
/** Native-only migration fixtures: no engine, installed settings or database. */
if (PHP_SAPI !== 'cli') { exit(2); }
$app=realpath($argv[1] ?? dirname(__DIR__,2).'/app');
$GLOBALS['kewl_entry_point_run']=true;
#[AllowDynamicProperties]
class ChisimbaObject { public $objects=array(); public function getObject($name,$module=null) { return $this->objects[$name]; } }
class customException extends Exception {}
class FixturePaths {
    public $base;
    public function __construct($base) { $this->base=$base; }
    public function getcontentBasePath() { return $this->base.'/content/'; }
    public function getsiteRootPath() { return $this->base.'/'; }
}
class FixtureIdentity {
    public $id='alice'; public $admin=false; public $logged=true;
    public function userId() { return $this->id; }
    public function isLoggedIn() { return $this->logged; }
    public function isAdmin() { return $this->admin; }
    public function getUserId($name) { return $name; }
}
class FixtureLanguage { public function abstractText($value) { return $value; } public function languageText($key,$module=null) { return $key; } }
class FixtureModuleFiles {
    public $records=array(); public $duringRead;
    public function getLocalModuleList() { return array_keys($this->records); }
    public function findregisterfile($module) { return $module; }
    public function readRegisterFile($file) { if ($this->duringRead) { ($this->duringRead)(); $this->duringRead=null; } return $this->records[$file]; }
}
class FixtureReconcile {
    public $calls=array(); public $path;
    public function reconcileAvailableModules($ids) {
        if (!is_file($this->path)) { throw new RuntimeException('Reconcile ran before publication'); }
        $this->calls[]=$ids; return array('old-fixture');
    }
}
require $app.'/core_modules/config/classes/configurationdocument.php';
require $app.'/core_modules/config/classes/ini_class_inc.php';
require $app.'/core_modules/userparamsadmin/classes/dbuserparamsadmin_class_inc.php';
require $app.'/core_modules/modulecatalogue/classes/catalogueconfig_class_inc.php';
set_error_handler(static function($severity,$message) { throw new ErrorException($message,0,$severity); });
$base=sys_get_temp_dir().'/chisimba-migration-'.bin2hex(random_bytes(8)); mkdir($base,0700); mkdir($base.'/config',0700);
$count=0;
function check($ok,$label) { global $count; if (!$ok) { throw new RuntimeException('FAIL: '.$label); } ++$count; }
function rejects($fn,$label) { try { $fn(); } catch(RuntimeException $e) { check(true,$label); return; } check(false,$label); }
function preferences($paths,$user) { $c=new dbuserparamsadmin(); $c->objects=array('altconfig'=>$paths,'user'=>$user,'language'=>new FixtureLanguage()); $c->init(); return $c; }
try {
    $codec=new ChisimbaConfigurationIni();
    $legacy="[Settings]\ntruth=On\nfalsehood=Off\nquoted=\"On\"\nzero=0\nleading=007\nempty=\"\"\nlist=\"one, two\"\ncompact=\"one,two\"\n";
    $tree=$codec->parse($legacy)[0];
    check($tree->toArray()['root']['Settings']===array('truth'=>'1','falsehood'=>'','quoted'=>'On','zero'=>'0','leading'=>'007','empty'=>'','list'=>array('one','two'),'compact'=>'one,two'),'legacy INI semantic fixture');
    $values=array('comma'=>'one, two','quote'=>'a "quote"','slashes'=>'C:\path\\more','dollar'=>'${UNDEFINED_CONFIG_FIXTURE}','unicode'=>'Café 日本語','newline'=>"a\nb",'empty'=>'','false'=>'FALSE','leading'=>'007','duplicate'=>array('one','two'));
    $root=ChisimbaConfigurationNode::fromArray(array('Settings'=>$values));
    $rendered=$codec->render($root);
    check($codec->parse($rendered)[0]->toArray()['root']['Settings']===$values,'native INI lossless literal values');
    check($codec->parse("[Settings]\nempty=\ntruth=true\nnull=NULL\n") [0]->toArray()['root']['Settings']===array('empty'=>'','truth'=>'1','null'=>''),'legacy empty and boolean values');
    foreach(array("[Settings]\nbad","[Settings]\nx=\"unterminated", "[Settings]\nx[bad]=y") as $bad) { rejects(fn()=>$codec->parse($bad),'bad INI refused'); }
    rejects(fn()=>$codec->render(ChisimbaConfigurationNode::fromArray(array('Settings'=>array("bad\nkey"=>'x')))),'INI key injection rejected');
    $paths=new FixturePaths($base); $user=new FixtureIdentity(); $p=preferences($paths,$user);
    check($p->getValue('canvas')===null && !is_dir($base.'/content'),'read absent prefs has no filesystem side effects');
    check($p->setItem('canvas','earth'),'create preference');
    check($p->setItem('literal','a, b; "c" & 日本語'),'save punctuation');
    check($p->writeProperties('add','alice','accepted_blog_terms',1),'add preference');
    check($p->getValue('canvas')==='earth' && $p->getValue('literal')==='a, b; "c" & 日本語','add preserves unrelated preferences');
    check(preferences($paths,$user)->getValue('accepted_blog_terms')==='1','fresh reader sees new preference');
    $file=$base.'/content/users/alice/userconfig_properties.ini'; $bytes=file_get_contents($file);
    check((fileperms($file)&0777)===0600,'new user preferences private');
    $stale=preferences($paths,$user); $revision=$stale->getRevision();
    check($p->setItem('canvas','new'),'newer preference write');
    check(!$stale->setItem('canvas','lost'),'stale preference write refused');
    $otherRequest=preferences($paths,$user);
    check(!$otherRequest->writeProperties('edit','alice','canvas','lost',$revision),'stale editor revision refused');
    check($p->delete('accepted_blog_terms') && $p->getValue('canvas')==='new','delete retains unrelated values');
    check(!$p->writeConfig(array('x'=>'y'),'XML'),'unsupported preference format refused');
    $p->setUserId('bob'); check($p->readConfig()===false && !$p->setItem('secret','x'),'cross-user read/write denied');
    $user->admin=true; check($p->setItem('canvas','blue'),'admin can write selected user');
    check(preferences($paths,$user)->getValue('canvas')==='new','selected-user save does not write logged-in user');
    $p->setUserId('../alice'); check($p->readConfig()===false && !$p->delete('canvas'),'path traversal denied');
    $user->admin=false; $user->logged=false; check(preferences($paths,$user)->readConfig()===false,'anonymous preferences denied'); $user->logged=true;
    $helper=new ini(); $helper->objects=array('altconfig'=>$paths); $helper->init();
    check($helper->createConfig('Custom',array('one'=>'a, b'),$base,'generic.ini'),'generic helper creates named section');
    check($helper->readConfig(false,'IniFile',$base,'generic.ini')->toArray()['root']['Custom']['one']==='a, b','generic helper respects path/section');
    check($helper->setItem('one','next','Custom') && $helper->getItem('one',null,'Custom')==='next','generic setter writes');
    check(!$helper->writeConfig('Custom',array('one'=>'bad'),'unsupported',$base,'generic.ini'),'generic unsupported format fails');
    check($helper->createAdmConfig(array('name'=>'fixture','url'=>'https://example.invalid/?a=1&b=2','email'=>'a@example.invalid')),'ADM create');
    check($helper->createAdmConfig(array('name'=>'second','url'=>'https://example.invalid/second','email'=>'b@example.invalid')),'ADM append');
    $adm=new ChisimbaConfigurationDocument($base.'/content/adm/adm.xml','adm');
    check($adm->root->getItem('section','adm')->countChildren('section')===2,'ADM preserves existing server');
    $source=new FixtureModuleFiles(); $source->records=array('one'=>array('MODULE_ID'=>'one','MODULE_NAME'=>'Café 日本語 & <One>','MODULE_DESCRIPTION'=>'Quoted \' " text','MODULE_CATEGORY'=>array('core','other'),'TAGS'=>array('one|two')), 'two'=>array('MODULE_ID'=>'two'));
    $reconcile=new FixtureReconcile(); $reconcile->path=$base.'/config/catalogue.xml';
    $catalogue=new catalogueconfig(); $catalogue->objects=array('altconfig'=>$paths,'language'=>new FixtureLanguage(),'modulefile'=>$source,'modules'=>$reconcile); $catalogue->objEngine=(object)array('version'=>'26 & test'); $catalogue->init();
    $summary=$catalogue->writeCatalogue();
    check($summary['discovered']===2 && $summary['reconciled'] && $reconcile->calls===array(array('one','two')),'catalogue complete publish before reconciliation');
    check((string)$catalogue->getModuleName('one')[0]==='Café 日本語 & <One>','catalogue Unicode and escaping');
    check(count($catalogue->getModulelist('core'))===1 && count($catalogue->getModulelist('all'))===2,'catalogue lists/categories');
    check($catalogue->searchModulelist("' or 1=1 or '",'name')===false,'catalogue query treated as literal');
    check(count($catalogue->getModuleDetails())===2 && count($catalogue->getModuleTags())===2,'catalogue details/tags shape');
    check($catalogue->getModuleStatus('absent')===false && $catalogue->getModuleDeps('absent')===false,'catalogue missing values safe');
    check($catalogue->getNavParam('anything')===false,'missing legacy catalogue navigation safe');
    $before=file_get_contents($reconcile->path); $calls=count($reconcile->calls);
    $source->records['two']=false;
    rejects(fn()=>$catalogue->writeCatalogue(),'incomplete scan rejected');
    check(file_get_contents($reconcile->path)===$before && count($reconcile->calls)===$calls,'incomplete scan cannot publish or prune');
    $source->records['two']=array('MODULE_ID'=>'one');
    rejects(fn()=>$catalogue->writeCatalogue(),'ambiguous IDs rejected');
    $source->records['two']=array('MODULE_ID'=>'two');
    $source->duringRead=static function() use ($reconcile,$before) { file_put_contents($reconcile->path,$before."\n"); };
    rejects(fn()=>$catalogue->writeCatalogue(),'uncooperative catalogue change rejected');
    check(file_get_contents($reconcile->path)===$before."\n" && count($reconcile->calls)===$calls,'stale catalogue cannot prune');
    $packages=$argv[2] ?? (is_dir($app.'/packages') ? $app.'/packages' : dirname($app,2).'/modules');
    require $packages.'/rtt/classes/rttutil_class_inc.php';
    $rtt=new rttutil(); $rtt->objDbUserparamsadmin=preferences($paths,$user);
    check($rtt->checkSipParams() && $rtt->objDbUserparamsadmin->getValue('rtt_username')==='0000','RTT creates missing preference');
    check($rtt->objDbUserparamsadmin->getValue('canvas')==='new','RTT preserves unrelated preferences');
    $rtt->objDbUserparamsadmin->setItem('rtt_username','existing');
    check($rtt->checkSipParams() && $rtt->objDbUserparamsadmin->getValue('rtt_username')==='existing','RTT preserves existing identity');
    check(!class_exists('Config',false) && !class_exists('Config_Container',false),'migrated services never load PEAR Config');
    echo 'PASS: '.$count." native-only INI, user isolation, generic helper and catalogue migration checks\n";
} finally {
    restore_error_handler();
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $entry) { if($entry->isDir()&&!$entry->isLink()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); } rmdir($base);
}
