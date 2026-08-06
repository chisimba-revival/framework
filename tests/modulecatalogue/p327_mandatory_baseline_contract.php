<?php
/**
 * Contract for mandatory baseline installation and uninstall protection.
 *
 * @category Chisimba
 * @package  modulecatalogue
 * @author   Derek Keats
 */

$root = dirname(__DIR__, 2);
$xmlPath = $root . '/app/installer/dbhandlers/systemtypes.xml';
$dbPath = $root . '/app/installer/steps/databasecreate.inc';
$adminPath = $root
    . '/app/core_modules/modulecatalogue/classes/modulesadmin_class_inc.php';
$policyPath = $root
    . '/app/core_modules/modulecatalogue/classes/mandatorymodulepolicy_class_inc.php';

function p327Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$xml = simplexml_load_file($xmlPath);
p327Assert($xml !== false, 'systemtypes XML parses');
$basic = $xml->xpath("//category[categoryname='Basic System Only']");
p327Assert(count($basic) === 1, 'one Basic System Only baseline exists');
$modules = array();
foreach ($basic[0]->module as $module) {
    $modules[] = trim((string) $module);
}
p327Assert(in_array('config', $modules, true), 'config remains mandatory');
p327Assert(
    in_array('abuseprotection', $modules, true),
    'abuseprotection is mandatory'
);
p327Assert(
    count(array_keys($modules, 'abuseprotection', true)) === 1,
    'abuseprotection occurs once in baseline'
);

$db = file_get_contents($dbPath);
p327Assert(
    substr_count($db, "core_modules/abuseprotection/sql/tbl_abuse_events.sql")
        === 1,
    'abuse events table is bootstrapped once'
);

$admin = file_get_contents($adminPath);
$guard = strpos($admin, '$objMandatoryPolicy->isMandatory($moduleId)');
$destructive = strpos($admin, '$this->objModuleBlocks->deleteModuleBlocks($moduleId)');
p327Assert($guard !== false, 'mandatory uninstall guard exists');
p327Assert($destructive !== false, 'destructive uninstall code exists');
p327Assert($guard < $destructive, 'guard precedes destructive uninstall work');
p327Assert(
    strpos($admin, "'mandatorymodulepolicy'") !== false,
    'uninstall uses canonical mandatory policy'
);

$policy = file_get_contents($policyPath);
p327Assert(
    strpos($policy, "categoryname='Basic System Only'") !== false,
    'policy reads installer baseline'
);
p327Assert(
    strpos($policy, 'return true;') !== false,
    'policy contains fail-closed outcomes'
);

echo "P327_CONTRACT=PASS\n";
