<?php
/** Read-only comparison against all installed English items; explicit local opt-in. */
if(PHP_SAPI!=='cli'||getenv('TRANSLATION_SMOKE')!=='1')exit(64);
chdir($argv[1]);$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();$native=$engine->getObject('languageConfig','language')->setup();$store=$engine->getObject('translationstore','language');
require_once 'Translation2.php';
class InstalledTranslationOracleStore {
 public $store;public function setLang($id){return ['id'=>$id];}
 public function getPage($page,$language){return $this->store->page($language,$page);}
 public function getOne($id,$page,$language){return $this->getPage($page,$language)[$id]??'';}
}
$base=new Translation2();$base->Translation2();$base->storage=new InstalledTranslationOracleStore();$base->storage->store=$store;$base->setLang('en');
$legacy=$base->getDecorator('CacheMemory');$legacy->setOption('prefetch',true);$legacy=$legacy->getDecorator('SpecialChars');$legacy->setOption('charset','UTF-8');$legacy=$legacy->getDecorator('UTF8');$legacy=$legacy->getDecorator('DefaultText');$legacy=$legacy->getDecorator('Lang');$legacy->setOption('fallbackLang','en');
$count=0;$unicode=0;$zero=0;
foreach($store->getPageNames() as $page)foreach($store->page('en',$page) as $id=>$raw){
 ++$count;$old=$legacy->get($id,$page,'en');$new=$native->get($id,$page,'en');if($old===$new)continue;
 if($raw==='0'&&$new==='0'){++$zero;continue;}
 if($new===htmlentities((string)$raw,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')&&preg_match('/[^\\x00-\\x7f]/',$new)){++$unicode;continue;}
 throw new RuntimeException('Unexplained installed translation difference');
}
echo "PASS: $count installed English translations compared; $unicode Unicode repairs; $zero zero-value repairs; no unexplained differences\n";
