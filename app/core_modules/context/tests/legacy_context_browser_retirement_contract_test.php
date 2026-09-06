<?php
$root = dirname(__DIR__);
$manifest = file_get_contents($root . '/register.conf');
$retired = array(
    'classes/block_browsecontext_class_inc.php',
    'resources/Ext.ux.grid.Search.js',
    'resources/contextbrowser.js',
    'resources/extcontexbrowser.js',
    'resources/othercontexts.js',
    'resources/search.js',
    'resources/usercontextslist.js',
);

$failed = array();
if (preg_match('/^WIDEBLOCK:\s*browsecontext\b/m', $manifest)) {
    $failed[] = 'browsecontext remains registered';
}
foreach ($retired as $path) {
    if (file_exists($root . '/' . $path)) {
        $failed[] = $path . ' still exists';
    }
}
if (strpos($manifest, 'BLOCK: mycontexts|postlogin') === false) {
    $failed[] = 'the maintained My Classes block was removed';
}
if ($failed) {
    fwrite(STDERR, "Legacy context browser retirement failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Legacy context browser retirement passed.\n";
