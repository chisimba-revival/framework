<?php
/** Null/empty rendering contracts with all PHP warnings promoted to failures. */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$GLOBALS['kewl_entry_point_run'] = true;
$app = dirname(__DIR__, 2) . '/app';
require $app . '/classes/core/object_class_inc.php';
require $app . '/core_modules/htmlelements/classes/textinput_class_inc.php';
require $app . '/core_modules/htmlelements/classes/dropdown_class_inc.php';
require $app . '/core_modules/htmlelements/classes/button_class_inc.php';
function componentCheck($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$input = new textinput('empty');
componentCheck($input->value === '', 'An omitted input value is empty text');
$input = new textinput('quoted', 'Café "<tag>" &');
componentCheck(html_entity_decode($input->value) === 'Café "<tag>" &', 'Input escaping preserves text');
$select = new dropdown('choice');
$select->addOption();
$select->addOption('', 'Choose');
$select->addOption('0', 'Zero');
$select->setSelected(null);
componentCheck(str_contains($select->show(), 'Choose'), 'Null selection preserves the empty option');
$select->setSelected('0');
componentCheck(str_contains($select->show(), 'value="0" selected'), 'Zero remains a valid selection');
$button = new button('save', 'Save');
$button->setOnClick('return false;');
componentCheck(str_contains($button->show(), 'Save'), 'Button state renders without dynamic properties');
echo "PASS: strict empty/null, escaped Unicode, zero-selection and button rendering\n";
