<?php
/**
 * RFC 6238-compatible TOTP generation and verification.
 *
 * @author Derek Keats
 */
class TotpService
{
    private $period;
    private $digits;
    private $window;

    public function __construct($period = 30, $digits = 6, $window = 1)
    {
        $this->period = max(1, (int) $period);
        $this->digits = max(6, min(8, (int) $digits));
        $this->window = max(0, (int) $window);
    }

    /**
     * Generate a cryptographically secure Base32 secret.
     *
     * @param int $bytes Number of random bytes before Base32 encoding.
     * @return string
     */
    public function generateSecret($bytes = 20)
    {
        $bytes = (int) $bytes;
        if ($bytes < 16 || $bytes > 64) {
            throw new InvalidArgumentException(
                'TOTP secret length must be between 16 and 64 bytes.'
            );
        }

        return $this->encodeBase32(random_bytes($bytes));
    }

    public function verify($base32Secret, $code, $time, $lastAcceptedStep = null)
    {
        if (!preg_match('/^[0-9]{' . $this->digits . '}$/', (string) $code)) {
            return false;
        }
        $current = (int) floor(((int) $time) / $this->period);
        for ($offset = -$this->window; $offset <= $this->window; $offset++) {
            $step = $current + $offset;
            if ($step < 0 || ($lastAcceptedStep !== null
                && $step <= (int) $lastAcceptedStep)) {
                continue;
            }
            if (hash_equals($this->atStep($base32Secret, $step), (string) $code)) {
                return $step;
            }
        }
        return false;
    }

    public function atStep($base32Secret, $step)
    {
        $secret = $this->decodeBase32($base32Secret);
        $high = (int) floor(((int) $step) / 4294967296);
        $low = ((int) $step) % 4294967296;
        $counter = pack('N2', $high, $low);
        $hash = hash_hmac('sha1', $counter, $secret, true);
        $offset = ord($hash[19]) & 0x0f;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);
        $modulus = 10 ** $this->digits;
        return str_pad((string) ($binary % $modulus), $this->digits, '0', STR_PAD_LEFT);
    }

    private function encodeBase32($value)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        for ($i = 0; $i < strlen($value); $i++) {
            $bits .= str_pad(decbin(ord($value[$i])), 8, '0', STR_PAD_LEFT);
        }

        $result = '';
        for ($i = 0; $i < strlen($bits); $i += 5) {
            $chunk = substr($bits, $i, 5);
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $result .= $alphabet[bindec($chunk)];
        }

        return $result;
    }

    private function decodeBase32($value)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/i', '', (string) $value));
        if ($clean === '') {
            throw new InvalidArgumentException('TOTP secret is empty.');
        }
        $bits = '';
        for ($i = 0; $i < strlen($clean); $i++) {
            $position = strpos($alphabet, $clean[$i]);
            if ($position === false) {
                throw new InvalidArgumentException('TOTP secret is invalid.');
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }
        $result = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $result .= chr(bindec(substr($bits, $i, 8)));
        }
        return $result;
    }
}
