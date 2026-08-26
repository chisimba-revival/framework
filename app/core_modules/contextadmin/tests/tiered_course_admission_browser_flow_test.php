<?php
/** Static browser-flow contract for deliberate course-policy migration. */
$module = dirname(__DIR__);
$framework = dirname(dirname($module));
$read = function ($path) {
    $content = file_get_contents($path);
    if ($content === false) { throw new RuntimeException('Unable to read ' . $path); }
    return $content;
};
$expect = function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
};

$form = $read($module . '/templates/content/step1.php');
$controller = $read($module . '/controller.php');
$creation = $read($module . '/classes/coursecreationservice_class_inc.php');
$editForm = $read(dirname($module) . '/context/classes/contextforms_class_inc.php');

foreach (array('public', 'free', 'tier_1', 'tier_2', 'private') as $policy) {
    $needle = "addOption('" . $policy . "'";
    $expect(strpos($form, $needle) !== false, 'Missing admission choice: ' . $policy);
}
$expect(strpos($form, "'open'=>'free'") !== false,
    'Legacy Open courses must be presented as Free without changing admission semantics.');
$expect(strpos($form, "addOption('private', '<strong>Paid separately</strong>')") !== false
    && strpos($editForm, "addOption('private', '<strong>Paid separately</strong>')") !== false,
    'Both forms must describe the learner-facing purchase choice plainly.');
$expect(strpos($form, 'id="context_private_admission"') !== false
    && strpos($editForm, 'id="context_private_admission"') !== false
    && strpos($form, '.prop("disabled",!paid)') !== false
    && strpos($editForm, '.prop("disabled",!paid)') !== false,
    'Admission mode must be visible and submitted only for separately paid courses.');
$expect(strpos($creation, "if (\$accessPolicy !== 'private')") !== false
    && strpos($controller, "if (\$accessPolicy !== 'private')") !== false,
    'Hidden admission modes must also be discarded at the server boundary.');
$expect(strpos($form, "new radio('status')") !== false
    && strpos($editForm, "new radio('status')") !== false,
    'Two-state publication status must use radio controls consistently.');
$expect(strpos($editForm, 'Email students when course content is added or updated') !== false,
    'Legacy Alerts must explain the actual email consequence.');
$expect(substr_count($controller, "getParam('access_policy'") >= 2,
    'Create and edit flows must both accept the policy.');
$expect(strpos($creation, "'accessPolicy'") !== false,
    'Creation orchestration must persist deliberate policy selection.');

fwrite(STDOUT, "PASS: tiered course admission browser-flow contract\n");
?>
