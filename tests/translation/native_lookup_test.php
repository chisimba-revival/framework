<?php
require dirname(__DIR__,2).'/app/core_modules/language/classes/nativetranslation.php';
class NativeTranslationFixture {
    public $version=0; public $reads=0;
    public $data=['en'=>['module'=>['plain'=>'Hello','markup'=>'<b>A & B</b>','accent'=>'Café','unicode'=>'中文 😀','zero'=>'0','fallback'=>'English only']], 'fr'=>['module'=>['plain'=>'Bonjour']]];
    public function languages(){return ['fr'=>['id'=>'fr','name'=>'French','encoding'=>'UTF-8']];}
    public function revision(){return $this->version;}
    public function page($language,$page){++$this->reads;return $this->data[$language][$page]??[];}
}
function translationCheck($condition,$message){if(!$condition)throw new RuntimeException($message);}
$store=new NativeTranslationFixture();$t=new ChisimbaTranslation($store);$t->setPageID('module');
foreach(['plain'=>'Hello','markup'=>'&lt;b&gt;A &amp; B&lt;/b&gt;','accent'=>'Caf&eacute;','unicode'=>'中文 😀','zero'=>'0'] as $id=>$expected)translationCheck($t->get($id)===$expected,$id);
translationCheck($store->reads===1,'Page prefetch reused');
$t->setLang('fr');translationCheck($t->get('plain')==='Bonjour','Selected language');translationCheck($t->get('fallback')==='English only','English fallback');
translationCheck($t->get('missing','module',null,'Default')==='Default','Explicit default');translationCheck($t->get('missing')==='missing','Missing ID');
translationCheck($t->get('plain','other')==='plain','Page isolation');
translationCheck($t->get('plain','module','../bad')==='Hello','Untrusted language falls back');
$store->data['en']['module']['plain']='Changed';++$store->version;
translationCheck($t->get('plain','module','en')==='Changed','Write invalidates cache');
translationCheck($t->getLangs()===['fr'=>'French'],'Names shape');translationCheck($t->getLangs('ids')===['fr'],'IDs shape');
translationCheck(!class_exists('Translation2',false),'No vendor class loaded');
echo "PASS: native lookup, escaping, Unicode, zero, fallback, defaults, page isolation, cache invalidation and language-list shapes\n";
