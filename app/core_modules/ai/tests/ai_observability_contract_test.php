<?php
/** Static safety and presentation contracts for AI observability. */
$root = dirname(__DIR__);
$service = file_get_contents($root . '/classes/aiservice_class_inc.php');
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/dashboard_tpl.php');
$schema = file_get_contents($root . '/sql/tbl_ai_requests.sql');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'service exposes filtered analytics'=>strpos($service, 'usageAnalytics(array $filters') !== false,
    'period filter is bounded'=>strpos($service, 'array(7, 30, 90)') !== false,
    'dimensions are validated'=>strpos($service, 'safeDimension') !== false,
    'cost is calculated not persisted'=>strpos($service, 'estimateCost') !== false
        && strpos($schema, 'estimated_cost') === false,
    'canonical time service is used'=>strpos($service, "getObject('timeanddateservice', 'timeanddate-service')") !== false
        && strpos($service, "date('Y-m-d H:i:s')") === false,
    'no content fields are stored'=>strpos($schema, "'prompt'") === false
        && strpos($schema, "'response'") === false
        && strpos($schema, "'input_text'") === false,
    'controller validates dashboard filters'=>strpos($controller, 'analyticsFilters') !== false,
    'dashboard uses shared icon service'=>strpos($template, "getObject('iconservice', 'ui')") !== false,
    'dashboard includes visual trend'=>strpos($template, 'dashboard-bar-chart') !== false,
    'dashboard includes reliability and cost'=>strpos($template, 'Success rate') !== false
        && strpos($template, 'Estimated cost') !== false,
    'pricing is administrator configured'=>strpos($register, 'AI_OPENAI_INPUT_COST_PER_MILLION') !== false
        && strpos($register, 'AI_OPENAI_OUTPUT_COST_PER_MILLION') !== false,
);
$failed = array_keys(array_filter($checks, static function ($passed) { return !$passed; }));
if ($failed) { fwrite(STDERR, 'Failed: ' . implode(', ', $failed) . PHP_EOL); exit(1); }
echo 'AI observability contracts passed (' . count($checks) . ').' . PHP_EOL;
