<?php
require_once dirname(__FILE__)
    . '/../classes/nativeauth/persistentloginrepositoryinterface.php';
require_once dirname(__FILE__)
    . '/../classes/nativeauth/persistentloginservice.php';
require_once dirname(__FILE__)
    . '/../classes/nativeauth/persistentlogincookiepolicy.php';

class MemoryPersistentLoginRepository
    implements PersistentLoginRepositoryInterface
{
    public $records = array();
    public function store(array $record) { $this->records[$record['id']] = $record; }
    public function findActiveBySelector($selector, $now) {
        foreach ($this->records as $record) {
            if ($record['selector'] === $selector
                && empty($record['revoked_at'])
                && $record['expires_at'] > $now) {
                return $record;
            }
        }
        return false;
    }
    public function rotate($id, array $replacement, $usedAt) {
        if (!isset($this->records[$id]) || !empty($this->records[$id]['revoked_at'])) {
            return false;
        }
        $this->records[$id]['revoked_at'] = $usedAt;
        $this->records[$id]['replaced_by_id'] = $replacement['id'];
        $this->records[$replacement['id']] = $replacement;
        return true;
    }
    public function revoke($id, $revokedAt) {
        if (isset($this->records[$id])) $this->records[$id]['revoked_at'] = $revokedAt;
    }
    public function revokeAllForUser($userId, $revokedAt) {
        foreach ($this->records as &$r) if ($r['user_id'] === $userId) $r['revoked_at'] = $revokedAt;
        return true;
    }
    public function purgeExpired($now) { return true; }
}
function ensureV11($condition, $label) {
    if (!$condition) { fwrite(STDERR, 'FAIL: ' . $label . PHP_EOL); exit(1); }
}
$repo = new MemoryPersistentLoginRepository();
$ids = array('one', 'two');
$service = new PersistentLoginService(
    $repo,
    30,
    function () use (&$ids) { return array_shift($ids); }
);
$cookie = $service->issue('user-1', 1000);
ensureV11(count($repo->records) === 1, 'token stored');
ensureV11($repo->records['one']['expires_at'] === 2593000, '30-day expiry');
ensureV11(strpos($repo->records['one']['verifier_hash'], $cookie) === false, 'usable cookie not stored');
$restored = $service->restoreAndRotate($cookie, 1100);
ensureV11($restored['user_id'] === 'user-1', 'identity restored');
ensureV11($service->restoreAndRotate($cookie, 1200) === false, 'old token replay blocked');
$options = (new PersistentLoginCookiePolicy())->options(2000);
ensureV11($options['secure'] && $options['httponly'], 'secure cookie policy');
ensureV11($options['samesite'] === 'Lax', 'SameSite policy');
fwrite(STDOUT, 'PASS: persistent login foundation' . PHP_EOL);
