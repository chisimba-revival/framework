<?php
$coordinatorPath = dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/'
    . 'authenticationtransactioncoordinator.php';
if (!is_file($coordinatorPath)) {
    $coordinatorPath = dirname(__FILE__)
        . '/../nativeauth/authenticationtransactioncoordinator.php';
}
require_once $coordinatorPath;

function v40assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

class V40Pending
{
    public $record;
    public $consumes = 0;

    public function begin($userId, $username, $remember, array $metadata)
    {
        $this->record = (object) array(
            'id' => 'tx-1',
            'userId' => $userId,
            'username' => $username,
            'remember' => $remember,
            'metadata' => $metadata,
        );
        return $this->record;
    }

    public function peek()
    {
        return $this->record;
    }

    public function consume($id)
    {
        if ($id !== 'tx-1' || $this->record === null) {
            return null;
        }
        $record = $this->record;
        $this->record = null;
        $this->consumes++;
        return $record;
    }
}

class V40Challenges
{
    public $accept = false;
    public function verifyTotp($userId, $code, $now)
    {
        return $this->accept && $code === '123456';
    }
    public function verifyRecoveryCode($userId, $code, $now)
    {
        return $this->accept && $code === 'recover-once';
    }
}

class V40Sessions
{
    public $established = 0;
    public $destroyed = 0;
    public function establish($userId, array $attributes)
    {
        $this->established++;
        return true;
    }
    public function destroy()
    {
        $this->destroyed++;
        return true;
    }
}

class V40Persistent
{
    public $issued = 0;
    public $accept = true;
    public function issue($userId, $now, $days)
    {
        $this->issued++;
        return $this->accept;
    }
}

$pending = new V40Pending();
$challenges = new V40Challenges();
$sessions = new V40Sessions();
$persistent = new V40Persistent();
$coordinator = new AuthenticationTransactionCoordinator(
    $pending,
    $challenges,
    $sessions,
    $persistent,
    30,
    function () {
        return 1700000000;
    }
);

$result = $coordinator->begin(
    'user-1',
    'derek',
    true,
    array('ip' => '127.0.0.1')
);
v40assert(
    $result['status'] === AuthenticationTransactionCoordinator::STATUS_MFA_REQUIRED,
    'MFA policy must create a pending transaction.'
);
v40assert($sessions->established === 0, 'Pending MFA must not establish identity.');
v40assert($persistent->issued === 0, 'Pending MFA must not issue remembered login.');
v40assert(
    !$coordinator->completeTotp('tx-1', '000000'),
    'Invalid TOTP must fail.'
);
v40assert($pending->consumes === 0, 'Invalid TOTP must not consume transaction.');
v40assert($sessions->established === 0, 'Invalid TOTP must not establish identity.');

$challenges->accept = true;
v40assert(
    $coordinator->completeTotp('tx-1', '123456'),
    'Valid TOTP must complete authentication.'
);
v40assert($pending->consumes === 1, 'Successful MFA must consume transaction once.');
v40assert($sessions->established === 1, 'Successful MFA must establish identity once.');
v40assert($persistent->issued === 1, 'Remembered login must follow final identity.');
v40assert(
    !$coordinator->completeTotp('tx-1', '123456'),
    'Consumed transaction must reject replay.'
);
v40assert($sessions->established === 1, 'Replay must not establish another session.');

$result = $coordinator->begin('user-2', 'user2', false, false);
v40assert(
    $result['status'] === AuthenticationTransactionCoordinator::STATUS_COMPLETE,
    'Policy-permitted login must complete directly.'
);
v40assert($sessions->established === 2, 'Direct completion must establish once.');
v40assert($persistent->issued === 1, 'Remember-off must not issue a credential.');

$threw = false;
try {
    $coordinator->begin(
        'user-3',
        'user3',
        false,
        true,
        array('permissions' => array('admin'))
    );
} catch (InvalidArgumentException $exception) {
    $threw = true;
}
v40assert($threw, 'Authorization data must be rejected from pending state.');

echo "PASS: V40 authentication transaction coordinator tests." . PHP_EOL;
