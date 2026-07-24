<?php
/**
 * Guarded breadcrumb logger for native-auth shadow diagnostics.
 *
 * The trace is enabled only when the same auth-shadow marker used by the
 * comparator exists. Failures are swallowed so tracing cannot affect login.
 */
class NativeAuthShadowTrace
{
    public static function log($source, $message, array $context = array())
    {
        try {
            $directory = self::getDirectory();
            if (!is_file($directory . DIRECTORY_SEPARATOR . 'ENABLED')) {
                return;
            }

            if (!is_dir($directory)
                && !mkdir($directory, 0700, true)
                && !is_dir($directory)
            ) {
                return;
            }

            $line = array(
                'time' => gmdate('c'),
                'source' => (string) $source,
                'message' => (string) $message,
                'context' => self::redact($context),
            );

            file_put_contents(
                $directory . DIRECTORY_SEPARATOR . 'trace.log',
                json_encode($line, JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );

            @chmod(
                $directory . DIRECTORY_SEPARATOR . 'trace.log',
                0600
            );
        } catch (Exception $exception) {
            // Diagnostic tracing must never change runtime behaviour.
        }
    }

    private static function getDirectory()
    {
        return dirname(__FILE__)
            . '/../../../../../usrfiles/auth-shadow';
    }

    private static function redact($value, $key = '')
    {
        $sensitive = array(
            'password',
            'passwd',
            'pass',
            'token',
            'secret',
            'nonce',
            'cookie',
            'session',
        );

        $normalised = strtolower((string) $key);
        foreach ($sensitive as $candidate) {
            if ($normalised !== ''
                && strpos($normalised, $candidate) !== false
            ) {
                return '[REDACTED]';
            }
        }

        if (is_array($value)) {
            $result = array();
            foreach ($value as $childKey => $childValue) {
                $result[$childKey] = self::redact(
                    $childValue,
                    (string) $childKey
                );
            }
            return $result;
        }

        if (is_object($value)) {
            return array('__class' => get_class($value));
        }

        return $value;
    }
}
