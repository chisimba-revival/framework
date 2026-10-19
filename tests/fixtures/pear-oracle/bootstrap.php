<?php
/** Historical comparison fixtures, excluded from deployed app/. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());
