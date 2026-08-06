<?php
/**
 * Sole production composition root for guarded native web authentication.
 *
 * Controllers receive the finished adapter and application services from this
 * factory. They do not construct repositories, MFA services, session writers,
 * persistent-login services, secret protection, or CSRF storage themselves.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
foreach (array(
    'nativesessionserviceinterface.php',
    'authenticationapplicationservice.php',
    'authenticationtransactioncoordinator.php',
    'csrftokenservice.php',
    'guardedloginapplicationservice.php',
    'installationmfakeyprovider.php',
    'deferredmfasecretprotector.php',
    'mdb2mfarepository.php',
    'mdb2persistentloginrepository.php',
    'mfapolicycontextresolver.php',
    'nativeauthpolicycontextreaders.php',
    'mfachallengeservice.php',
    'mfaenrolmentapplicationservice.php',
    'mfasecretprotector.php',
    'mfawebcontrolleradapter.php',
    'mfawebflowservice.php',
    'pendingauthenticationservice.php',
    'persistentlogincoordinator.php',
    'persistentlogincookiepolicy.php',
    'persistentloginrepositoryinterface.php',
    'persistentloginservice.php',
    'recoverycodeservice.php',
    'totpprovisioningservice.php',
    'totpservice.php',
) as $dependency) {
    require_once dirname(__FILE__) . '/' . $dependency;
}
require_once dirname(__FILE__)
    . '/../../../abuseprotection/classes/abuseprotectionservice.php';
require_once dirname(__FILE__)
    . '/../../../abuseprotection/classes/mdb2abuseeventrepository.php';
require_once dirname(__FILE__)
    . '/../../../abuseprotection/classes/installationabusekeyprovider.php';

final class NativeAuthWebCompositionFactory
{
    public static function build(
        $connection,
        $sessionBackend,
        NativeSessionServiceInterface $sessions,
        $credentialVerifier,
        $mfaPolicy,
        $userService,
        $groupService,
        $sysconfig,
        $rememberDays = 30,
        array $abusePolicy = array()
    ) {
        self::requireSessionBackend($sessionBackend);

        $csrf = new CsrfTokenService($sessionBackend);
        $pending = new PendingAuthenticationService($sessionBackend);
        $factors = new Mdb2MfaRepository($connection);
        $totp = new TotpService();
        $protector = new DeferredMfaSecretProtector(
            new InstallationMfaKeyProvider()
        );
        $persistent = new PersistentLoginCoordinator(
            new PersistentLoginService(
                new Mdb2PersistentLoginRepository($connection),
                $rememberDays
            ),
            new PersistentLoginCookiePolicy()
        );
        $transactions = new AuthenticationTransactionCoordinator(
            $pending,
            new MfaChallengeService($factors, $totp, $protector),
            $sessions,
            $persistent,
            $rememberDays
        );
        $authentication = new AuthenticationApplicationService(
            $transactions,
            $csrf
        );
        $enrolment = new MfaEnrolmentApplicationService(
            $factors,
            $totp,
            $protector,
            new RecoveryCodeService()
        );
        $flow = new MfaWebFlowService(
            $authentication,
            $enrolment,
            new TotpProvisioningService(),
            $pending,
            $factors
        );

        $policyReaders = new NativeAuthPolicyContextReaders(
            $userService,
            $groupService,
            $sysconfig,
            $factors
        );
        $policyContext = new MfaPolicyContextResolver(
            array($policyReaders, 'userRecord'),
            array($policyReaders, 'isSiteAdministrator'),
            array($policyReaders, 'hasActiveFactor'),
            array($policyReaders, 'policyEnabledAt')
        );
        $abuse = new AbuseProtectionService(
            new Mdb2AbuseEventRepository($connection),
            (new InstallationAbuseKeyProvider())->getKey()
        );
        $guardedLogin = new GuardedLoginApplicationService(
            $credentialVerifier,
            $mfaPolicy,
            $policyContext,
            $flow,
            $abuse,
            $abusePolicy
        );

        return array(
            'adapter' => new MfaWebControllerAdapter($flow, $csrf),
            'guarded_login' => $guardedLogin,
            'abuse' => $abuse,
            'csrf' => $csrf,
            'flow' => $flow,
            'factors' => $factors,
            'pending' => $pending,
            'persistent' => $persistent,
            'sessions' => $sessions,
        );
    }

    private static function requireSessionBackend($backend)
    {
        foreach (array('getSession', 'setSession', 'unsetSession') as $method) {
            if (!is_object($backend) || !method_exists($backend, $method)) {
                throw new InvalidArgumentException(
                    'Native authentication session backend is invalid.'
                );
            }
        }
    }
}
