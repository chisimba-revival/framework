<?php
/** Retired widget markers cannot generate third-party scripts. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function getObject($name,$module) {
        if($name==='user')return new class {public function getFirstName(){return 'Synthetic';} public function getSurname(){return 'Reader';}};
        if($name==='language')return new class {public function languageText($key){return $key;}};
        throw new RuntimeException('Unexpected service');
    }
}
require dirname(__DIR__,2).'/app/core_modules/filters/classes/parse4chiki_class_inc.php';
$filter=new parse4chiki();$filter->init();
foreach(['', 'Plain 日本語', '{evripost}', '{EVRIPOPVER}', '{evrisidebar}', '<p>{evripost} {EVRISIDEBAR}</p>', '{evripost', '{evripost} {unknown}'] as $value) {
    if($filter->parse($value)!==$value)throw new RuntimeException('Retired text changed');
}
if($filter->parse('{evripost} {firstname} {EVRISIDEBAR}')!=='{evripost} Synthetic {EVRISIDEBAR}')throw new RuntimeException('Ordinary Chiki command changed');
echo "PASS retired Evri markers remain intact; ordinary Chiki commands still work without widget services\n";
