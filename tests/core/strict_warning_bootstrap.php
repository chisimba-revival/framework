<?php
/** Opt-in test bootstrap: fail on every warning, notice and deprecation. */
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
