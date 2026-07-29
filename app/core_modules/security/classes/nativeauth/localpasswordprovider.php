<?php
require_once dirname(__FILE__) . '/authenticationproviderinterface.php';
require_once dirname(__FILE__) . '/canonicalauthenticationresult.php';
require_once dirname(__FILE__) . '/nativeuserrepositoryinterface.php';
require_once dirname(__FILE__) . '/nativepasswordverifierinterface.php';

/**
 * Verifies local Chisimba username/password credentials.
 *
 * This provider performs credential verification only. It does not create a
 * session, calculate authorisation, provision users, redirect requests, or
 * render user interfaces.
 */
class LocalPasswordProvider implements AuthenticationProviderInterface
{
    const PROVIDER_ID = 'local';

    private $users;
    private $passwords;

    public function __construct(
        NativeUserRepositoryInterface $users,
        NativePasswordVerifierInterface $passwords
    ) {
        $this->users = $users;
        $this->passwords = $passwords;
    }

    public function getProviderId()
    {
        return self::PROVIDER_ID;
    }

    public function authenticate(
        $identifier,
        $secret,
        array $context = array()
    ) {
        $normalisedIdentifier = trim((string) $identifier);
        $plainTextPassword = (string) $secret;

        if ($normalisedIdentifier === '' || $plainTextPassword === '') {
            $this->recordFailure($normalisedIdentifier, $context);

            return CanonicalAuthenticationResult::failure(
                self::PROVIDER_ID,
                CanonicalAuthenticationResult::STATUS_INVALID_CREDENTIALS
            );
        }

        $user = $this->users->findByUsername($normalisedIdentifier);

        if (!is_array($user)) {
            $this->recordFailure($normalisedIdentifier, $context);

            return CanonicalAuthenticationResult::failure(
                self::PROVIDER_ID,
                CanonicalAuthenticationResult::STATUS_INVALID_CREDENTIALS
            );
        }

        $userId = isset($user['user_id']) ? trim((string) $user['user_id']) : '';
        $username = isset($user['username'])
            ? trim((string) $user['username'])
            : $normalisedIdentifier;
        $storedHash = isset($user['password_hash'])
            ? (string) $user['password_hash']
            : '';

        if ($userId === '' || !$this->users->isUserActive($userId)) {
            return CanonicalAuthenticationResult::failure(
                self::PROVIDER_ID,
                CanonicalAuthenticationResult::STATUS_INACTIVE
            );
        }

        if (!$this->passwords->verify(
            $plainTextPassword,
            $storedHash,
            $user
        )) {
            $this->recordFailure($normalisedIdentifier, $context);

            return CanonicalAuthenticationResult::failure(
                self::PROVIDER_ID,
                CanonicalAuthenticationResult::STATUS_INVALID_CREDENTIALS
            );
        }

        $metadata = array(
            'password_rehash_required' =>
                $this->passwords->needsRehash($storedHash),
        );

        return CanonicalAuthenticationResult::success(
            self::PROVIDER_ID,
            $userId,
            $username,
            array(),
            $metadata
        );
    }

    private function recordFailure($identifier, array $context)
    {
        $this->users->recordFailedLogin((string) $identifier, $context);
    }
}
