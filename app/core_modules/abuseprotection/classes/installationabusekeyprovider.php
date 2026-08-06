<?php
require_once dirname(__DIR__, 3)
    . '/classes/core/installationmasterkeyprovider.php';
/** Supplies the purpose-derived abuse-protection key. @author Derek Keats */
final class InstallationAbuseKeyProvider
{
    public function getKey()
    {
        $provider = new InstallationMasterKeyProvider();
        return $provider->deriveKey('abuse-protection-v1');
    }
}
