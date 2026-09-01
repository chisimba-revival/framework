<?php
/** @author Derek Keats */
$source = file_get_contents(dirname(__DIR__) . '/classes/datetimepicker_class_inc.php');
$checks = array(
    'native date input' => str_contains($source, 'type="date"'),
    'native time input' => str_contains($source, 'type="time"'),
    'accessible labels' => substr_count($source, '<label for=') === 2,
    'stable paired names' => str_contains($source, "\$this->name . '_date'")
        && str_contains($source, "\$this->name . '_time'"),
    'no server-local clock' => !preg_match('/\b(?:date|time|strtotime)\s*\(/', $source),
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
echo 'OK: ' . count($checks) . " shared date-time picker checks\n";
?>
