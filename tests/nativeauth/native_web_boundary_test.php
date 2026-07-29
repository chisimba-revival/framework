<?php
/**
 * Structural contract for the complete native authentication web boundary.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$root = dirname(__DIR__, 2);
$controller = file_get_contents(
    $root . '/app/core_modules/security/controller.php'
);
$login = file_get_contents(
    $root . '/app/core_modules/security/templates/content/native_login_tpl.php'
);
$landing = file_get_contents(
    $root
    . '/app/core_modules/security/templates/content/'
    . 'native_authenticated_tpl.php'
);
$installer = file_get_contents(
    $root . '/app/installer/extra/ajax_install.js'
);

function nativeWebContract($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

nativeWebContract(
    strpos(
        $installer,
        '../index.php?module=security&action=showlogin'
    ) !== false,
    'installer ends at native login'
);
nativeWebContract(
    strpos($controller, "['guarded_login']->begin(") !== false,
    'credentials enter guarded application service'
);
nativeWebContract(
    strpos($controller, "case 'authenticated'") !== false
        && strpos($controller, "return 'native_authenticated_tpl.php'") !== false,
    'successful authentication has a deliberate landing'
);
nativeWebContract(
    strpos($controller, "case 'logout'") !== false
        && strpos($controller, 'if (!$this->isPost())') !== false
        && strpos(
            $controller,
            "consume(\n            self::LOGOUT_CSRF_CONTEXT"
        ) !== false
        && strpos($controller, "['sessions']->destroy()") !== false
        && strpos(
            $controller,
            "['persistent']->revokeAllForUser("
        ) !== false,
    'logout is POST and CSRF guarded and destroys canonical state'
);
nativeWebContract(
    strpos($login, 'name="native_auth_begin"') !== false
        && strpos($login, 'name="username"') !== false
        && strpos($login, 'name="password"') !== false
        && strpos($login, 'name="remember"') !== false,
    'native login form exposes only the canonical login inputs'
);
foreach (array(
    'ldap', 'twitter', 'facebook', 'openid', 'LiveUser', 'logoff',
) as $forbidden) {
    nativeWebContract(
        stripos($controller . $login . $landing, $forbidden) === false,
        'native web boundary excludes ' . $forbidden
    );
}
nativeWebContract(
    strpos($landing, 'method="post"') !== false
        && strpos($landing, 'name="native_auth_logout"') !== false,
    'native landing owns the POST logout form'
);

echo "ALL NATIVE WEB BOUNDARY CONTRACTS PASSED\n";
