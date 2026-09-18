<?php
require_once dirname(__DIR__, 4)
    . '/classes/core/installationmasterkeyprovider.php';
/** Supplies the purpose-derived installation MFA key. @author Derek Keats */
final class InstallationMfaKeyProvider
{
    private $masterKeys;

    public function __construct(?InstallationMasterKeyProvider $masterKeys = null)
    {
        $this->masterKeys = $masterKeys ?? new InstallationMasterKeyProvider();
    }

    public function getKey()
    {
        return $this->masterKeys->deriveKey('mfa-encryption-v1');
    }
}
