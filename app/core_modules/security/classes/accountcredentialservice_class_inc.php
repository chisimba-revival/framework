<?php
/** Canonical boundary for credential replacement and session revocation. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class accountcredentialservice extends dbTable
{
    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorHandler'
    ) {
        parent::init(
            $tableName !== null ? $tableName : 'tbl_users',
            $pearDb,
            $errorCallback
        );
        $this->objUsers = $this->getObject('userservice', 'security');
        $this->objAuthentication = $this->getObject(
            'authenticationservice',
            'security'
        );
        $this->objNativeAuth = $this->getObject(
            'nativeauthwebcomposition',
            'security'
        );
    }

    public function replacePasswordAndRevokeSessions($userId, $plainTextPassword)
    {
        $this->beginTransaction();
        $result = $this->replaceWithinTransaction($userId, $plainTextPassword);
        if (empty($result['ok'])) {
            $this->rollbackTransaction();
            return $result;
        }
        $this->commitTransaction();
        return $result;
    }

    /** Used by an application service that already owns the DB transaction. */
    public function replaceWithinTransaction($userId, $plainTextPassword)
    {
        $user = $this->objUsers->findByUserId($userId);
        if (!is_array($user) || empty($user['userid'])) {
            return $this->result(false, 'user_not_found');
        }
        try {
            $hash = $this->objAuthentication->createPasswordHash(
                $plainTextPassword
            );
        } catch (InvalidArgumentException $exception) {
            return $this->result(false, 'invalid_password');
        }
        $updated = $this->objUsers->updatePasswordHash($user['userid'], $hash);
        if (empty($updated['ok'])) {
            return $this->result(false, 'password_update_failed');
        }
        $stack = $this->objNativeAuth->build();
        if (!isset($stack['persistent'])
            || !$stack['persistent']->revokeAllForUser($user['userid'], time())) {
            return $this->result(false, 'session_revocation_failed');
        }
        return $this->result(true, 'password_replaced', $user['userid']);
    }

    private function result($ok, $code, $userId = null)
    {
        return array('ok' => (bool) $ok, 'code' => $code, 'userId' => $userId);
    }
}
?>
