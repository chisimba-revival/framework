<?php
$source = file_get_contents(
    __DIR__.'/../../app/core_modules/security/controller.php'
);
function p129LogoutAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}
p129LogoutAssert(
    strpos($source, "array('authenticated', 'logout')") !== false,
    'authenticated landing and logout require an authenticated session'
);
p129LogoutAssert(strpos($source, "case 'logout':") !== false,
    'logout remains a declared controller action');
p129LogoutAssert(strpos($source, 'LOGOUT_CSRF_CONTEXT') !== false,
    'logout retains its CSRF boundary');
echo "ALL P129 LOGOUT ACCESS BOUNDARY TESTS PASSED\n";
