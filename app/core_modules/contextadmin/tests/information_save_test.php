<?php
$GLOBALS['kewl_entry_point_run'] = true;
set_error_handler(static function ($n, $s) { throw new ErrorException($s, 0, $n); });
class controller {
    public $params = [], $vars = [], $services = [];
    function getParam($key, $default = null) { return $this->params[$key] ?? $default; }
    function getSession($key) { return 'course'; }
    function getObject($name, $module = '') { return $this->services[$name]; }
    function setVar($key, $value) { $this->vars[$key] = $value; }
    function nextAction($action, $args = []) { return $action; }
    function setPageTemplate($value) {}
    function setLayoutTemplate($value) {}
}
require dirname(__DIR__) . '/controller.php';
function expect($ok, $why) { if (!$ok) throw new RuntimeException($why); }
$c = new contextadmin();
$c->services['contextsettingsaccess'] = new class { public $allowed = true; function canEdit($code) { return $this->allowed; } };
$c->services['contextimage'] = new class {
    public $writes = 0, $fail = false, $removed = false;
    function setContextImage($code, $id) { ++$this->writes; return !$this->fail; }
    function removeContextImage($code) { ++$this->writes; $this->removed = true; return !$this->fail; }
};
$c->services['richtextsanitizer'] = new class { function cleanHtml($s) { return 'sanitised:' . $s; } };
$c->objContext = new class {
    public $writes = 0, $saved = null;
    function getContext($code) { return ['about' => 'old']; }
    function updateAbout($code, $about) { ++$this->writes; $this->saved = $about; return true; }
};
$c->csrf = new class {
    function issue($purpose) { return 'fresh'; }
    function consume($purpose, $token) { return $purpose === 'context_information_' . substr(hash('sha256', 'course'), 0, 32) && $token === 'valid'; }
};
$invoke = static function ($method) use ($c) { return (new ReflectionMethod($c, $method))->invoke($c); };
$c->params = ['contextCode' => 'course', 'about' => '<p>draft</p>', 'imageselect' => '', 'removeimage' => '1', 'csrf_token' => 'valid'];
$_SERVER['REQUEST_METHOD'] = 'GET'; $invoke('__savestep2');
expect($c->objContext->writes === 0 && $c->services['contextimage']->writes === 0, 'GET must not write');
$_SERVER['REQUEST_METHOD'] = 'POST';
$c->services['contextsettingsaccess']->allowed = false; $invoke('__savestep2');
expect($c->objContext->writes === 0, 'revoked role must not write');
$c->services['contextsettingsaccess']->allowed = true;
$c->params['contextCode'] = 'foreign'; $invoke('__savestep2');
expect($c->objContext->writes === 0, 'cross-scope must not write'); $c->params['contextCode'] = 'course';
foreach (['expired', [], ''] as $token) {
    $c->params['csrf_token'] = $token;
    expect($invoke('__savestep2') === 'step2.php', 'token failure renders draft');
    expect($c->vars['context']['about'] === '<p>draft</p>' && $c->vars['informationCsrf'] === 'fresh', 'draft and renewed token');
}
expect($c->services['contextimage']->writes === 0, 'token failure does not remove image');
$c->params['csrf_token'] = 'valid'; $c->params['imageselect'] = 'replacement'; $c->services['contextimage']->fail = true;
expect($invoke('__savestep2') === 'step2.php' && $c->objContext->writes === 0, 'image failure keeps description unsaved');
expect(!$c->services['contextimage']->removed, 'failed replacement never removes existing image');
$c->services['contextimage']->fail = false;
expect($invoke('__savestep2') === 'step3', 'successful replacement advances');
expect(!$c->services['contextimage']->removed && $c->objContext->saved === 'sanitised:<p>draft</p>', 'replacement takes priority and sanitiser is used');
$c->params['imageselect'] = ''; $invoke('__savestep2');
expect($c->services['contextimage']->removed, 'explicit removal on protected save');
$before = $c->services['contextimage']->writes; ob_start(); $invoke('__removeimage'); $body = ob_get_clean();
expect($body === 'gone' && http_response_code() === 410 && $c->services['contextimage']->writes === $before, 'old endpoint is inert');
echo "PASS information authority, method, scope, CSRF, draft recovery, replacement/removal and retired endpoint\n";
