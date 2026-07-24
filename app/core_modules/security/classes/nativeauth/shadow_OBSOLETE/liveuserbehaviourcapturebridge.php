<?php
require_once dirname(__FILE__) . '/liveuserbehaviourrecorder.php';

/**
 * Explicit bridge between Chisimba runtime state and the recorder.
 *
 * This bridge is disabled unless CHISIMBA_AUTH_RECORDER_ENABLED is defined as
 * true. Merely loading this file performs no capture and changes no state.
 */
class LiveUserBehaviourCaptureBridge
{
    public static function isEnabled()
    {
        return defined('CHISIMBA_AUTH_RECORDER_ENABLED')
            && CHISIMBA_AUTH_RECORDER_ENABLED === true;
    }

    public static function capture(
        array $state,
        array $metadata,
        $targetDirectory
    ) {
        if (!self::isEnabled()) {
            return false;
        }

        $targetDirectory = rtrim((string) $targetDirectory, DIRECTORY_SEPARATOR);
        $username = isset($state['identity']['username'])
            ? preg_replace(
                '/[^A-Za-z0-9_.-]+/',
                '_',
                (string) $state['identity']['username']
            )
            : 'unknown';

        $filename = sprintf(
            'liveuser-%s-%s.json',
            $username !== '' ? $username : 'unknown',
            gmdate('Ymd-His')
        );

        $recorder = new LiveUserBehaviourRecorder();
        $snapshot = $recorder->createSnapshot($state, $metadata);

        return $recorder->writeSnapshot(
            $snapshot,
            $targetDirectory . DIRECTORY_SEPARATOR . $filename
        );
    }
}
