<?php
require_once dirname(__DIR__, 4)
    . '/classes/core/installationmasterkeyprovider.php';
/** Supplies the purpose-derived installation MFA key. @author Derek Keats */
final class InstallationMfaKeyProvider
{
    public function getKey()
    {
        $provider = new InstallationMasterKeyProvider();
        return $provider->deriveKey('mfa-encryption-v1');
    }
}
