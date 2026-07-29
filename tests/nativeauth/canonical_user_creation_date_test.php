<?php
/**
 * Permanent contract for canonical account creation timestamps.
 *
 * @author Derek Keats
 */
$root = dirname(__DIR__, 2);
$servicePath = $root
    . '/app/core_modules/security/classes/userservice_class_inc.php';
$resolverPath = $root
    . '/app/core_modules/security/classes/nativeauth/mfapolicycontextresolver.php';
$service = file_get_contents($servicePath);
$resolver = file_get_contents($resolverPath);

function contract($condition, $label)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
    echo "PASS: {$label}\n";
}

$creation = '$values[\'creationdate\'] = date(\'Y-m-d\');';
$insert = '$inserted = $this->insert($values);';
$creationPosition = strpos($service, $creation);
$insertPosition = strpos($service, $insert);

contract(
    $creationPosition !== false,
    'UserService assigns a canonical creation date'
);
contract(
    substr_count($service, $creation) === 1,
    'creation date has exactly one account-creation owner'
);
contract(
    $insertPosition !== false && $creationPosition < $insertPosition,
    'creation date is assigned before tbl_users insertion'
);
contract(
    strpos(
        $resolver,
        'isset($user[\'creationdate\']) ? $user[\'creationdate\'] : null'
    ) !== false,
    'MFA policy reads the canonical account creation date'
);
contract(
    strpos(
        $resolver,
        'MFA policy has no valid enforcement start time.'
    ) !== false,
    'MFA resolver still rejects genuinely invalid policy context'
);
