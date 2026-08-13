<?php
/**
 * CHISIMBA READ-ONLY SHARED CSS ASSEMBLY
 *
 * Emit the ordered shared stylesheets directly. Production releases are
 * immutable, so this endpoint must never create a cache file beside source.
 */

header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$cssFiles = array(
    'layout.css',
    'common2.css',
    'htmlelements.css',
    'creativecommons.css',
    'forum.css',
    'calendar.css',
    'cms.css',
    'stepmenu.css',
    'switchmenu.css',
    'colorboxes.css',
    'manageblocks.css',
    'facebox.css',
    'modernbrickmenu.css',
    'jquerytags.css',
    'overlappingtabs.css',
    'login.css',
    'navigationmenu.css',
    'modulespecific.css',
    'cssdropdownmenu.css',
    'sexybuttons.css',
    'chisimbacanvas.css',
    'filemanager.css',
);

foreach ($cssFiles as $cssFile) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $cssFile;
    if (!is_file($path) || !is_readable($path)) {
        continue;
    }
    readfile($path);
    echo "\n";
}
