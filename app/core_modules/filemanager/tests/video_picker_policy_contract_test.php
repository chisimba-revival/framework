<?php
/**
 * Managed video picker policy regression contract.
 *
 * @category Tests
 * @package  filemanager
 * @author   Derek Keats
 */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$api = file_get_contents($root . '/classes/fileapi_class_inc.php');
$template = file_get_contents($root . '/templates/content/api_filepicker_tpl.php');
$register = file_get_contents($root . '/register.conf');

$checks = array(
    'controller permits video policy' => str_contains($controller, "'audio', 'video', 'pdf'"),
    'video formats are constrained' => str_contains($api, "'extensions' => array('mp4', 'webm', 'ogv')")
        && str_contains($api, "'mimetypes' => array('video/mp4', 'application/mp4', 'video/webm', 'video/ogg')"),
    'picker has a video icon' => str_contains($template, "'video' => '<svg class=\"picker-icon picker-video-icon\""),
    'video picker language exists' => str_contains($register, 'mod_filemanager_picker_select_video')
        && str_contains($register, 'mod_filemanager_picker_upload_video'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
