<?php
/**
 * Guard the course-membership authorisation boundary against dispatcher
 * regressions. This checks the central route gate and mutation-level defence
 * in depth without requiring a configured database.
 */
$frameworkRoot = dirname(__DIR__, 3);
$access = file_get_contents($frameworkRoot . '/classes/core/access_class_inc.php');
$controller = file_get_contents(dirname(__DIR__) . '/controller.php');
$register = file_get_contents(dirname(__DIR__) . '/register.conf');

$policyCheck = strpos(
    $access,
    'if ($this->requiresLogin($action) && !$this->isValid($action))'
);
$dispatch = strpos($access, 'return $module->dispatch($action);');
$mutation = strpos($controller, 'private function mutationValidationError()');
$authority = strpos(
    $controller,
    "if (!\$this->canManageMembers()) {",
    $mutation === false ? 0 : $mutation
);

$checks = array(
    'dispatcher evaluates controller authorisation before dispatch' =>
        $policyCheck !== false && $dispatch !== false && $policyCheck < $dispatch,
    'membership mutations independently require manager authority' =>
        $mutation !== false && $authority !== false,
    'denial text is registered' => str_contains(
        $register,
        'mod_contextgroups_err_notauthorised'
    ),
    'module version records the security repair' => preg_match(
        '/^MODULE_VERSION:\s+1\.086$/m',
        $register
    ) === 1,
);

foreach ($checks as $label => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

echo "PASS: context-membership authorisation dispatch contract verified.\n";
