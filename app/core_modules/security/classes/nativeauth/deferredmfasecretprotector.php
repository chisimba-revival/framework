<?php
require_once __DIR__ . '/mfasecretprotectorinterface.php';

/**
 * Loads the installation MFA key only when an MFA secret is used.
 *
 * Ordinary password authentication can therefore run while MFA is disabled
 * without requiring installation MFA key configuration.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class DeferredMfaSecretProtector implements MfaSecretProtectorInterface
{
    private $keyProvider;
    private $protector;

    public function __construct($keyProvider)
    {
        if (!is_object($keyProvider)
            || !method_exists($keyProvider, 'getKey')) {
            throw new InvalidArgumentException(
                'The MFA key provider is invalid.'
            );
        }
        $this->keyProvider = $keyProvider;
    }

    public function protect($secret)
    {
        return $this->protector()->protect($secret);
    }

    public function reveal($ciphertext, $nonce)
    {
        return $this->protector()->reveal($ciphertext, $nonce);
    }

    private function protector()
    {
        if ($this->protector === null) {
            $this->protector = new MfaSecretProtector(
                $this->keyProvider->getKey()
            );
        }
        return $this->protector;
    }
}
