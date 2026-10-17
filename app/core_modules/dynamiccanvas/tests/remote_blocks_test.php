<?php
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
class controller extends ChisimbaObject {
    public $action = 'proxy';
    public $vars = [];
    public function getParam($key, $default = null) { return $key === 'action' ? $this->action : $default; }
    public function setVar($key, $value) { $this->vars[$key] = $value; }
    public function getObject($name, $module = null) { throw new RuntimeException('Unexpected service/network call'); }
}
require dirname(__DIR__) . '/classes/blockfilter_class_inc.php';
require dirname(__DIR__) . '/controller.php';
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
$language = new class {
    public function code2Txt(...$args) { return 'Remote blocks retired'; }
    public function languageText(...$args) { return 'Invalid block'; }
};
$filter = new blockfilter();
$filter->objLanguage = $language;
$filter->objBlockFilter = new class {
    public $configData;
    public function blockExists($block, $module) { return $block === 'fixture' && $module === 'fixture'; }
    public function showBlock(...$args) { return 'LOCAL BLOCK'; }
};
check(str_contains($filter->parse('{"display":"externalblock","server":"http://127.0.0.1/"}'), 'Remote blocks retired'), 'Remote import must not fetch');
check($filter->parse('{"display":"block","module":"fixture","block":"fixture"}') === 'LOCAL BLOCK', 'Local blocks must work');
check(!str_contains($filter->parse('{"display":"block","module":"fixture","block":"<img src=x>"}'), '<img'), 'Invalid markup escaped');
check(!method_exists($filter, 'proxy') && !method_exists($filter, 'renderEmbed'), 'Remove obsolete transports');
$c = new dynamiccanvas(); $c->objLanguage = $language;
check($c->dispatch() === 'dump_tpl.php' && http_response_code() === 410, 'Retired anonymous proxy');
$c->action = 'getMethod';
check($c->dispatch() === 'dump_tpl.php', 'Internal helpers not routable');
echo "PASS retired transports, local rendering, escaped diagnostics, action allowlist\n";
