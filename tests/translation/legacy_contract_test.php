<?php
/** Characterise the installed decorator order using synthetic storage only. */
set_include_path(dirname(__DIR__,2).'/app/lib/pear'.PATH_SEPARATOR.get_include_path());
require_once 'Translation2.php';
class TranslationFixtureStore {
    public $reads=0;
    public $pages=['en'=>['module'=>['plain'=>'Hello','markup'=>'<b>A & B</b>','accent'=>'Café','unicode'=>'中文 😀','zero'=>'0','empty'=>'','fallback'=>'English only']], 'fr'=>['module'=>['plain'=>'Bonjour']]];
    public function setLang($id){return true;}
    public function getLangData($id){return ['id'=>$id,'name'=>$id,'encoding'=>'UTF-8'];}
    public function getPage($page,$lang){++$this->reads;return $this->pages[$lang][$page]??[];}
    public function getOne($id,$page,$lang){return $this->getPage($page,$lang)[$id]??'';}
}
$base=new Translation2();$base->Translation2();$store=new TranslationFixtureStore();$base->storage=$store;$base->setLang('en');$base->setPageID('module');
$lang=$base->getDecorator('CacheMemory');$lang->setOption('prefetch',true);
$lang=$lang->getDecorator('SpecialChars');$lang->setOption('charset','UTF-8');
$lang=$lang->getDecorator('UTF8');$lang=$lang->getDecorator('DefaultText');$lang=$lang->getDecorator('Lang');$lang->setOption('fallbackLang','en');
$expected=['plain'=>'Hello','markup'=>'&lt;b&gt;A &amp; B&lt;/b&gt;','accent'=>'Caf&eacute;','unicode'=>'?? ?','zero'=>'zero','empty'=>'empty'];
foreach($expected as $id=>$value){$actual=$lang->get($id,'module','en');if($actual!==$value)throw new RuntimeException($id.': '.json_encode($actual));}
if($lang->get('fallback','module','fr')!=='fallback')throw new RuntimeException('Fallback observation changed');
if($lang->get('missing','module','en','Default')!=='Default')throw new RuntimeException('Default observation changed');
if($store->reads!==2)throw new RuntimeException('Page cache observation changed');
echo "PASS: legacy escaping, Latin-1 conversion, zero/empty handling, blocked English fallback and per-language page cache characterised\n";
