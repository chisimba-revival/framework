<?php
require_once dirname(__FILE__)
    . '/liveuserbehaviourrecorderinterface.php';
require_once dirname(__FILE__) . '/authsnapshotredactor.php';

/**
 * Non-invasive authentication-state recorder.
 *
 * This class has no dependency on LiveUser internals. A caller supplies the
 * observable state explicitly, making the recorder testable and preventing it
 * from reaching into globals unless an activation hook chooses to do so.
 */
class LiveUserBehaviourRecorder
    implements LiveUserBehaviourRecorderInterface
{
    private $redactor;

    public function __construct(AuthSnapshotRedactor $redactor = null)
    {
        $this->redactor = $redactor ?: new AuthSnapshotRedactor();
    }

    public function createSnapshot(
        array $state,
        array $metadata = array()
    ) {
        $snapshot = array(
            'schema_version' => 1,
            'captured_at' => gmdate('c'),
            'metadata' => $metadata,
            'authentication' => isset($state['authentication'])
                ? $state['authentication'] : array(),
            'identity' => isset($state['identity'])
                ? $state['identity'] : array(),
            'groups' => isset($state['groups'])
                ? $state['groups'] : array(),
            'roles' => isset($state['roles'])
                ? $state['roles'] : array(),
            'permissions' => isset($state['permissions'])
                ? $state['permissions'] : array(),
            'session' => isset($state['session'])
                ? $state['session'] : array(),
            'context' => isset($state['context'])
                ? $state['context'] : array(),
            'language' => isset($state['language'])
                ? $state['language'] : array(),
            'liveuser' => isset($state['liveuser'])
                ? $state['liveuser'] : array(),
            'database_queries' => isset($state['database_queries'])
                ? $state['database_queries'] : array(),
        );

        return $this->redactor->redact($snapshot);
    }

    public function writeSnapshot(array $snapshot, $target)
    {
        $target = (string) $target;
        $directory = dirname($target);

        if (!is_dir($directory)
            && !mkdir($directory, 0700, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create snapshot directory: ' . $directory
            );
        }

        $json = json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        if (!is_string($json)) {
            throw new RuntimeException('Unable to encode auth snapshot.');
        }

        $temporary = tempnam($directory, '.auth-snapshot-');
        if ($temporary === false) {
            throw new RuntimeException(
                'Unable to create temporary snapshot file.'
            );
        }

        try {
            if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX)
                === false
            ) {
                throw new RuntimeException(
                    'Unable to write temporary snapshot file.'
                );
            }

            chmod($temporary, 0600);

            if (!rename($temporary, $target)) {
                throw new RuntimeException(
                    'Unable to move snapshot into place.'
                );
            }
        } catch (Exception $exception) {
            if (is_file($temporary)) {
                unlink($temporary);
            }
            throw $exception;
        }

        return true;
    }
}
