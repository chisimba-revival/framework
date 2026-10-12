<?php
/** Actual controller refusal, including forged uploads, before file or catalogue access. */
ob_start();
require __DIR__ . '/catalogue_refresh_controller_test.php';
ob_end_clean();
set_error_handler(static function ($n, $s, $f, $l) { throw new ErrorException($s, 0, $n, $f, $l); });
$fixture = tempnam(sys_get_temp_dir(), 'retired-archive-');
file_put_contents($fixture, 'synthetic archive bytes');
try {
    $c->objects = []; // Any downstream service lookup must fail this test.
    $c->params = ['action'=>'uploadarchive'];
    foreach (['GET', 'POST'] as $method) {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_FILES = ['archive'=>['name'=>'../escape.tar', 'tmp_name'=>$fixture, 'size'=>23, 'error'=>0]];
        $identity->admin = true;
        http_response_code(200);
        check($c->dispatch() === 'archive_retired_tpl.php', 'retired response template');
        check(http_response_code() === 410, 'retired HTTP status');
        check(file_get_contents($fixture) === 'synthetic archive bytes', 'upload untouched');
        $identity->admin = false;
        check($c->dispatch() === 'noaccess_tpl.php', 'existing administrator gate retained');
    }
    check(!class_exists('Archive_Tar', false), 'archive parser never loaded');
    $template = file_get_contents($app . '/core_modules/modulecatalogue/templates/content/front_tpl.php');
    check(!str_contains($template, 'uploadarchive') && !str_contains($template, 'MAX_FILE_SIZE'), 'upload controls removed');
    check(str_contains($template, 'batchinstall'), 'deployed module installation retained');
} finally {
    unlink($fixture);
    $_FILES = [];
}
echo "PASS retired archive route: GET/POST 410, access control, untouched files, no extraction, normal install retained\n";
