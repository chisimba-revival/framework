<?php
require_once dirname(__FILE__) . '/canonicalauthenticationresult.php';

/**
 * Converts native repository/service output into the canonical result.
 */
class NativeAuthenticationResultAdapter
{
    public function fromUserRecord(
        array $user,
        array $groups = array(),
        array $roles = array(),
        array $permissions = array(),
        array $metadata = array()
    ) {
        $provider = 'native_database';
        $userId = isset($user['user_id'])
            ? (string) $user['user_id'] : '';
        $username = isset($user['username'])
            ? (string) $user['username'] : '';
        $isActive = isset($user['is_active'])
            ? (bool) $user['is_active'] : false;

        $identity = array(
            'title' => isset($user['title'])
                ? (string) $user['title'] : '',
            'first_name' => isset($user['first_name'])
                ? (string) $user['first_name'] : '',
            'surname' => isset($user['surname'])
                ? (string) $user['surname'] : '',
            'creation_date' => isset($user['creation_date'])
                ? $user['creation_date'] : null,
            'email_address' => isset($user['email_address'])
                ? (string) $user['email_address'] : '',
            'login_count' => isset($user['login_count'])
                ? $user['login_count'] : 0,
            'is_active' => $isActive,
            'access_level' => isset($user['access_level'])
                ? (string) $user['access_level'] : '',
        );

        if (!$isActive) {
            return CanonicalAuthenticationResult::inactive(
                $provider,
                $userId,
                $username,
                $identity,
                $metadata
            );
        }

        return CanonicalAuthenticationResult::success(
            $provider,
            $userId,
            $username,
            $identity,
            $groups,
            $roles,
            $permissions,
            $metadata
        );
    }
}
