<?php
/**
 * Owns the persistent installation master key.
 *
 * The installer calls ensureExists(). Runtime consumers call deriveKey().
 * Existing malformed key material is never silently replaced.
 *
 * @category  Chisimba
 * @package   core
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL v2
 */
final class InstallationMasterKeyProvider
{
    const KEY_BYTES = 32;
    private $path;

    public function __construct($path = null)
    {
        $this->path = $path ?: dirname(__DIR__, 2)
            . '/config/installation.key';
    }

    public function ensureExists()
    {
        if (is_file($this->path)) {
            $this->readKey();
            return false;
        }
        if (file_exists($this->path)) {
            throw new RuntimeException(
                'The installation master-key path is not a regular file.'
            );
        }
        $directory = dirname($this->path);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException(
                'The installation configuration directory is not writable.'
            );
        }
        $encoded = base64_encode(random_bytes(self::KEY_BYTES)) . PHP_EOL;
        $temporary = $this->path . '.new.' . bin2hex(random_bytes(8));
        $previous = umask(0077);
        try {
            $written = file_put_contents($temporary, $encoded, LOCK_EX);
            if ($written !== strlen($encoded) || !@chmod($temporary, 0600)) {
                throw new RuntimeException(
                    'Could not securely write the installation master key.'
                );
            }
            if (is_file($this->path)) {
                @unlink($temporary);
                $this->readKey();
                return false;
            }
            if (!@rename($temporary, $this->path)) {
                throw new RuntimeException(
                    'Could not publish the installation master key.'
                );
            }
        } finally {
            umask($previous);
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
        $this->readKey();
        return true;
    }

    public function deriveKey($purpose)
    {
        $purpose = trim((string) $purpose);
        if ($purpose === '') {
            throw new InvalidArgumentException('A key purpose is required.');
        }
        return hash_hmac('sha256', 'chisimba:' . $purpose, $this->readKey(), true);
    }

    private function readKey()
    {
        if (!is_file($this->path)) {
            throw new RuntimeException(
                'The installation master key has not been created.'
            );
        }
        $encoded = file_get_contents($this->path);
        $key = is_string($encoded) ? base64_decode(trim($encoded), true) : false;
        if ($key === false || strlen($key) !== self::KEY_BYTES) {
            throw new RuntimeException(
                'The stored installation master key is invalid.'
            );
        }
        return $key;
    }
}
