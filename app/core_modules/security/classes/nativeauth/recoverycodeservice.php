<?php
/**
 * Generates and verifies single-use MFA recovery codes.
 *
 * @author Derek Keats
 */
class RecoveryCodeService
{
    public function generate($count = 10)
    {
        $plain = array();
        $hashes = array();
        for ($i = 0; $i < max(1, (int) $count); $i++) {
            $code = strtoupper(bin2hex(random_bytes(5)));
            $plain[] = substr($code, 0, 5) . '-' . substr($code, 5);
            $hashes[] = password_hash($this->normalise($plain[$i]), PASSWORD_DEFAULT);
        }
        return array('plain' => $plain, 'hashes' => $hashes);
    }

    public function matches($code, $hash)
    {
        return password_verify($this->normalise($code), $hash);
    }

    private function normalise($code)
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $code));
    }
}
