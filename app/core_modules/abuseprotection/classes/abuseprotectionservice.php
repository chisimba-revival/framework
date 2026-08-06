<?php
/**
 * Canonical first-party abuse-decision, form-evidence and rate-limiting service.
 *
 * @category  Chisimba
 * @package   abuseprotection
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
require_once dirname(__FILE__) . '/abuseprotectiondecision.php';
require_once dirname(__FILE__) . '/abuseeventrepositoryinterface.php';
final class AbuseProtectionService
{
    private $events;
    private $key;
    private $clock;
    private $idFactory;
    public function __construct(AbuseEventRepositoryInterface $events, $key,
        $clock = null, $idFactory = null)
    {
        if (strlen((string) $key) < 32) {
            throw new InvalidArgumentException('Abuse-protection key is too short.');
        }
        $this->events = $events;
        $this->key = (string) $key;
        $this->clock = $clock ?: 'time';
        $this->idFactory = $idFactory ?: function () { return bin2hex(random_bytes(16)); };
    }
    public function issueFormEvidence($action)
    {
        $action = $this->action($action);
        $issued = $this->now();
        $nonce = bin2hex(random_bytes(16));
        $payload = $action . '|' . $issued . '|' . $nonce;
        return array('issued_at' => $issued, 'nonce' => $nonce,
            'signature' => hash_hmac('sha256', $payload, $this->key));
    }
    public function evaluate($action, array $context, array $evidence,
        array $policy = array())
    {
        $action = $this->action($action);
        $now = $this->now();
        if (!empty($evidence['website'])) {
            return new AbuseProtectionDecision(AbuseProtectionDecision::REJECT, 0, 'honeypot');
        }
        if (!$this->validEvidence($action, $evidence, $now,
            isset($policy['minimum_seconds']) ? $policy['minimum_seconds'] : 1,
            isset($policy['maximum_seconds']) ? $policy['maximum_seconds'] : 3600)) {
            return new AbuseProtectionDecision(AbuseProtectionDecision::REJECT, 0, 'form_evidence');
        }
        $subject = $this->subjectHash($action, $context);
        $window = max(1, (int) ($policy['window_seconds'] ?? 900));
        $limit = max(1, (int) ($policy['failure_limit'] ?? 5));
        $failures = $this->events->countFailures($action, $subject, $now - $window);
        if ($failures >= $limit) {
            return new AbuseProtectionDecision(AbuseProtectionDecision::DELAY,
                min(900, 30 * ($failures - $limit + 1)), 'rate_limit');
        }
        return new AbuseProtectionDecision(AbuseProtectionDecision::ALLOW);
    }
    public function record($action, array $context, $success, $ttl = 86400)
    {
        $now = $this->now();
        return $this->events->record(array(
            'id' => call_user_func($this->idFactory),
            'action_key' => $this->action($action),
            'subject_hash' => $this->subjectHash($action, $context),
            'outcome' => $success ? 'success' : 'failure',
            'occurred_at' => $now,
            'expires_at' => $now + max(60, (int) $ttl),
        ));
    }
    public function purgeExpired() { return $this->events->purgeExpired($this->now()); }
    private function validEvidence($action, array $e, $now, $min, $max)
    {
        $issued=(int)($e['issued_at']??0); $nonce=(string)($e['nonce']??'');
        $signature=(string)($e['signature']??'');
        if ($issued < 1 || strlen($nonce) !== 32 || strlen($signature) !== 64) return false;
        $age=$now-$issued;
        if ($age < max(0,(int)$min) || $age > max(1,(int)$max)) return false;
        $expected=hash_hmac('sha256',$action.'|'.$issued.'|'.$nonce,$this->key);
        return hash_equals($expected,$signature);
    }
    private function subjectHash($action, array $context)
    {
        $parts=array($action, (string)($context['ip']??''),
            strtolower(trim((string)($context['account']??''))),
            (string)($context['session']??''));
        return hash_hmac('sha256', implode('|',$parts), $this->key);
    }
    private function action($action)
    {
        $action=trim((string)$action);
        if (!preg_match('/^[a-z0-9][a-z0-9._-]{1,99}$/',$action)) {
            throw new InvalidArgumentException('Invalid abuse-protection action.');
        }
        return $action;
    }
    private function now() { return (int) call_user_func($this->clock); }
}
