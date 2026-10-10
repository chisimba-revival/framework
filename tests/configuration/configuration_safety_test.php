<?php
/**
 * Verify repaired Config/altconfig behaviour using disposable fixtures.
 * Every run requires all safety gates to pass; --require-safe remains accepted.
 * Never reads or writes an installed configuration or includes database settings.
 */
if (PHP_SAPI !== 'cli') { exit(2); }
$app = isset($argv[1]) && $argv[1][0] !== '-' ? $argv[1] : dirname(__DIR__, 2) . '/app';
$app = realpath($app);
set_include_path($app . '/lib/pear' . PATH_SEPARATOR . get_include_path());
require_once 'Config.php';
class ChisimbaObject {}
class customException extends Exception {}
function log_debug($message) {}
require $app . '/core_modules/config/classes/altconfig_class_inc.php';
$base = sys_get_temp_dir() . '/chisimba-config-contract-' . bin2hex(random_bytes(8));
mkdir($base, 0700);mkdir($base . '/config', 0700);
$originalCwd = getcwd();chdir($base);
$diagnostics = array();
set_error_handler(static function ($severity, $message) use (&$diagnostics) {
    $diagnostics[] = preg_replace('~(?:/[^ :]+)+~', '[path]', $message);
    return true;
});
$xml = '<?xml version="1.0" encoding="UTF-8"?><Settings><KEWL_SITENAME>Fixture site</KEWL_SITENAME><EMPTY/><ZERO>0</ZERO><FALSE_TEXT>FALSE</FALSE_TEXT><SPACE>  padded  </SPACE><ENTITY>A &amp; B</ENTITY><DUP>one</DUP><DUP>two</DUP></Settings>';
function freshFixture() {
    global $base, $xml;
    file_put_contents($base . '/config/config.xml', $xml);
    $config = new altconfig();$config->_path = $base . '/config/';
    return $config;
}
$result = array();
try {
    $c=freshFixture(); $root=$c->readConfig('', 'XML');
    $result['read']=$root->toArray();
    $result['missing']=$c->getItem('MISSING');
    $result['selectedDuplicate']=$c->getItem('DUP');
    $c->setSiteName('Changed & name');
    $result['setter']=array('sameObject'=>$c->getSiteName(),'diskValid'=>simplexml_load_file($base.'/config/config.xml')!==false);
    $n=new altconfig();$n->_path=$base.'/config/';$result['setter']['freshObject']=$n->getSiteName();
    $c=freshFixture();$c->getItem('ZERO');$result['writeReturn']=$c->writeConfig(array('KEWL_SITENAME'=>'Replacement','ZERO'=>'0'), 'XML');
    $result['writeSameObject']=$c->getSiteName();
    $n=new altconfig();$n->_path=$base.'/config/';$result['writeFreshObject']=$n->getSiteName();
    $c=freshFixture();$c->getItem('ZERO');$result['appendReturn']=$c->appendToConfig(array('ADDED'=>'new'));
    $result['appendSameObject']=$c->getItem('ADDED');
    $n=new altconfig();$n->_path=$base.'/config/';$result['appendFreshObject']=$n->getItem('ADDED');
    $c=freshFixture();$c->getItem('ZERO');
    try {$result['setItem']=$c->setItem('ZERO','1');}catch(Throwable $e){$result['setItemError']=get_class($e);}
    $c=freshFixture();$c->getItem('ZERO');
    $result['unsupportedWriteReturn']=$c->writeConfig(array('A'=>'b'),'not-a-format');
    $result['unsupportedWritePreserved']=is_file($base.'/config/config.xml') && file_get_contents($base.'/config/config.xml') === $xml;
    file_put_contents($base.'/sample.ini', "[Settings]\ntruth=On\nfalsehood=Off\nzero=0\nempty=\"\"\nleading=007\nlist=\"one, two\"\ncompact=\"one,two\"\n");
    $p=new Config();$r=$p->parseConfig($base.'/sample.ini','IniFile');$result['ini']=$r->toArray();
    $p=new Config();$r=$p->parseConfig(array('truth'=>true,'falsehood'=>false,'null'=>null,'zero'=>0),'PHPArray');$result['phpArray']=$r->toArray();
    $c=freshFixture();$c->getItem('ZERO');$c->_path=$base.'/absent/';
    $result['missingDirectoryWriteReturn']=$c->writeConfig(array('A'=>'b'),'XML');
    $result['missingDirectoryFileCreated']=is_file($base.'/absent/config.xml');
    $p=new Config();file_put_contents($base.'/bad.xml','<Settings><broken></Settings>');
    $result['malformedXmlIsError']=PEAR::isError($p->parseConfig($base.'/bad.xml','XML'));
    file_put_contents($base.'/unicode.xml','<?xml version="1.0" encoding="UTF-8"?><Settings><TEXT>Café 日本語</TEXT></Settings>');
    $p=new ChisimbaConfigurationDocument($base.'/unicode.xml','Settings');$r=$p->root;
    $v=$r->toArray()['root']['Settings']['TEXT'];
    $result['utf8OptionPreservesUnicode']=$v==='Café 日本語';
    $result['unicodeBytesHex']=bin2hex($v);
    $c=freshFixture();$c->getItem('ZERO');
    file_put_contents($base.'/config/sysconfig_properties.xml','<sysConfigSettings><OPTION>fixture</OPTION></sysConfigSettings>');
    $beforeConfig=file_get_contents($base.'/config/config.xml');
    $beforeProperties=file_get_contents($base.'/config/sysconfig_properties.xml');
    $c->readProperties($base.'/config','XML');$c->setSiteName('After properties');
    $result['propertiesThenSetter']=array(
        'siteChanged'=>file_get_contents($base.'/config/config.xml')!==$beforeConfig,
        'propertiesChanged'=>file_get_contents($base.'/config/sysconfig_properties.xml')!==$beforeProperties);
    file_put_contents($base.'/catalogue.xml','<settings><module enabled="yes"><id>one</id><tag>a</tag><tag>b</tag></module><module><id>two</id></module></settings>');
    $p=new Config();$r=$p->parseConfig($base.'/catalogue.xml','XML');
    $result['catalogue']=$r->toArray();
    $result['diagnostics']=array_values(array_unique($diagnostics));

    $expected = array(
        'read' => array('root'=>array('Settings'=>array('KEWL_SITENAME'=>'Fixture site','EMPTY'=>'','ZERO'=>'0','FALSE_TEXT'=>'FALSE','SPACE'=>'padded','ENTITY'=>'A & B','DUP'=>array('one','two')))),
        'missing'=>false, 'selectedDuplicate'=>'two',
        'setter'=>array('sameObject'=>'Changed & name','diskValid'=>true,'freshObject'=>'Changed & name'),
        'writeReturn'=>true, 'writeSameObject'=>'Replacement', 'writeFreshObject'=>'Replacement',
        'appendReturn'=>true, 'appendSameObject'=>'new', 'appendFreshObject'=>'new',
        'setItem'=>true, 'unsupportedWriteReturn'=>false, 'unsupportedWritePreserved'=>true,
        'ini'=>array('root'=>array('Settings'=>array('truth'=>'1','falsehood'=>'','zero'=>'0','empty'=>'','leading'=>'007','list'=>array('one','two'),'compact'=>'one,two'))),
        'phpArray'=>array('root'=>array('truth'=>true,'falsehood'=>false,'null'=>null,'zero'=>0)),
        'missingDirectoryWriteReturn'=>false, 'missingDirectoryFileCreated'=>false,
        'malformedXmlIsError'=>true, 'utf8OptionPreservesUnicode'=>true,
        'unicodeBytesHex'=>bin2hex('Café 日本語'),
        'propertiesThenSetter'=>array('siteChanged'=>true,'propertiesChanged'=>false),
        'catalogue'=>array('root'=>array('settings'=>array('module'=>array(
            array('@'=>array('enabled'=>'yes'),'id'=>'one','tag'=>array('a','b')),
            array('id'=>'two'))))),
    );
    foreach ($expected as $key=>$value) {
        if (!array_key_exists($key,$result) || $result[$key] !== $value) {
            throw new RuntimeException('Safety observation failed: '.$key.'');
        }
    }
    $allowedDiagnostics = array();
    foreach ($result['diagnostics'] as $diagnostic) {
        if (!in_array($diagnostic,$allowedDiagnostics,true)) {
            throw new RuntimeException('Unexpected diagnostic: '.$diagnostic);
        }
    }
    $safety = array(
        'whole_file_write_refreshes_cache'=>$result['writeSameObject']===$result['writeFreshObject'],
        'append_refreshes_cache'=>$result['appendSameObject']===$result['appendFreshObject'],
        'generic_setter_works'=>($result['setItem'] ?? false) === true,
        'unsupported_format_preserves_file'=>$result['unsupportedWritePreserved'] && $result['unsupportedWriteReturn']!==true,
        'write_failure_is_reported'=>$result['missingDirectoryWriteReturn']!==true,
        'utf8_input_is_preserved'=>$result['utf8OptionPreservesUnicode'],
        'properties_and_site_writes_are_isolated'=>$result['propertiesThenSetter']===array('siteChanged'=>true,'propertiesChanged'=>false),
    );
    $result['observationChecks']=count($expected);
    $result['cutoverSafetyGates']=$safety;
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
    $exitCode = in_array(false, $safety, true) ? 1 : 0;
} finally {
    restore_error_handler();chdir($originalCwd);
    foreach (glob($base.'/config/*') as $path) unlink($path);
    foreach (glob($base.'/*') as $path) if(is_file($path))unlink($path);
    rmdir($base.'/config');rmdir($base);
}

exit($exitCode);
