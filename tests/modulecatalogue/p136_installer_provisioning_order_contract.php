<?php
$source = file_get_contents(
    __DIR__.'/../../app/core_modules/modulecatalogue/controller.php'
);

function p136Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}

$start = strpos($source, 'private function firstRegister($sysType)');
$end = strpos($source, 'private function', $start + 40);
$method = substr($source, $start, $end - $start);
$calls = array();
$offset = 0;
while (($position = strpos(
    $method,
    "->ensureInitialAdministrator('1')",
    $offset
)) !== false) {
    $calls[] = $position;
    $offset = $position + 1;
}
$firstRegistration = strpos($method, '$this->smartRegister');
$lastRegistration = strrpos($method, '$this->smartRegister');

p136Assert($start !== false && $end !== false,
    'first-time registration method is present');
p136Assert(count($calls) === 2,
    'initial administrator provisioning has exactly two phases');
p136Assert($calls[0] < $firstRegistration,
    'baseline administrator and groups exist before module registration');
p136Assert($calls[1] > $lastRegistration,
    'post-registration phase grants newly defined rights');
p136Assert(strpos($method, 'initialadminprovisioningservice') !== false,
    'canonical provisioning service owns both phases');
p136Assert(strpos($method, '->loginUser(') === false
    && strpos($method, '->authenticate(') === false
    && strpos($method, '->setSession(') === false,
    'first-time registration creates no login or fabricated session');

$service = file_get_contents(
    __DIR__.'/../../app/core_modules/security/classes/'
    .'initialadminprovisioningservice_class_inc.php'
);
p136Assert(strpos($service, 'ensureAllDefinedRightsForGroup') !== false,
    'post-registration provisioning retains complete rights grant');

echo "ALL P136 INSTALLER PROVISIONING ORDER CONTRACTS PASSED\n";
