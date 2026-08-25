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

foreach (array('public', 'free', 'tier_1', 'tier_2', 'private') as $policy) {
    $needle = "addOption('" . $policy . "'";
    $expect(strpos($form, $needle) !== false, 'Missing admission choice: ' . $policy);
}
$expect(strpos($form, "'open'=>'free'") !== false,
    'Legacy Open courses must be presented as Free without changing admission semantics.');
$expect(strpos($form, 'controls entry only') !== false,
    'The form must distinguish admission from course permissions.');
$expect(substr_count($controller, "getParam('access_policy'") >= 2,
    'Create and edit flows must both accept the policy.');
$expect(strpos($creation, "'accessPolicy'") !== false,
    'Creation orchestration must persist deliberate policy selection.');

fwrite(STDOUT, "PASS: tiered course admission browser-flow contract\n");
?>
