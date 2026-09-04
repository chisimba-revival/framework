<?php
/** Canonical password-recovery route contract. */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$interface = file_get_contents($root . '/classes/logininterface_class_inc.php');
$error = file_get_contents($root . '/templates/content/error_message.php');
$splash = file_get_contents($root . '/classes/splashscreen_class_inc.php');
$active = $controller . $interface . $error . $splash;
$checks = array(
    'legacy request redirects to canonical recovery' => str_contains(
        $controller,
        "case 'needpassword':"
    ) && str_contains($controller, "'forgotpassword'")
        && str_contains($controller, "'registration-service'"),
    'active login surfaces use canonical recovery' => substr_count(
        $active,
        "'registration-service'"
    ) >= 4,
    'active login surfaces do not generate legacy recovery links' => !str_contains(
        $interface . $error . $splash,
        "'action'=>'needpassword'"
    ) && !str_contains(
        $interface . $error . $splash,
        "'action' => 'needpassword'"
    ),
    'unsafe legacy eLearning login is retired' => !file_exists(
        $root . '/classes/block_elearnlogin_class_inc.php'
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
