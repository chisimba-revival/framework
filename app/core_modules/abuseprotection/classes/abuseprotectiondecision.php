<?php
/**
 * Structured result returned by the canonical abuse-protection service.
 *
 * @category  Chisimba
 * @package   abuseprotection
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
final class AbuseProtectionDecision
{
    const ALLOW = 'allow';
    const DELAY = 'delay';
    const REJECT = 'reject';
    private $status;
    private $retryAfter;
    private $reason;
    public function __construct($status, $retryAfter = 0, $reason = '')
    {
        if (!in_array($status, array(self::ALLOW, self::DELAY, self::REJECT), true)) {
            throw new InvalidArgumentException('Invalid abuse-protection decision.');
        }
        $this->status = $status;
        $this->retryAfter = max(0, (int) $retryAfter);
        $this->reason = (string) $reason;
    }
    public function isAllowed() { return $this->status === self::ALLOW; }
    public function getStatus() { return $this->status; }
    public function getRetryAfter() { return $this->retryAfter; }
    public function getReason() { return $this->reason; }
}
