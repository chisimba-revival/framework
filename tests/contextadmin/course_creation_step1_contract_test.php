<?php
function step1Assert($condition, $message)
{
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
    echo "PASS: $message\n";
}

$root = dirname(__DIR__, 2) . '/app/core_modules/';
$controller = file_get_contents($root . 'contextadmin/controller.php');
$service = file_get_contents($root . 'contextadmin/classes/coursecreationservice_class_inc.php');
$context = file_get_contents($root . 'context/classes/dbcontext_class_inc.php');
$template = file_get_contents($root . 'contextadmin/templates/content/step1.php');
$sql = file_get_contents($root . 'context/sql/tbl_context.sql');

foreach (array($controller, $service, $context, $template, $sql) as $source) {
    step1Assert($source !== false, 'contract target is readable');
}

step1Assert(strpos($controller, "const CSRF_CONTEXT = 'contextadmin_step1'") !== false,
    'Step 1 has a dedicated CSRF context');
step1Assert(strpos($controller, '!$this->isPost()') !== false,
    'Step 1 mutations require POST');
step1Assert(strpos($controller, '$this->objUser->isAdmin()') !== false,
    'course creation requires server-side administrator authorisation');
step1Assert(strpos($controller, '$this->canManageContext($contextCode)') !== false,
    'course editing performs a server-side context permission decision');
step1Assert(strpos($controller, 'Todo - Check Permissions') === false,
    'the known edit permission TODO is removed');

step1Assert(strpos($service, '$this->context->beginTransaction();') !== false
    && strpos($service, '$this->context->commitTransaction();') !== false
    && strpos($service, '$this->context->rollbackTransaction();') !== false,
    'course creation orchestration owns the shared transaction');
step1Assert(strpos($service, "getObject('cpdservice', 'cpd')") !== false,
    'optional CPD recognition uses the canonical CPD service');
step1Assert(strpos($service, 'tbl_cpd_') === false,
    'course creation never writes CPD tables directly');
step1Assert(strpos($service, "checkIfRegistered('cpd', 'cpd')") !== false,
    'non-CPD course creation remains independent of CPD installation');
step1Assert(strpos($service, 'if ($cpd) {' . "\n" . "            \$access = 'Private';") !== false,
    'CPD course access is forced to Private at the service boundary');

step1Assert(strpos($context, "array('standard', 'microlearning')") !== false,
    'delivery formats are strictly validated');
step1Assert(strpos($context, "array('sequential', 'backward', 'free')") !== false,
    'navigation modes are strictly validated');
step1Assert(strpos($context, "'microlearning' ? 'backward' : 'free'") !== false,
    'format-specific navigation defaults are canonical');
step1Assert(strpos($sql, "'delivery_format'") !== false && strpos($sql, "'navigation_mode'") !== false,
    'context-owned learning design fields are installed canonically');

step1Assert(strpos($template, "new hiddeninput('csrf_token'") !== false,
    'Step 1 form submits its CSRF token');
step1Assert(strpos($template, 'DD-MM-YYYY') !== false,
    'human-facing CPD dates are explicitly day-first');
step1Assert(strpos($template, 'mm/dd') === false,
    'Step 1 does not expose American date guidance');
step1Assert(strpos($template, 'CPD recognition starts (DD-MM-YYYY)') !== false
    && strpos($template, 'CPD recognition ends (DD-MM-YYYY)') !== false,
    'CPD recognition date labels explain what is valid');
step1Assert(strpos($template, 'previousAccess') !== false
    && strpos($template, 'access.filter("[value=Private]")') !== false,
    'enabling CPD selects Private and disabling it restores the previous access choice');
step1Assert(strpos($template, 'CPD courses are private so learner identity') !== false,
    'the Private access rule is explained to administrators');

echo "ALL SECURE COURSE CREATION STEP 1 CONTRACTS PASSED\n";
?>
