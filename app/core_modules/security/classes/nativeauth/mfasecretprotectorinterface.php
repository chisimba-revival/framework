<?php
/**
 * Contract for authenticated protection of MFA secrets.
 *
 * Implementations may load their encryption key eagerly or on first use.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
interface MfaSecretProtectorInterface
{
    public function protect($secret);

    public function reveal($ciphertext, $nonce);
}
