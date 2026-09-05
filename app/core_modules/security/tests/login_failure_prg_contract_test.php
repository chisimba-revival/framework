<?php
$controller = file_get_contents(dirname(__FILE__) . '/../controller.php');
$block = file_get_contents(dirname(__FILE__)
    . '/../classes/block_login_class_inc.php');
$login = file_get_contents(dirname(__FILE__)
    . '/../classes/logininterface_class_inc.php');
$template = file_get_contents(dirname(__FILE__)
    . '/../templates/content/native_login_tpl.php');

$controllerGates = array(
    "setSession('native_auth_login_failure'",
    "'message_key' => 'mod_security_authenticationfailed'",
    "'username' => substr(trim((string) \$username), 0, 255)",
    "header('Location: ' . \$this->failedLoginPath(), true, 303)",
    "return \$front . 'index.php?module=security'",
    "checkIfRegistered('systemmanagement')",
    "return \$this->frontPagePath()",
);
$blockGates = array(
    "getSession(\n                'native_auth_login_failure'",
    "unsetSession('native_auth_login_failure')",
    'renderLoginBox(NULL, $failure)',
);
$loginGates = array(
    'renderLoginBox($module = NULL, array $state = array())',
    'class="auth-login-error" role="alert"',
    "new textinput('username', \$username, 'text', '15')",
    "isset(\$state['return_to'])",
    "isset(\$_GET['return_to'])",
);
$templateGates = array(
    'action="index.php?module=security&amp;action=login"',
);

foreach (array(
    'controller' => array($controller, $controllerGates),
    'block' => array($block, $blockGates),
    'login interface' => array($login, $loginGates),
    'native login template' => array($template, $templateGates),
) as $label => $contract) {
    foreach ($contract[1] as $gate) {
        if (strpos($contract[0], $gate) === false) {
            fwrite(STDERR, 'missing ' . $label . ' gate: '
                . $gate . PHP_EOL);
            exit(1);
        }
    }
}
$flashStart = strpos(
    $controller,
    "\$this->setSession('native_auth_login_failure'"
);
$flashEnd = strpos($controller, '));', $flashStart);
$flash = substr($controller, $flashStart, $flashEnd - $flashStart);
if (strpos($flash, "'password'") !== false) {
    fwrite(STDERR, 'password must not be persisted in failure state'
        . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'PASS: failed login uses prelogin PRG flash' . PHP_EOL);
