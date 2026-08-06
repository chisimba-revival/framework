<?php
/**
 * Chisimba composition boundary for the canonical abuse-protection service.
 *
 * @category  Chisimba
 * @package   abuseprotection
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class abuseprotectionprovider extends ChisimbaObject
{
    private $service;

    public function init()
    {
        require_once dirname(__FILE__) . '/abuseprotectionservice.php';
        require_once dirname(__FILE__) . '/mdb2abuseeventrepository.php';
        require_once dirname(__FILE__) . '/installationabusekeyprovider.php';
        $this->service = new AbuseProtectionService(
            new Mdb2AbuseEventRepository($this->objEngine->getDbObj()),
            (new InstallationAbuseKeyProvider())->getKey()
        );
    }

    public function issueFormEvidence($action)
    {
        return $this->service->issueFormEvidence($action);
    }

    public function evaluate($action, array $context, array $evidence, array $policy = array())
    {
        return $this->service->evaluate($action, $context, $evidence, $policy);
    }

    public function record($action, array $context, $success, $ttl = 86400)
    {
        return $this->service->record($action, $context, $success, $ttl);
    }
}
