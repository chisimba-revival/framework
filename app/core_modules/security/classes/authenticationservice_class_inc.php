<?php
/**
 * Canonical authentication service boundary.
 *
 * User-management callers use this service for credential operations instead
 * of selecting a password implementation directly. User records remain owned
 * by UserService, while login/session orchestration will converge on this
 * boundary in bounded migrations.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

require_once dirname(__FILE__)
    . '/nativeauth/nativepasswordverifierinterface.php';
require_once dirname(__FILE__)
    . '/nativeauth/nativepasswordverifier.php';

class authenticationservice extends ChisimbaObject
{
    private $passwordVerifier;

    public function init()
    {
        $this->passwordVerifier = new NativePasswordVerifier();
    }

    /**
     * Create a password_hash()-compatible credential for storage.
     */
    public function createPasswordHash($plainTextPassword)
    {
        if (!is_scalar($plainTextPassword)
            || trim((string) $plainTextPassword) === '') {
            throw new InvalidArgumentException('Password must not be empty.');
        }

        return $this->passwordVerifier->createHash((string) $plainTextPassword);
    }

    /**
     * Verify a supplied password against a stored supported credential.
     */
    public function verifyPassword(
        $plainTextPassword,
        $storedHash,
        array $userRecord = array()
    ) {
        return $this->passwordVerifier->verify(
            (string) $plainTextPassword,
            (string) $storedHash,
            $userRecord
        );
    }

    public function passwordHashNeedsUpgrade($storedHash)
    {
        return $this->passwordVerifier->needsRehash((string) $storedHash);
    }

    public function passwordHashScheme($storedHash)
    {
        return $this->passwordVerifier->identifyHashScheme((string) $storedHash);
    }
}
?>
