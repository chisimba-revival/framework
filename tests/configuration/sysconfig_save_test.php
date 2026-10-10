<?php
/** Actual controller and form helper over disposable configuration and service doubles. */
if (PHP_SAPI !== 'cli') { exit(2); }
$app = realpath($argv[1] ?? dirname(__DIR__, 2) . '/app');
$GLOBALS['kewl_entry_point_run'] = true;
#[AllowDynamicProperties]
class ChisimbaObject {
    public $services = array(); public $params = array(); public $vars = array();
    public function getObject($name, $module = null) { return $this->services[$name]; }
    public function getParam($name, $default = null) { return $this->params[$name] ?? $default; }
    public function setVar($name, $value) { $this->vars[$name] = $value; }
    public function nextAction($name, $params) { return array($name, $params); }
    public function uri($params) { return '/fixture'; }
    public function loadClass($name, $module) {}
}
class controller extends ChisimbaObject {}
class customException extends Exception {}
class FixtureLanguage { public function languageText($key, $module = 'system', $fallback = null) { return $fallback ?? $key; } }
class FixtureUser { public $admin = true; public function isAdmin() { return $this->admin; } }
class FixtureCsrf {
    public function consume($context, $token) { return $context === 'sysconfig_site_save' && $token === 'valid'; }
    public function issue($context) { return 'fresh-token'; }
}
class FixtureComposition { public function build() { return array('csrf'=>new FixtureCsrf()); } }
class FixtureDb { public function __call($name, $args) { throw new RuntimeException('Site edit unexpectedly called database method ' . $name); } }
class FixtureCheck { public function objectFileExists($name, $module) { return false; } }
class textinput {
    public $name; public $value = ''; public $fldType = 'text'; public $size;
    public function __construct($name) { $this->name = $name; }
    public function setValue($value) { $this->value = $value; }
    public function show() { return '<input name="'.$this->name.'" value="'.htmlspecialchars((string)$this->value,ENT_QUOTES,'UTF-8').'">'; }
}
class button extends textinput { public function setToSubmit() {} }
class label { public function __construct($text,$id) {} public function show() { return 'Value'; } }
class form {
    public $displayType; private $html = '';
    public function __construct($name) {} public function setAction($uri) {}
    public function addToForm($html) { $this->html .= $html; }
    public function show() { return $this->html; }
}
require $app . '/core_modules/config/classes/altconfig_class_inc.php';
require $app . '/core_modules/sysconfig/controller.php';
require $app . '/core_modules/sysconfig/classes/sysconfiginterface_class_inc.php';
set_error_handler(static function ($severity,$message) { throw new ErrorException($message,0,$severity); });
$base = sys_get_temp_dir().'/chisimba-config-editor-'.bin2hex(random_bytes(8)); mkdir($base,0700);
file_put_contents($base.'/config.xml','<Settings><KEWL_SITENAME>Original</KEWL_SITENAME></Settings>');
$count = 0;
function check($value,$label) { global $count; if (!$value) { throw new RuntimeException($label); } ++$count; }
try {
    $config = new altconfig(); $config->_path = $base;
    $services = array('altconfig'=>$config,'nativeauthwebcomposition'=>new FixtureComposition(),'checkobject'=>new FixtureCheck());
    $ui = new sysconfiginterface(); $ui->services=$services; $ui->objLanguage = new FixtureLanguage(); $ui->objDbSysconfig = new FixtureDb();
    $c = new sysconfig(); $c->services=$services; $c->objInterface=$ui; $c->objUser=new FixtureUser(); $c->objLanguage=new FixtureLanguage(); $c->objSysConfig=new FixtureDb();
    $params = array('action'=>'save','pmodule'=>'_site_','id'=>'KEWL_SITENAME','pvalue'=>'Draft & 日本語','config_revision'=>$config->getRevision(),'config_csrf'=>'valid');
    $ui->params = array('action'=>'edit','id'=>'KEWL_SITENAME');
    $html = $ui->showEditAddForm('_site_');
    check(str_contains($html,'value="Original"') && str_contains($html,$config->getRevision()) && str_contains($html,'fresh-token'), 'edit includes current value, revision and CSRF');
    foreach (array('nonadmin','get','csrf','revision','stale') as $case) {
        $_SERVER['REQUEST_METHOD']='POST'; $c->objUser->admin=true; $c->params=$params;
        if ($case==='nonadmin') { $c->objUser->admin=false; }
        if ($case==='get') { $_SERVER['REQUEST_METHOD']='GET'; }
        if ($case==='csrf') { $c->params['config_csrf']='invalid'; }
        if ($case==='revision') { unset($c->params['config_revision']); }
        if ($case==='stale') { $c->params['config_revision']=str_repeat('0',64); }
        $ui->params=$c->params;
        $result=$c->dispatch();
        check(is_string($result), $case.' rejected');
        check($config->getSiteName()==='Original', $case.' preserves value');
        if ($case!=='nonadmin') {
            check(str_contains($c->vars['str'],'role="alert"') && str_contains($c->vars['str'],'Draft &amp; 日本語'), $case.' visible failure retains escaped draft');
        }
    }
    $_SERVER['REQUEST_METHOD']='POST'; $c->objUser->admin=true; $c->params=$params; $ui->params=$params;
    check(is_array($c->dispatch()), 'valid POST redirects after save');
    check($config->getSiteName()==='Draft & 日本語', 'valid POST saves fixture');
    // The same form cannot overwrite the new revision on a second request.
    check(is_string($c->dispatch()), 'duplicate form revision rejected');
    check(str_contains($c->vars['str'], $params['config_revision']), 'failed form retains stale revision until explicit reload');
    echo 'PASS: '.$count." site-editor access, POST, CSRF, revision, draft and successful-save checks\n";
} finally {
    restore_error_handler(); foreach(glob($base.'/*') as $file) { unlink($file); } rmdir($base);
}
