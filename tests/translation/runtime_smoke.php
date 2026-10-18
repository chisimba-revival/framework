<?php
/** Read-only installed-engine gate; never changes language data or configuration. */
if(PHP_SAPI!=='cli'||getenv('TRANSLATION_SMOKE')!=='1')exit(64);
chdir($argv[1]);$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();$language=$engine->getObject('language','language');
foreach([['word_home','system'],['word_save','system'],['mod_security_rememberme','security']] as [$id,$module]){
 $value=$language->languageText($id,$module);if(!is_string($value)||$value===''||str_contains($value,'Language item not found'))throw new RuntimeException('Language lookup failed');
}
$names=$engine->getObject('languagecode','language');if($names->getLanguage('en')!=='English'||count($names->countryListArr())<200)throw new RuntimeException('Locale lists unavailable');
foreach(get_included_files() as $file)if(preg_match('~/(?:Translation2|I18Nv2)(?:/|\\.php)~',$file))throw new RuntimeException('Legacy translation/locale code loaded');
echo "PASS: installed language lookups and country/language lists, without Translation2 or I18Nv2 code\n";
