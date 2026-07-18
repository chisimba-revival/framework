<?php
/**
 * Redacts secrets and canonicalises authentication snapshots.
 */
class AuthSnapshotRedactor
{
    private $sensitiveKeys;

    public function __construct(array $additionalSensitiveKeys = array())
    {
        $this->sensitiveKeys = array_merge(
            array(
                'password',
                'passwd',
                'pass',
                'password_hash',
                'credential',
                'credentials',
                'secret',
                'token',
                'csrf',
                'nonce',
                'session_id',
                'sessionid',
                'cookie',
                'authorization',
                'auth_header',
                'private_key',
                'api_key',
            ),
            $additionalSensitiveKeys
        );
    }

    public function redact($value, $key = '')
    {
        if ($this->isSensitiveKey($key)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $result = array();
            foreach ($value as $childKey => $childValue) {
                $result[$childKey] = $this->redact(
                    $childValue,
                    (string) $childKey
                );
            }
            return $this->canonicaliseArray($result);
        }

        if (is_object($value)) {
            return array(
                '__class' => get_class($value),
                '__properties' => $this->redact(
                    get_object_vars($value),
                    ''
                ),
            );
        }

        if (is_resource($value)) {
            return '[RESOURCE:' . get_resource_type($value) . ']';
        }

        if (is_string($value) && strlen($value) > 4096) {
            return substr($value, 0, 4096) . '[TRUNCATED]';
        }

        return $value;
    }

    private function isSensitiveKey($key)
    {
        $normalised = strtolower(trim((string) $key));
        if ($normalised === '') {
            return false;
        }

        foreach ($this->sensitiveKeys as $candidate) {
            $candidate = strtolower((string) $candidate);
            if ($normalised === $candidate
                || strpos($normalised, $candidate) !== false
            ) {
                return true;
            }
        }

        return false;
    }

    private function canonicaliseArray(array $value)
    {
        $isList = array_keys($value) === range(0, (is_countable($value) ? count($value) : 0) - 1);

        if ($isList) {
            usort($value, function ($left, $right) {
                return strcmp(
                    json_encode($left),
                    json_encode($right)
                );
            });
            return $value;
        }

        ksort($value);
        return $value;
    }
}
