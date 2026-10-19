<?php
/** Regression for old skin sessions after optional-plugin retirement. */
if (PHP_SAPI !== 'cli') exit(64);
error_reporting(E_ALL);
set_error_handler(static function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
$GLOBALS['kewl_entry_point_run'] = true;
const APP_ROOT = __DIR__ . '/../../../';
class ChisimbaObject
{
    public $objConfig;
    public static array $session = [];
    public static bool $suppress = false;
    public function getObject($name, $module)
    {
        if ($name === 'altconfig') return new class {
            public function getSiteRootPath() { return APP_ROOT; }
        };
        if ($name === 'dbsysconfig') return new class {
            public function getValue($key, $module) { return $key === 'JQUERY_CORE' ? '1.7.2' : 'base'; }
        };
        if ($name === 'coreloader') { $loader = new coreloader(); $loader->init(); return $loader; }
        throw new RuntimeException('Unexpected dependency ' . $name);
    }
    public function getResourcePath($file, $module) { return APP_ROOT . 'core_modules/' . $module . '/resources/' . $file; }
    public function getResourceUri($file) { return '/core_modules/jquerycore/resources/' . $file; }
    public function getJavascriptFile($file) { return '<script src="' . $this->getResourceUri($file) . '"></script>'; }
    public function getSession($key, $default) { return self::$session[$key] ?? $default; }
    public function getVar($key, $default) { return $key === 'SUPPRESS_JQUERY' ? self::$suppress : $default; }
}
require __DIR__ . '/../classes/coreloader_class_inc.php';
require APP_ROOT . 'core_modules/skin/classes/skinjavascript_class_inc.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function render(array $selection): string
{
    ChisimbaObject::$session = ['plugins' => $selection, 'unrelated_preference' => 'keep me'];
    $before = ChisimbaObject::$session;
    $skin = new skinjavascript();
    $skin->objModules = new class { public function checkIfRegistered($name) { return $name === 'jquerycore'; } };
    $html = $skin->getJQuery();
    check(ChisimbaObject::$session === $before, 'Skin must not clear or rewrite the session');
    return (string) $html;
}
$retired = ['tooltip', 'flexigrid', 'bgiframe', 'delegate', 'dimensions', 'pngfix', 'corner', 'oembed', 'livequery', 'form', 'validate'];
$retained = [];
$baseline = render([]);
check(str_contains($baseline, 'core/1.7.2/jquery.min.js'), 'Configured core missing');
check(str_contains($baseline, 'ui/javascript/1.8.18/jquery-ui.min.js'), 'UI changed');
check(str_contains($baseline, 'ui/themes/base/jquery.ui.all.css'), 'Theme changed');
check(render($retired) === $baseline, 'Old session must not emit retired plugin URLs');
check(render(['unknown-plugin', '../tooltip']) === $baseline, 'Unknown selections must be ignored');
foreach ($retained as $plugin) {
    $expected = render([$plugin]);
    check(substr_count($expected, '/plugins/' . $plugin . '/') === 1, 'Retained plugin missing or duplicated: ' . $plugin);
    check(render(array_merge($retired, [$plugin, $plugin, 'unknown-plugin'])) === $expected, 'Mixed stale selections changed retained plugin: ' . $plugin);
}
$loader = new coreloader(); $loader->init(); $loader->setCoreVersion('1.7.2');
foreach ($retired as $plugin) $loader->setPlugin($plugin);
check($loader->load() === $baseline, 'Single-plugin API must reject retired selections');
$loader->setPlugin('validate'); $loader->setPlugins(['tooltip', 'form', 'validate']);
$html = $loader->load();
check($html === $baseline, 'Repeated retired selections must preserve core/UI without plugin assets');
preg_match_all('/(?:src|href)="([^"]+)"/', render(array_merge($retired, $retained)), $urls);
foreach ($urls[1] as $url) check(is_file(APP_ROOT . ltrim($url, '/')), 'Emitted missing asset: ' . $url);
ChisimbaObject::$suppress = true;
check(render(array_merge($retired, $retained)) === '', 'Suppression must remain effective');
echo "PASS empty catalogue, stale/mixed sessions, duplicate selections, core/UI/theme, asset existence and unchanged session\n";
