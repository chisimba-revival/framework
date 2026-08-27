<?php
/** Verify direct course settings can safely change course format. */
$root = dirname(__DIR__);
$forms = file_get_contents($root . '/classes/contextforms_class_inc.php');
$controller = file_get_contents($root . '/controller.php');

$checks = array(
    'settings use the canonical course-format field' => str_contains(
        $forms,
        "new dropdown('delivery_format')"
    ),
    'all validated formats are editable' => str_contains($forms, "'standard'")
        && str_contains($forms, "'microlearning'")
        && str_contains($forms, "'masterclass'"),
    'saved format is selected' => str_contains($forms, "\$context['delivery_format']")
        && str_contains($forms, '$deliveryFormat->setSelected($selectedFormat)'),
    'format guidance follows the selected option' => str_contains(
        $forms,
        'context-delivery-format-help'
    ) && str_contains($forms, 'data-format-descriptions'),
    'server validates the submitted learning design' => str_contains(
        $controller,
        '$this->objContext->validateLearningDesign('
    ) && str_contains($controller, '$learningDesign !== FALSE'),
    'existing navigation mode is preserved' => str_contains(
        $controller,
        "getField('navigation_mode', \$contextCode)"
    ) && str_contains($controller, "\$learningDesign['navigation_mode']"),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: $name\n");
}
