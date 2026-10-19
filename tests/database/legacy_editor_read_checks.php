<?php
/** Textblock and Slate read boundaries; disposable PDO runner only. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
require_once '/modules/textblock/classes/dbtextblock_class_inc.php';
require_once '/modules/slate/classes/dbslatepages_class_inc.php';
$temporaryTables = ['tbl_textblock', 'tbl_slate_pages', 'tbl_module_blocks'];
try {
    $table->executePrepared('CREATE TEMPORARY TABLE tbl_textblock (id VARCHAR(64), blockid VARCHAR(64), title VARCHAR(250), blocktext TEXT, css_id VARCHAR(250), css_class VARCHAR(250), show_title VARCHAR(1), modified DATETIME, datemodified DATETIME, datecreated DATETIME, creatorid VARCHAR(32), modifierid VARCHAR(32))');
    $table->executePrepared('CREATE TEMPORARY TABLE tbl_slate_pages (id VARCHAR(64), title VARCHAR(250), page VARCHAR(250), description TEXT, datecreated DATETIME)');
    $table->executePrepared('CREATE TEMPORARY TABLE tbl_module_blocks (id VARCHAR(64), moduleid VARCHAR(50), blockname VARCHAR(150))');
    $table->executePrepared('INSERT INTO tbl_textblock (id,blockid,title) VALUES (?, ?, ?), (?, ?, ?)', ["quoted'id", 'text1', 'Narrow', 'wide', 'widetext1', 'Wide']);
    $table->executePrepared('INSERT INTO tbl_slate_pages (id,title) VALUES (?, ?), (?, ?)', ["quoted'id", 'Page', 'second', 'Other page']);
    $table->executePrepared('INSERT INTO tbl_module_blocks VALUES (?, ?, ?), (?, ?, ?), (?, ?, ?)', ["catalogue'id", 'textblock', 'text1', 'foreign', 'othermodule', 'text1', 'unconfigured', 'textblock', 'text2']);
    $textblocks = new dbtextblock();
    injectCallerTable($textblocks, 'tbl_textblock');
    $blockCatalogue = new dbTable();
    injectCallerTable($blockCatalogue, 'tbl_module_blocks');
    (new ReflectionProperty(dbtextblock::class, 'objBlock'))->setValue($textblocks, $blockCatalogue);
    $slates = new dbslatepages();
    injectCallerTable($slates, 'tbl_slate_pages');
    ensureBaseline($textblocks->getBlockById("quoted'id")[0]['title'] === 'Narrow', 'Textblock quoted ID lookup');
    ensureBaseline($slates->getPageById("quoted'id")[0]['title'] === 'Page', 'Slate quoted ID lookup');
    foreach (["' OR 1=1 -- ", 'missing'] as $id) {
        ensureBaseline($textblocks->getBlockById($id) === [], 'Textblock ID must not broaden lookup');
        ensureBaseline($slates->getPageById($id) === [], 'Slate ID must not broaden lookup');
        ensureBaseline($textblocks->getBlock($id) === null, 'Textblock catalogue ID must not broaden lookup');
    }
    ensureBaseline($textblocks->getBlock("catalogue'id")['title'] === 'Narrow', 'Textblock catalogue lookup');
    ensureBaseline($textblocks->getBlock('foreign') === null, 'Foreign module block rejected');
    ensureBaseline($textblocks->getBlock('unconfigured') === null, 'Unconfigured block has no row');
    ensureBaseline($textblocks->getArUsedBlockss('text') === ['text1'], 'Narrow block selection');
    ensureBaseline($textblocks->getArUsedBlockss('widetext') === ['widetext1'], 'Wide block selection');
    foreach (['%', '_', "text' OR 1=1 -- ", 'unknown'] as $type) {
        ensureBaseline($textblocks->getArUsedBlockss($type) === null, 'Invalid block type rejected');
    }
    require __DIR__ . '/legacy_editor_write_checks.php';
    echo "PASS: Textblock/Slate bound reads, quoted IDs, injection rejection, missing and foreign blocks\n";
} finally {
    foreach ($temporaryTables as $name) $table->executePrepared('DROP TEMPORARY TABLE IF EXISTS ' . $name);
}
