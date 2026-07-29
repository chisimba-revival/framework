<?php
/**
 * Supplies the installation MFA key from process configuration.
 *
 * The key is never read from a request, session, database table, or source
 * constant. CHISIMBA_MFA_KEY must contain one Base64-encoded 32-byte key.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class InstallationMfaKeyProvider
{
    const ENVIRONMENT_NAME = 'CHISIMBA_MFA_KEY';

    public function getKey()
    {
        $encoded = getenv(self::ENVIRONMENT_NAME);
        if (!is_string($encoded) || trim($encoded) === '') {
            throw new RuntimeException(
                'The installation MFA encryption key is not configured.'
            );
        }

        $key = base64_decode(trim($encoded), true);
        if ($key === false
            || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException(
                'The installation MFA encryption key is invalid.'
            );
        }

        return $key;
    }
}
