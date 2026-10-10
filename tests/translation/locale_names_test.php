<?php
class ChisimbaObject {}
require dirname(__DIR__,2).'/app/core_modules/language/classes/languagecode_class_inc.php';
class NamesFixture extends languagecode {public function getObject($name,$module=null){return new class {public function getdefaultLanguageAbbrev(){return 'fr';}public function getCountry(){return 'ZA';}};}}
$n=new NamesFixture();$n->init();
function namesCheck($value,$label){if(!$value)throw new RuntimeException($label);}
namesCheck($n->getLanguage('EN')==='English','English language-code names');namesCheck($n->getISO('English')==='en'&&$n->getISO('EN')==='en','Reverse lookup');
namesCheck(count($n->iso_639_2_tags->codes)>100,'Public code-list contract');
foreach(['ZA','FR'] as $code){$html=$n->country($code);namesCheck(substr_count($html,'selected="selected"')===1&&str_contains($html,'value="'.$code.'" selected="selected"'),'Selection isolation');}
namesCheck($n->getName('fr')==='France','Country lookup');namesCheck($n->getName('missing')==='','Unknown country');
namesCheck(str_contains($n->dec_country(),'onchange="this.form.submit()"'),'Decorated country auto-submit');
namesCheck(!str_contains($n->country('\"><script>'),'script'),'Selection cannot inject markup');
namesCheck(!class_exists('I18Nv2_Country',false),'No PEAR locale classes');
namesCheck(str_contains($n->countryAlpha(),'id="input_country"'),'Country label target preserved');
echo "PASS: native language/country lists, reverse lookup, HTML selection/escaping and legacy list shape\n";
