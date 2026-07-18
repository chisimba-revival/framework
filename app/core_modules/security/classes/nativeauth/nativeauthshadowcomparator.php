<?php
require_once dirname(__FILE__) . '/legacyauthenticationresultadapter.php';
require_once dirname(__FILE__) . '/liveuserbehaviourrecorder.php';
require_once dirname(__FILE__) . '/nativeauthshadowtrace.php';

/**
 * Observes the established Chisimba database-login session.
 *
 * Activation is intentionally external to the login result:
 * - CHISIMBA_NATIVE_AUTH_SHADOW === TRUE, or
 * - an ENABLED marker in <contentBasePath>/auth-shadow.
 *
 * The comparator never changes authentication, session, or database state.
 */
class NativeAuthShadowComparator
{
    public function isEnabled($userObject)
    {
        if (defined('CHISIMBA_NATIVE_AUTH_SHADOW')
            && CHISIMBA_NATIVE_AUTH_SHADOW === TRUE
        ) {
            return true;
        }

        $directory = $this->getOutputDirectory($userObject);

        return is_file($directory . DIRECTORY_SEPARATOR . 'ENABLED');
    }

    public function compare($userObject, $username)
    {
        NativeAuthShadowTrace::log(
            'shadow.compare',
            'entered',
            array('username' => $username)
        );

        if (!$this->isEnabled($userObject)) {
            NativeAuthShadowTrace::log(
                'shadow.compare',
                'disabled'
            );
            return false;
        }

        NativeAuthShadowTrace::log(
            'shadow.compare',
            'enabled'
        );

        $record = $userObject->lookupData($username);

        NativeAuthShadowTrace::log(
            'shadow.compare',
            'lookup completed',
            array('record_found' => is_array($record))
        );

        if (!is_array($record)) {
            return false;
        }

        $adapter = new LegacyAuthenticationResultAdapter();
        $canonical = $adapter->fromDatabaseRecord(
            $record,
            'liveuser_database',
            array('mode' => 'shadow')
        );

        if (!$canonical->isSuccess()) {
            return false;
        }

        $legacy = $canonical->toLegacyUserRecord();

        $expected = array(
            'username' => $legacy['username'],
            'userid' => $legacy['userid'],
            'title' => stripcslashes($legacy['title']),
            'name' => trim(
                stripcslashes($legacy['firstname'])
                . ' '
                . stripcslashes($legacy['surname'])
            ),
            'logins' => ((int) $legacy['logins']) + 1,
            'emailaddress' => stripcslashes($legacy['emailaddress']),
            'context' => 'lobby',
            'isadmin' => (
                isset($legacy['accesslevel'])
                && (string) $legacy['accesslevel'] === '1'
            ),
        );

        $actual = array(
            'username' => $userObject->getSession('username'),
            'userid' => $userObject->getSession('userid'),
            'title' => $userObject->getSession('title'),
            'name' => $userObject->getSession('name'),
            'logins' => $userObject->getSession('logins'),
            'emailaddress' => $userObject->getSession('email'),
            'context' => $userObject->getSession('context'),
            'isadmin' => $userObject->getSession('isAdmin'),
        );

        $mismatches = array();
        foreach ($expected as $key => $expectedValue) {
            $actualValue = array_key_exists($key, $actual)
                ? $actual[$key] : null;

            if ((string) $actualValue !== (string) $expectedValue) {
                $mismatches[$key] = array(
                    'expected' => $expectedValue,
                    'actual' => $actualValue,
                );
            }
        }

        $recorder = new LiveUserBehaviourRecorder();
        $snapshot = $recorder->createSnapshot(
            array(
                'authentication' => array(
                    'authenticated' => true,
                    'provider' => 'liveuser_database',
                    'shadow_match' => $mismatches === array(),
                ),
                'identity' => $canonical->toSnapshotArray(),
                'session' => array(
                    'expected' => $expected,
                    'actual' => $actual,
                    'mismatches' => $mismatches,
                ),
            ),
            array(
                'source' => 'user::authenticateUser',
                'mode' => 'shadow',
            )
        );

        $safeUsername = preg_replace(
            '/[^A-Za-z0-9_.-]+/',
            '_',
            (string) $username
        );

        if ($safeUsername === '') {
            $safeUsername = 'unknown';
        }

        $target = $this->getOutputDirectory($userObject)
            . DIRECTORY_SEPARATOR
            . 'auth-shadow-'
            . $safeUsername
            . '-'
            . gmdate('Ymd-His')
            . '.json';

        NativeAuthShadowTrace::log(
            'shadow.compare',
            'writing snapshot',
            array(
                'target' => $target,
                'mismatch_count' => (is_countable($mismatches) ? count($mismatches) : 0),
            )
        );

        $recorder->writeSnapshot($snapshot, $target);

        NativeAuthShadowTrace::log(
            'shadow.compare',
            'snapshot written',
            array('target' => $target)
        );

        return array(
            'target' => $target,
            'match' => $mismatches === array(),
            'mismatches' => $mismatches,
        );
    }

    public function getOutputDirectory($userObject)
    {
        $base = null;

        if (isset($userObject->objConfig)
            && is_object($userObject->objConfig)
            && method_exists($userObject->objConfig, 'getcontentBasePath')
        ) {
            $base = $userObject->objConfig->getcontentBasePath();
        }

        if (!is_string($base) || trim($base) === '') {
            $base = dirname(__FILE__) . '/../../../../../usrfiles';
        }

        return rtrim($base, '/\\')
            . DIRECTORY_SEPARATOR
            . 'auth-shadow';
    }
}
