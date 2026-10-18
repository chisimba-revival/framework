<?php
/** Actual language facade with synthetic lookup and system-text context. */
class dbTable {}
function log_debug($message){}
require __DIR__.'/native_lookup_test.php';
require dirname(__DIR__,2).'/app/core_modules/language/classes/language_class_inc.php';
class LanguageFixture extends language {public function getObject($name,$module=null){return $this->objConfig;}}
$l=new LanguageFixture();$l->lang=$t;$l->objConfig=new class {public function getdefaultLanguageAbbrev(){return 'en';}public function getCountry(){return 'ZA';}};
$l->abstractList=['context'=>'course','author'=>'instructor'];$_SESSION=[];$_POST=[];
$store->data['en']['module']['terms']='[-AUTHOR-] teaches this [-context-].';++$store->version;
translationCheck($l->languageText('terms','module')==='Instructor teaches this course.','System-text substitution');
translationCheck($l->languageText('no_such_key','module','Fallback label')==='Fallback label','Facade default');
translationCheck($l->languageText('zero','module')==='0','Facade zero');
$_POST['Languages']=['en'];translationCheck($l->currentLanguage()==='en','Array input rejected safely');
$_POST=['Languages'=>'FR'];translationCheck($l->currentLanguage()==='fr'&&$_SESSION['language']==='fr','Language selection normalised');
$_POST=[];translationCheck($l->languageText('fallback','module')==='English only','Facade fallback');
translationCheck(!class_exists('I18Nv2',false)&&!class_exists('Translation2',false),'No vendor translation/locale classes');
echo "PASS: language facade, system terms, fallback labels, zero, session selection and malformed input\n";
