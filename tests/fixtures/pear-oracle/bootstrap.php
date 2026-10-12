<?php
/** Historical comparison fixtures, excluded from deployed app/. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
set_include_path(__DIR__ . PATH_SEPARATOR . dirname(__DIR__, 3) . '/app/lib/pear' . PATH_SEPARATOR . get_include_path());
