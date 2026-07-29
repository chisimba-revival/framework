<?php
/**
 * Builds standards-compatible TOTP authenticator provisioning data.
 *
 * The URI contains the one-time enrollment secret and must only be rendered
 * on the authenticated enrollment page. It must never be logged, persisted,
 * placed in a URL parameter, or sent to an external QR service.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class TotpProvisioningService
{
    public function build($issuer, $accountName, $secret)
    {
        $issuer = $this->cleanLabel($issuer, 'issuer');
        $accountName = $this->cleanLabel($accountName, 'account');
        $secret = strtoupper(preg_replace('/\s+/', '', (string) $secret));

        if (!preg_match('/^[A-Z2-7]{16,128}$/', $secret)) {
            throw new InvalidArgumentException(
                'The TOTP provisioning secret is invalid.'
            );
        }

        $label = rawurlencode($issuer . ':' . $accountName);
        $query = http_build_query(
            array(
                'secret' => $secret,
                'issuer' => $issuer,
                'algorithm' => 'SHA1',
                'digits' => 6,
                'period' => 30,
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        return array(
            'uri' => 'otpauth://totp/' . $label . '?' . $query,
            'manual_key' => $secret,
            'issuer' => $issuer,
            'account_name' => $accountName,
        );
    }

    private function cleanLabel($value, $name)
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', (string) $value));
        if ($value === '' || strlen($value) > 200) {
            throw new InvalidArgumentException(
                'The TOTP provisioning ' . $name . ' is invalid.'
            );
        }
        return $value;
    }
}
