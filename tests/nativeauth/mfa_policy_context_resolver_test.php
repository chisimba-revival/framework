<?php
require_once dirname(__DIR__, 2)
    . '/app/core_modules/security/classes/nativeauth/'
    . 'mfapolicycontextresolver.php';

function v75check($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}
class V75AuthenticationResult
{
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function getUserId() { return $this->id; }
}

$calls = array();
$resolver = new MfaPolicyContextResolver(
    function ($id) use (&$calls) {
        $calls[] = 'user:' . $id;
        return array('creationdate' => '2026-07-20 06:00:00');
    },
    function ($id) use (&$calls) {
        $calls[] = 'admin:' . $id;
        return true;
    },
    function ($id) use (&$calls) {
        $calls[] = 'factor:' . $id;
        return false;
    },
    function ($id) use (&$calls) {
        $calls[] = 'policy:' . $id;
        return 1784527200;
    },
    function () { return 1785132000; }
);
$context = $resolver->resolve(new V75AuthenticationResult('user-7'));
v75check($context['is_site_administrator'] === true,
    'canonical administrator fact is preserved');
v75check($context['mfa_enrolled'] === false,
    'canonical factor fact is preserved');
v75check($context['account_created_at'] > 0,
    'account creation date is normalised');
v75check($context['policy_enabled_at'] === 1784527200,
    'policy start is normalised');
v75check($context['now'] === 1785132000,
    'clock is explicit and testable');
v75check($calls === array(
    'user:user-7', 'policy:user-7', 'admin:user-7', 'factor:user-7',
), 'each canonical reader is called exactly once');

$failed = false;
try {
    $resolver->resolve(new V75AuthenticationResult(''));
} catch (InvalidArgumentException $exception) {
    $failed = true;
}
v75check($failed, 'empty authenticated identity is rejected');

echo "PASS: MFA policy context resolver.\n";
