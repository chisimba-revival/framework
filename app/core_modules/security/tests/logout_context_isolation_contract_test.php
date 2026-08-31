<?php
/**
 * Contract ensuring logout cannot leak course scope between identities.
 *
 * @author Derek Keats
 */

$source = file_get_contents(dirname(__DIR__) . '/controller.php');
if ($source === false) {
    fwrite(STDERR, "FAIL: unable to read Security controller\n");
    exit(1);
}

$required = array(
    '$this->clearContextScope();',
    '$this->destroyApplicationSession()',
    "->leaveContext()",
    "'contextCode'",
    "\$this->unsetSession(\$key, 'context');",
    '$_SESSION = array();',
    'session_destroy()',
    'session_get_cookie_params()',
);
foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: logout does not clear active course scope\n");
        exit(1);
    }
}

$clearPosition = strpos($source, '$this->clearContextScope();');
$destroyPosition = strpos($source, "\$stack['sessions']->destroy()");
if ($clearPosition === false || $destroyPosition === false
    || $clearPosition > $destroyPosition) {
    fwrite(STDERR, "FAIL: authentication is destroyed before course cleanup\n");
    exit(1);
}

echo "PASS: logout clears course scope before authentication teardown.\n";
