<?php
/** Resolve forwarding chains only across explicitly trusted proxy addresses/CIDRs. */
final class TrustedClientAddress
{
    public static function resolve(array $server, $trusted)
    {
        $peer = (string) ($server['REMOTE_ADDR'] ?? '');
        if (!filter_var($peer, FILTER_VALIDATE_IP)) { return ''; }
        $ranges = preg_split('/[\s,]+/', trim((string) $trusted), -1, PREG_SPLIT_NO_EMPTY);
        if (!self::trusted($peer, $ranges)) { return $peer; }
        $forwarded = (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '');
        if (strlen($forwarded) > 2048) { return $peer; }
        $chain = array_map('trim', explode(',', $forwarded));
        foreach ($chain as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP)) { return $peer; }
        }
        for ($i = count($chain) - 1; $i >= 0 && self::trusted($peer, $ranges); --$i) {
            $peer = $chain[$i];
        }
        return $peer;
    }

    private static function trusted($address, array $ranges)
    {
        $address = inet_pton($address);
        foreach ($ranges as $range) {
            $parts = explode('/', $range, 2);
            if (!filter_var($parts[0], FILTER_VALIDATE_IP)) { continue; }
            $network = inet_pton($parts[0]);
            if (strlen($network) !== strlen($address)) { continue; }
            $bits = isset($parts[1]) ? filter_var($parts[1], FILTER_VALIDATE_INT) : strlen($address) * 8;
            if ($bits === false || $bits < 0 || $bits > strlen($address) * 8) { continue; }
            $bytes = intdiv($bits, 8); $remainder = $bits % 8;
            if (substr($address, 0, $bytes) === substr($network, 0, $bytes)
                && (!$remainder || ((ord($address[$bytes]) ^ ord($network[$bytes])) & (255 << (8 - $remainder))) === 0)) {
                return true;
            }
        }
        return false;
    }
}
