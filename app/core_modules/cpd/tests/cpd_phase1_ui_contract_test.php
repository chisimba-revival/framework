<?php
/**
 * Static contract checks for the CPD Phase 1 interface.
 *
 * @author  Derek Keats
 * @package cpd
 */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$service = file_get_contents($root . '/classes/cpdservice_class_inc.php');
$template = file_get_contents($root . '/templates/content/cpd_home_tpl.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'controller uses canonical service' => strpos($controller, "getObject('cpdservice', 'cpd')") !== false,
    'controller uses canonical user service' => strpos($controller, "getObject('userservice', 'security')") !== false,
    'controller uses native CSRF composition' => strpos($controller, "getObject('nativeauthwebcomposition', 'security')") !== false,
    'mutations require POST' => strpos($controller, "REQUEST_METHOD") !== false && strpos($controller, "'POST'") !== false,
    'service exposes scheme reads' => strpos($service, 'function listSchemes') !== false,
    'service exposes category reads' => strpos($service, 'function listCategories') !== false,
    'service exposes current recognition' => strpos($service, 'function currentRecognition') !== false,
    'service exposes context history' => strpos($service, 'function historyForContext') !== false,
    'canonical user IDs have separate validation' => strpos($service, 'function userId') !== false,
    'actors use canonical user ID validation' => substr_count($service, "userId(\$input['actorUserId']") >= 4,
    'learner history uses canonical user ID validation' => strpos($service, '$user = $this->userId($userId)') !== false,
    'interface localises service errors' => strpos($controller, 'function localiseResult') !== false,
    'template does not expose service error codes' => strpos($template, "\$cpdResult['code']") === false,
    'template escapes output' => strpos($template, 'htmlspecialchars') !== false,
    'template has no direct CPD table access' => strpos($template, 'tbl_cpd_') === false,
    'category creation is available in context' => strpos($template, 'cpdContextCode') !== false && substr_count($template, "\$action('createcategory')") >= 2,
    'empty categories block recognition' => strpos($template, "cpdText['nocategories']") !== false,
    'dates use explicit day month year input' => strpos($template, 'placeholder="DD-MM-YYYY"') !== false && strpos($template, 'type="date"') === false,
    'controller canonicalises date input' => strpos($controller, 'function canonicalDate') !== false,
    'history dates are displayed day month year' => strpos($template, '$displayDate') !== false,
    'context terminology is resolved' => strpos($controller, "str_ireplace('[-CONTEXT-]'" ) !== false,
    'recognition wording is non-technical' => strpos($register, 'Save CPD settings') !== false && strpos($register, 'Save recognition version') === false,
    'module exposes admin page' => strpos($register, 'MODULE_HASADMINPAGE: 1') !== false,
);
$failed = 0;
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS: ' : 'FAIL: ') . $name . PHP_EOL;
    if (!$ok) { $failed++; }
}
exit($failed === 0 ? 0 : 1);
?>
