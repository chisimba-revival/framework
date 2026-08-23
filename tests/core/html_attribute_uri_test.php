<?php
/**
 * Verify that application URIs are encoded exactly once for HTML attributes.
 */

$GLOBALS['kewl_entry_point_run'] = true;
require_once dirname(__DIR__, 2) . '/app/classes/core/object_class_inc.php';

class HtmlAttributeUriTestEngine
{
    public $arguments = array();

    public function uri($params, $module, $mode, $omitServer, $javascriptCompatibility, $strict)
    {
        $this->arguments = func_get_args();
        return '/index.php?module=' . rawurlencode($module)
            . '&action=' . rawurlencode($params['action'])
            . '&tableId=' . rawurlencode($params['tableId']);
    }
}

$engine = new HtmlAttributeUriTestEngine();
$object = new ChisimbaObject();
$object->objEngine = $engine;
$object->moduleName = 'rubric';

$uri = $object->uriForHtmlAttribute(array(
    'action' => 'edittable',
    'tableId' => 'shared rubric',
));

$expected = '/index.php?module=rubric&amp;action=edittable&amp;tableId=shared%20rubric';
if ($uri !== $expected) {
    fwrite(STDERR, "FAIL: expected $expected, received $uri\n");
    exit(1);
}
if (strpos($uri, '&amp;amp;') !== false) {
    fwrite(STDERR, "FAIL: URI contains a double-encoded query separator\n");
    exit(1);
}
if ($engine->arguments[4] !== true || $engine->arguments[5] !== true) {
    fwrite(STDERR, "FAIL: helper did not request raw strict query separators\n");
    exit(1);
}

echo "HTML attribute URI encoding contract passed.\n";
