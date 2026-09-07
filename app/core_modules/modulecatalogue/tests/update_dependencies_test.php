<?php
/** Dependency failures must offer the existing installer without hiding names. */
class controller {
    public $installer;
    public function getPatchObject($module) { return $this->installer; }
    public function uri($params, $module) {
        return '?module=' . $module . '&amp;' . http_build_query($params);
    }
}

class customException extends Exception {}
require dirname(__DIR__) . '/controller.php';
$reflection = new ReflectionClass('modulecatalogue');
$catalogue = $reflection->newInstanceWithoutConstructor();
$language = $reflection->getProperty('objLanguage');
$language->setValue($catalogue, new class {
    public function languageText($key, $module) {
        return array(
            'mod_modulecatalogue_updatedeps' => 'Install dependencies for {MODULE}',
            'mod_modulecatalogue_unmetdependencies' => 'Uninstalled dependencies',
            'mod_modulecatalogue_downloadmissing' => 'Download missing dependencies',
            'mod_modulecatalogue_update_failed' => 'Update failed',
            'mod_modulecatalogue_regconfirm' => 'Installed [MODULE]',
        )[$key];
    }
});
$message = $reflection->getMethod('patchFailureMessage');
$actions = $reflection->getMethod('patchDependencyActions');
$result = array('unMetDep' => 'help', 'modules' => array('ui'), 'missing' => array());
$links = $actions->invoke($catalogue, 'help', $result);
$checks = array(
    'dependency is named' => $message->invoke($catalogue, 'help', $result)
        === 'help: Uninstalled dependencies: ui',
    'installer is offered with module and decoded URL' => count($links) === 1
        && $links[0]['label'] === 'Install dependencies for help'
        && $links[0]['url'] === '?module=modulecatalogue&action=updatedeps&modname=help',
    'ordinary failure has no install action' => $actions->invoke($catalogue, 'help', false) === array(),
    'ordinary failure retains module name' => $message->invoke($catalogue, 'help', false) === 'help: Update failed',
);
$result['missing'] = array('unavailable');
$checks['absent source is named'] = str_contains(
    $message->invoke($catalogue, 'help', $result), 'Download missing dependencies: unavailable'
);
$checks['absent source prevents incomplete installation offer'] =
    $actions->invoke($catalogue, 'help', $result) === array();
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}

$events = new ArrayObject();
$catalogue->installer = new class($events) {
    public function __construct(private $events) {}
    public function preinstall($version) { $this->events[] = 'pre:' . $version; }
    public function postinstall($version) { $this->events[] = 'post:' . $version; }
};
$objects = array(
    'objModFile' => new class {
        public function findRegisterFile($module) { return $module; }
        public function readRegisterFile($module) {
            return $module === 'help'
                ? array('DEPENDS' => array('ui'), 'MODULE_VERSION' => '0.308')
                : array('MODULE_ID' => 'ui');
        }
    },
    'objModule' => new class {
        public function checkIfRegistered($module, $unused = null) { return false; }
    },
    'objModuleAdmin' => new class($events) {
        public $succeeds = true;
        public $output = '';
        public function __construct(private $events) {}
        public function installModule($registration) {
            $this->events[] = 'install:' . $registration['MODULE_ID'];
            echo 'installer progress';
            return $this->succeeds;
        }
        public function getLastError() { return 'failed'; }
    },
    'objPatch' => new class($events) {
        public $succeeds = true;
        public function __construct(private $events) {}
        public function applyUpdates($module) {
            $this->events[] = 'patch:' . $module;
            return $this->succeeds ? array('current' => '0.308') : false;
        }
    },
);
foreach ($objects as $property => $object) {
    $reflection->getProperty($property)->setValue($catalogue, $object);
}
$resume = $reflection->getMethod('updateDependenciesAndPatch');
ob_start();
$result = $resume->invoke($catalogue, 'help');
$output = ob_get_clean();
if ($result !== array('current' => '0.308') || $output !== ''
    || $events->getArrayCopy() !== array('install:ui', 'pre:0.308', 'patch:help', 'post:0.308')) {
    throw new Exception('Dependencies must install before the parent patch and hooks, without leaking output');
}
echo "PASS: dependency installation resumes original patch and hooks\n";
$events->exchangeArray(array());
$objects['objPatch']->succeeds = false;
$resume->invoke($catalogue, 'help');
if ($events->getArrayCopy() !== array('install:ui', 'pre:0.308', 'patch:help')) {
    throw new Exception('Failed patch must not run postinstall');
}
echo "PASS: failed parent patch skips postinstall\n";
$events->exchangeArray(array());
$objects['objModuleAdmin']->succeeds = false;
$level = ob_get_level();
try {
    $resume->invoke($catalogue, 'help');
    throw new Exception('Dependency failure must stop the parent patch');
} catch (customException $expected) {
    if ($events->getArrayCopy() !== array('install:ui') || ob_get_level() !== $level) {
        throw new Exception('Dependency failure ran the patch or leaked an output buffer');
    }
}
echo "PASS: dependency failure stops parent patch and restores output buffer\n";
