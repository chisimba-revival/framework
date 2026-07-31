<?php
/**
 * Contract test for lazy canonical UserService resolution by legacy user.
 *
 * @author Derek Keats
 * @category  Chisimba
 * @package   security
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */

function p89Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
}

$sourcePath = dirname(__DIR__, 2)
    . '/app/core_modules/security/classes/user_class_inc.php';
$source = file_get_contents($sourcePath);

p89Assert(
    is_string($source),
    'legacy user source could not be read'
);

$initStart = strpos($source, 'public function init(');
$nextMethod = strpos($source, 'public function', $initStart + 1);
$init = substr($source, $initStart, $nextMethod - $initStart);

p89Assert(
    strpos($init, "getObject('userservice', 'security')") === false,
    'user::init() eagerly constructs canonical UserService'
);

$fullnameStart = strpos($source, 'public function fullname(');
$nextMethod = strpos($source, 'public function', $fullnameStart + 1);
$fullname = substr($source, $fullnameStart, $nextMethod - $fullnameStart);

p89Assert(
    strpos($fullname, "getObject('userservice', 'security')") !== false
        && strpos($fullname, '->findByUserId(') !== false,
    'user::fullname() does not resolve and use canonical UserService lazily'
);
