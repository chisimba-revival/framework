<?php
/**
 * Permanent contract for cookie-only canonical session resumption.
 *
 * @author Derek Keats
 */
$root = dirname(__DIR__, 2);
$enginePath = $root . '/app/classes/core/engine_class_inc.php';
$engine = file_get_contents($enginePath);

function contract($condition, $label)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
    echo "PASS: {$label}\n";
}

$cookie = 'isset($_COOKIE[\'PHPSESSION\'])';
$start = '$this->sessionStart();';
$legacy = '$_REQUEST [session_name ()]';

contract(
    substr_count($engine, $cookie) === 1,
    'engine resumes the application-owned PHPSESSION cookie exactly once'
);
contract(
    strpos($engine, 'is_string($_COOKIE[\'PHPSESSION\'])') !== false,
    'session cookie is type-checked before resumption'
);
contract(
    strpos($engine, '$_COOKIE[\'PHPSESSION\'] !== \'\'') !== false,
    'empty session identifiers are rejected'
);
contract(
    strpos($engine, $legacy) === false,
    'session identifiers are no longer accepted through request parameters'
);
contract(
    strpos($engine, $cookie) < strpos($engine, $start),
    'cookie presence gates session startup'
);
