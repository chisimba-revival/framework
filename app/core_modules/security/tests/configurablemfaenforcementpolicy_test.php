<?php
require_once dirname(__FILE__)
    . '/../classes/nativeauth/configurablemfaenforcementpolicy.php';

function assertSameValue($expected, $actual, $label)
{
    if ($expected !== $actual) {
        fwrite(STDERR, 'FAIL: ' . $label . PHP_EOL);
        exit(1);
    }
}

$created = 1000000;
$day = 86400;

$disabled = new ConfigurableMfaEnforcementPolicy(false, false, 7);
assertSameValue(
    ConfigurableMfaEnforcementPolicy::STATUS_NOT_REQUIRED,
    $disabled->evaluate(null, array(
        'is_site_administrator' => true,
    ))['status'],
    'administrator switch can be disabled'
);

$adminOnly = new ConfigurableMfaEnforcementPolicy(true, false, 7);
assertSameValue(
    ConfigurableMfaEnforcementPolicy::STATUS_GRACE,
    $adminOnly->evaluate(null, array(
        'is_site_administrator' => true,
        'account_created_at' => $created,
        'now' => $created + (6 * $day),
    ))['status'],
    'administrator has seven-day grace'
);
assertSameValue(
    ConfigurableMfaEnforcementPolicy::STATUS_CHALLENGE_REQUIRED,
    $adminOnly->evaluate(null, array(
        'is_site_administrator' => true,
        'account_created_at' => $created,
        'now' => $created + (7 * $day),
    ))['status'],
    'administrator grace expires at seven days'
);
assertSameValue(
    ConfigurableMfaEnforcementPolicy::STATUS_NOT_REQUIRED,
    $adminOnly->evaluate(null, array(
        'is_site_administrator' => false,
    ))['status'],
    'ordinary-user switch is independent'
);

$usersOnly = new ConfigurableMfaEnforcementPolicy(false, true, 7);
assertSameValue(
    ConfigurableMfaEnforcementPolicy::STATUS_CHALLENGE_REQUIRED,
    $usersOnly->evaluate(null, array(
        'is_site_administrator' => false,
        'mfa_enrolled' => true,
    ))['status'],
    'enrolled user requires a challenge'
);
assertSameValue(
    $created + (17 * $day),
    $usersOnly->evaluate(null, array(
        'is_site_administrator' => false,
        'account_created_at' => $created,
        'policy_enabled_at' => $created + (10 * $day),
        'now' => $created + (11 * $day),
    ))['deadline'],
    'existing account rollout begins when policy is enabled'
);

printf("PASS: MFA policy settings and grace calculations\n");
