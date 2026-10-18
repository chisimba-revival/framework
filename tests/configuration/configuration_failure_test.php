<?php
/** Real service, synthetic files only: failures, isolation, XML and setter matrix. */
if (PHP_SAPI !== 'cli') { exit(2); }
$app = realpath($argv[1] ?? dirname(__DIR__, 2) . '/app');
class ChisimbaObject {}
class customException extends Exception {}
require $app . '/core_modules/config/classes/altconfig_class_inc.php';
set_error_handler(static function ($severity, $message) { throw new ErrorException($message, 0, $severity); });
$base = sys_get_temp_dir() . '/chisimba-config-failures-' . bin2hex(random_bytes(8));
mkdir($base, 0700);
$count = 0;
function check($condition, $label) {
    global $count;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    ++$count;
}
function rejects($operation, $label) {
    try { $operation(); } catch (RuntimeException $e) { check(true, $label); return; }
    check(false, $label);
}
function configAt($path) { $c = new altconfig(); $c->_path = $path; return $c; }
class FailingConfigurationFile extends ChisimbaConfigurationFile {
    public $failure;
    protected function writeChunk($stream, $bytes) {
        if ($this->failure === 'full') { return false; }
        if ($this->failure === 'zero') { return 0; }
        if ($this->failure === 'partial') { return parent::writeChunk($stream, substr($bytes, 0, 3)); }
        return parent::writeChunk($stream, $bytes);
    }
    protected function flush($stream) { return $this->failure === 'flush' ? false : parent::flush($stream); }
    protected function move($source, $destination) { return $this->failure === 'rename' ? false : parent::move($source, $destination); }
}
try {
    $path = $base . '/config.xml';
    $original = '<?xml version="1.0" encoding="ISO-8859-1"?><Settings><KEWL_SITENAME>Original</KEWL_SITENAME><ZERO>0</ZERO></Settings>';
    file_put_contents($path, $original); chmod($path, 0640);
    $files = new FailingConfigurationFile();
    foreach (array('full', 'zero', 'flush', 'rename') as $failure) {
        $files->failure = $failure;
        rejects(function () use ($files, $path, $original) { $files->replace($path, 'changed', $original); }, $failure . ' fails');
        check(file_get_contents($path) === $original, $failure . ' preserves bytes');
        check(glob($base . '/.chisimba-config-*') === array(), $failure . ' cleans temporary files');
    }
    $files->failure = 'flush';
    $document = new ChisimbaConfigurationDocument($path, 'Settings', false, $files);
    rejects(function () use ($document) { $document->save($document->fromArray(array('ZERO'=>'bad'))); }, 'document flush failure reported');
    check($document->root->toArray()['root']['Settings']['ZERO'] === '0', 'document cache unchanged on failed flush');
    $files->failure = 'partial';
    $files->replace($path, $original . "\n", $original);
    check(file_get_contents($path) === $original . "\n", 'partial writes complete');
    check((fileperms($path) & 0777) === 0640, 'permissions preserved');
    check(fileowner($path) === posix_geteuid() && filegroup($path) === posix_getegid(), 'ownership preserved');
    $a = configAt($base); $b = configAt($base);
    $a->getItem('ZERO'); $b->getItem('ZERO');
    check($a->setItem('ZERO', 'first'), 'first writer saves');
    $bytes = file_get_contents($path);
    check(!$b->setItem('ZERO', 'stale'), 'stale setter rejected');
    check($b->getItem('ZERO') === '0', 'failed setter leaves cache unchanged');
    check(!$b->appendToConfig(array('EXTRA' => 'stale')), 'stale append rejected');
    check(!$b->writeConfig(array('ZERO' => 'stale'), 'XML'), 'stale replacement rejected');
    check(file_get_contents($path) === $bytes, 'stale writers preserve winning bytes');
    check(!$a->setItem('MISSING', 'no'), 'missing directive fails');
    check(!$a->setItem('ZERO', "bad\0value"), 'invalid XML characters fail');
    check(!$a->setItem('ZERO', "\xff"), 'invalid UTF8 fails');
    check(!$a->writeConfig(array('bad name' => 'value'), 'XML'), 'invalid XML name fails');
    check(!$a->writeConfig(array('ZERO' => new stdClass()), 'XML'), 'invalid object value fails');
    check(file_get_contents($path) === $bytes && $a->getItem('ZERO') === 'first', 'validation preserves disk/cache');
    check($a->setSiteName('Café 日本語 & <tag>'), 'Latin1 file accepts Unicode without loss');
    $bytes = file_get_contents($path);
    check(str_contains($bytes, 'encoding="ISO-8859-1"') && str_contains($bytes, '&#'), 'encoding retained with references');
    check(configAt($base)->getSiteName() === 'Café 日本語 & <tag>', 'Unicode fresh-reader roundtrip');
    $xml = new ChisimbaConfigurationXml();
    foreach (array('', '<Settings><bad></Settings>', '<settings/>', '<!DOCTYPE Settings [<!ENTITY x SYSTEM "file:///etc/passwd">]><Settings><X>&x;</X></Settings>', '<Settings xmlns="urn:test"><X>v</X></Settings>', '<Settings><X>text<Y/>more</X></Settings>') as $bad) {
        rejects(function () use ($xml, $bad) { $xml->parse($bad, 'Settings'); }, 'unsafe/invalid XML rejected');
    }
    $shape = '<Settings version="1"><X unit="m">one</X><X unit="s">two</X><Group id="a"><Y><![CDATA[A & B]]></Y></Group><Group id="b"><Y>0</Y></Group><EMPTY/></Settings>';
    $root = $xml->parse($shape, 'Settings')[0];
    $expected = array('root'=>array('Settings'=>array('@'=>array('version'=>'1'), 'X'=>array(array('#'=>'one','@'=>array('unit'=>'m')),array('#'=>'two','@'=>array('unit'=>'s'))), 'Group'=>array(array('@'=>array('id'=>'a'),'Y'=>'A & B'),array('@'=>array('id'=>'b'),'Y'=>'0')), 'EMPTY'=>'')));
    check($root->toArray() === $expected, 'attributes duplicate sections/directives CDATA and empty shape');
    check($xml->parse($xml->render($root,'Settings','UTF-8'),'Settings')[0]->toArray() === $expected, 'tree roundtrip');
    $oldLibxml = libxml_use_internal_errors(false);
    $xml->parse('<Settings/>', 'Settings');
    check(libxml_use_internal_errors() === false, 'libxml error mode restored');
    libxml_use_internal_errors($oldLibxml);
    // A changed public path must never reuse another document's cached state.
    mkdir($base . '/other', 0700);
    file_put_contents($base . '/other/config.xml', $original);
    $a->_path = $base . '/other';
    check($a->getItem('ZERO') === '0', 'path switch reloads');
    check($a->setItem('ZERO', 'other') && file_get_contents($path) === $bytes, 'path switch isolates saves');
    $a = configAt($base);
    check($a->writeProperties(array('OPTION'=>'初期', 'EMPTY'=>''),'XML'), 'properties create');
    check($a->getParam('OPTION') === '初期', 'properties cache updated');
    check($a->setSiteName('Site after properties'), 'site save after properties');
    $siteBytes = file_get_contents($path);
    check($a->writeProperties(array('OPTION'=>'second'),'XML'), 'properties replacement');
    check(file_get_contents($path) === $siteBytes, 'properties write does not touch site');
    check($a->getParam('OPTION') === 'second', 'properties repeated lookup works');
    $propsBytes = file_get_contents($base . '/sysconfig_properties.xml');
    check(!$a->writeProperties(array('OPTION'=>'bad'),'invalid'), 'invalid properties format fails');
    check(file_get_contents($base . '/sysconfig_properties.xml') === $propsBytes, 'invalid properties format preserves bytes');
    check($a->readProperties($base . '/other', 'XML') === false, 'missing second properties document reported');
    check($a->writeProperties(array('OPTION'=>'other properties'), 'XML'), 'missing second properties document created at requested path');
    check(file_get_contents($base . '/sysconfig_properties.xml') === $propsBytes, 'failed read cannot redirect write to previous properties file');
    check($a->getParam('OPTION') === 'other properties', 'second properties document has independent state');
    $fresh = configAt($base . '/other');
    check($fresh->writeConfig(array(), 'XML'), 'empty Settings supported');
    check($fresh->readConfig('', 'XML')->toArray() === array('root'=>array('Settings'=>array())), 'empty Settings shape');
    check($fresh->appendToConfig(array('NEW'=>'value')), 'append on empty Settings');
    check(configAt($base . '/other')->getItem('NEW') === 'value', 'append without prior getter persists');
    // Symlinks and hardlinks fail before writes, including lock links.
    symlink($path, $base . '/linked.xml');
    rejects(function () use ($files, $base) { $files->read($base . '/linked.xml'); }, 'symlink refused');
    link($path, $base . '/hard.xml');
    rejects(function () use ($files, $path) { $files->read($path); }, 'hardlink refused');
    unlink($base . '/hard.xml');
    symlink($path, $base . '/new.xml.lock');
    rejects(function () use ($files, $base) { $files->replace($base . '/new.xml','new',null); }, 'lock symlink refused');
    check(file_get_contents($path) === $siteBytes, 'link tests preserve destination');
    $files->replace($base . '/created.xml', '<Settings/>', null);
    check((fileperms($base . '/created.xml') & 0777) === 0600, 'new files private');
    // Every specialised setter targets the independently specified directive only.
    $setters = array(
        'setSiteName'=>'KEWL_SITENAME', 'setSystemType'=>'KEWL_SYSTEM_TYPE',
        'setinstitutionShortName'=>'KEWL_INSTITUTION_SHORTNAME', 'setinstitutionName'=>'KEWL_INSTITUTION_NAME',
        'setsiteEmail'=>'KEWL_SITEEMAIL', 'setsystemTimeout'=>'KEWL_SYSTEMTIMEOUT',
        'setPrelogin'=>'KEWL_PRELOGIN_MODULE', 'setsiteRoot'=>'KEWL_SITE_ROOT',
        'setdefaultSkin'=>'KEWL_DEFAULT_SKIN', 'setskinRoot'=>'KEWL_SKIN_ROOT',
        'setdefaultLanguage'=>'KEWL_DEFAULT_LANGUAGE', 'setdefaultLanguageAbbrev'=>'KEWL_DEFAULT_LANGUAGE_ABBREV',
        'setbannerExtension'=>'KEWL_BANNER_EXT', 'setsiteRootPath'=>'KEWL_SITEROOT_PATH',
        'setallowSelfRegister'=>'KEWL_ALLOW_SELFREGISTER', 'setdefaultModuleName'=>'KEWL_POSTLOGIN_MODULE',
        'setuseLDAP'=>'LDAP_USED', 'setcontentPath'=>'KEWL_CONTENT_PATH', 'setcontentRoot'=>'KEWL_CONTENT_PATH',
        'seterror_reporting'=>'KEWL_ERROR_REPORTING', 'setDsn'=>'KEWL_DB_DSN', 'setDsn2'=>'KEWL_DB2_DSN');
    $values = array_fill_keys(array_values($setters), 'unchanged');
    $values['CHISIMBA_DB_PORT'] = '3306';
    $a = configAt($base); check($a->writeConfig($values, 'XML'), 'setter fixture saves');
    foreach ($setters as $setter=>$key) {
        $value = 'value for ' . $setter;
        check($a->$setter($value), $setter . ' succeeds');
        $values[$key] = $value;
        check(configAt($base)->readConfig('', 'XML')->toArray()['root']['Settings'] === $values, $setter . ' changes only intended key');
    }
    check($a->updateParam('ZERO', '', 'x') === false, 'updateParam missing key fails');
    check($a->updateParam('KEWL_SITENAME', '', 'updated'), 'updateParam existing key saves');
    check(configAt($base)->getSiteName() === 'updated', 'updateParam persisted');
    $revision = $a->getRevision();
    check($a->updateParam('KEWL_SITENAME', '', 'revision save', false, $revision), 'current editor revision accepted');
    $newRequest = configAt($base);
    check(!$newRequest->updateParam('KEWL_SITENAME', '', 'stale browser', false, $revision), 'stale editor across requests rejected');
    check(configAt($base)->getSiteName() === 'revision save', 'stale editor preserves current value');
    file_put_contents($base . '/other/config.xml', '<Settings><broken>');
    $broken = configAt($base . '/other');
    check(!$broken->updateParam('NEW', '', 'bad', false, $revision), 'malformed site returns checked editor save failure');
    check(file_get_contents($base . '/other/config.xml') === '<Settings><broken>', 'malformed original never overwritten');
    // Metadata/permission failure using a directory that the running user cannot write.
    if (posix_geteuid() !== 0) {
        mkdir($base . '/readonly', 0500);
        $r = configAt($base . '/readonly');
        check(!$r->writeConfig(array('ZERO'=>'0'),'XML'), 'read-only directory fails');
        chmod($base . '/readonly', 0700);
    }
    echo 'PASS: ' . $count . " configuration failure/roundtrip/setter checks\n";
} finally {
    restore_error_handler();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $file) { if ($file->isDir() && !$file->isLink()) { rmdir($file->getPathname()); } else { unlink($file->getPathname()); } }
    rmdir($base);
}
