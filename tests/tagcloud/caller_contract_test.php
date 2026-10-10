<?php
/** Execute the real FAQ caller and file-manager template with synthetic tags. */
require __DIR__ . '/native_tagcloud_test.php';
class dbTable extends ChisimbaObject {}
$modules = $argv[1] ?? dirname(__DIR__, 3) . '/modules';
require $modules . '/faq/classes/dbfaqtags_class_inc.php';
class TagFaqFixture extends dbfaqtags {
    public function getLastLimitTags($limit = 50) { return array(array('tag'=>'Fixture & tag', 'tagcount'=>2)); }
    public function newObject($class, $module) { $object = new tagcloud(); $object->init(); return $object; }
    public function uri($args) { return '/index.php?' . http_build_query($args); }
}
$faq = new TagFaqFixture();
tagCheck(tagLinks($faq->getTagCloud()) === array(array('Fixture & tag', '/index.php?action=tag&tag=Fixture+%26+tag')), 'real FAQ incremental caller');
class TagFileFixture extends TagFaqFixture {
    public $objLanguage;
    public function render($rows) {
        $tagCloudItems = $rows;
        $this->objLanguage = new class { public function languageText($key, $module, $fallback) { return $fallback; } };
        ob_start();
        require dirname(__DIR__, 2) . '/app/core_modules/filemanager/templates/content/tagcloud_tpl.php';
        return ob_get_clean();
    }
}
$files = new TagFileFixture();
tagCheck(tagLinks($files->render(array(array('tag'=>'File tag', 'weight'=>1)))) === array(array('File tag','/index.php?action=viewbytag&tag=File+tag')), 'real file-manager template');
tagCheck(count(tagLinks($files->render(array()))) === 0, 'empty file-manager template');
echo "PASS: FAQ and file-manager caller contracts\n";
