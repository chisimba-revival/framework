<?php
/**
 * Persistence contract for abuse-protection events.
 *
 * @category  Chisimba
 * @package   abuseprotection
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
interface AbuseEventRepositoryInterface
{
    public function countFailures($action, $subjectHash, $since);
    public function record(array $event);
    public function purgeExpired($now);
}
