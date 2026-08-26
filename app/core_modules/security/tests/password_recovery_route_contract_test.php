<?php
/** Canonical password-recovery route contract. */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$block = file_get_contents($root . '/classes/block_elearnlogin_class_inc.php');
$interface = file_get_contents($root . '/classes/logininterface_class_inc.php');
$error = file_get_contents($root . '/templates/content/error_message.php');
$splash = file_get_contents($root . '/classes/splashscreen_class_inc.php');
$active = $controller . $block . $interface . $error . $splash;
$checks = array(
    'legacy request redirects to canonical recovery' => str_contains(
        $controller,
        "case 'needpassword':"
    ) && str_contains($controller, "'forgotpassword'")
        && str_contains($controller, "'registration-service'"),
    'active login surfaces use canonical recovery' => substr_count(
        $active,
        "'registration-service'"
    ) >= 5,
    'active login surfaces do not generate legacy recovery links' => !str_contains(
        $block . $interface . $error . $splash,
        "'action'=>'needpassword'"
    ) && !str_contains(
        $block . $interface . $error . $splash,
        "'action' => 'needpassword'"
    ),
    'login block has one understandable recovery prompt' => !str_contains(
        $block,
        'mod_security_helpmelogin'
    ),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, "PASS: canonical password recovery route contract\n");
?>
