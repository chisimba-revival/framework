<?php
/**
 * Chisimba-facing composition boundary for native web authentication.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
require_once dirname(__FILE__) . '/nativeauth/nativeauthwebcompositionfactory.php';
require_once dirname(__FILE__) . '/nativeauth/nativesessionservice.php';
require_once dirname(__FILE__) . '/nativeauth/configurablemfaenforcementpolicy.php';

class nativeauthwebcomposition extends ChisimbaObject
{
    private $stack;

    public function build()
    {
        if ($this->stack !== null) {
            return $this->stack;
        }
        $settings = $this->getObject('dbsysconfig', 'sysconfig');
        $policy = new ConfigurableMfaEnforcementPolicy(
            $this->enabled($settings->getValue(
                'require_mfa_site_admins', 'security', 'false'
            )),
            $this->enabled($settings->getValue(
                'require_mfa_other_users', 'security', 'false'
            )),
            (int) $settings->getValue(
                'mfa_enrolment_grace_days', 'security', 7
            )
        );
        $this->stack = NativeAuthWebCompositionFactory::build(
            $this->objEngine->getDbObj(),
            $this,
            new NativeSessionService($this),
            $this->getObject('auth_database', 'security'),
            $policy,
            $this->getObject('userservice', 'security'),
            $this->getObject('groupservice', 'groupadmin'),
            $settings
        );
        return $this->stack;
    }

    private function enabled($value)
    {
        return in_array(
            strtolower(trim((string) $value)),
            array('1', 'true', 'yes', 'on'),
            true
        );
    }
}
