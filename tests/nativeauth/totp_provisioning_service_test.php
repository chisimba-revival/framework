<?php
require_once dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/'
    . 'totpprovisioningservice.php';

function v63check($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
    echo 'PASS: ' . $message . PHP_EOL;
}

$service = new TotpProvisioningService();
$secret = 'JBSWY3DPEHPK3PXP';
$data = $service->build('Chisimba Learn', 'derek@example.test', $secret);

v63check(
    strpos($data['uri'], 'otpauth://totp/Chisimba%20Learn%3A') === 0,
    'standard TOTP URI and encoded label are produced'
);
v63check(
    strpos($data['uri'], 'secret=JBSWY3DPEHPK3PXP') !== false,
    'Base32 setup secret is represented exactly'
);
v63check(
    strpos($data['uri'], 'issuer=Chisimba%20Learn') !== false,
    'issuer query parameter matches the label issuer'
);
v63check(
    strpos($data['uri'], 'algorithm=SHA1') !== false
        && strpos($data['uri'], 'digits=6') !== false
        && strpos($data['uri'], 'period=30') !== false,
    'TOTP algorithm parameters match the server verifier'
);
v63check(
    $data['manual_key'] === $secret,
    'manual setup key fallback is returned'
);

foreach (array('', 'not-base32!', 'ABC') as $invalid) {
    try {
        $service->build('Chisimba', 'derek', $invalid);
        v63check(false, 'invalid secret must fail closed');
    } catch (InvalidArgumentException $expected) {
        v63check(true, 'invalid secret fails closed');
    }
}

echo "PASS: V63 local TOTP provisioning tests." . PHP_EOL;
