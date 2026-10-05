<?php
/**
 * Publishes identity epochs to the denylist that field services read.
 *
 * A plain collaborator, for the same reason TokenAuthService is one: it needs no
 * engine handle, and inventing one to reach a module configuration would be
 * worse than being explicit about what it needs.
 *
 * It speaks RESP over a socket rather than using a client library. The Redis
 * extension is not present in this container, and the two commands this needs —
 * SET with an expiry — are a few lines of text protocol. Adding an extension to
 * the image for that would be a build change to every installation to avoid
 * twenty lines of PHP, and would leave a native dependency in the path of
 * something that decides who may write data.
 *
 * What it writes is read by the Go denylist, so the format is not ours to
 * choose: the key is revoked:<subject> and the value is <unix seconds>:<epoch>.
 * A value that service cannot parse is treated as revoked at its widest, so a
 * format mistake here does not fail visibly — it refuses every request. That is
 * why the two shapes are written out in full rather than assembled from parts.
 *
 * @author Chisimba Revival
 */
class DenylistPublisher
{
    /**
     * How long an entry is kept, in seconds.
     *
     * Must be at least the lifetime of the longest token it has to invalidate.
     * The default of a day comfortably covers an hour-long access token. A
     * refresh token is not a problem here: refreshing mints a token carrying
     * today's grants, which is exactly what a reduction should leave somebody
     * with.
     */
    const DEFAULT_TTL = 86400;

    /**
     * Write an epoch for a subject.
     *
     * @param string $subject The user identifier the token carries
     * @param int $epoch The epoch now in force
     * @return void
     * @throws RuntimeException When the denylist cannot be written
     */
    public function publish(string $subject, int $epoch): void
    {
        if (trim($subject) === '') {
            throw new RuntimeException('A denylist entry needs a subject.');
        }

        $address = $this->address();
        $reply = $this->command($address, array(
            'SET',
            'revoked:' . $subject,
            time() . ':' . $epoch,
            'EX',
            (string) $this->ttl(),
        ));

        if ($reply !== '+OK') {
            throw new RuntimeException(
                'The denylist refused the epoch for this user (' . $reply . ').'
            );
        }
    }

    /**
     * The configured denylist address.
     *
     * Refused rather than defaulted. A gateway with no denylist configured
     * cannot enforce a revocation, and defaulting to localhost would look like
     * it was enforcing one on installations where it was not — which is the
     * failure this whole design exists to prevent.
     *
     * @return string
     */
    private function address(): string
    {
        $configured = getenv('CHISIMBA_DENYLIST_REDIS');
        if ($configured === false || trim($configured) === '') {
            throw new RuntimeException(
                'CHISIMBA_DENYLIST_REDIS is not set, so an epoch cannot be '
                . 'published and a sign-in would be refused on a change nobody '
                . 'could see. Point it at the Redis the field services read.'
            );
        }
        return trim($configured);
    }

    /**
     * How long an entry is kept, refusing a value below an access token's life.
     *
     * Clamping would be worse than refusing here, exactly as it is in the Go
     * denylist: an entry that expires too early silently reopens the window
     * this exists to close, and nothing reports it.
     *
     * @return int
     */
    private function ttl(): int
    {
        $configured = getenv('CHISIMBA_DENYLIST_TTL');
        if ($configured === false || trim($configured) === '') {
            return self::DEFAULT_TTL;
        }
        if (!ctype_digit(trim($configured))) {
            throw new RuntimeException('CHISIMBA_DENYLIST_TTL must be whole seconds.');
        }
        $ttl = (int) trim($configured);
        if ($ttl < 3600) {
            throw new RuntimeException(
                'CHISIMBA_DENYLIST_TTL is below the one hour lifetime of an '
                . 'access token, so an entry could expire while a token it must '
                . 'invalidate is still valid.'
            );
        }
        return $ttl;
    }

    /**
     * Send one command and read its reply.
     *
     * The socket is closed with fclose() on every path. A denylist publisher
     * that leaks a file handle per sign-in would exhaust the web server's
     * descriptors under exactly the traffic pattern — many people signing in
     * after a permission change — that matters most here.
     *
     * @param string $address host:port
     * @param array $parts The command and its arguments
     * @return string The reply line
     */
    private function command(string $address, array $parts): string
    {
        $pieces = explode(':', $address);
        if (count($pieces) !== 2 || !ctype_digit($pieces[1])) {
            throw new RuntimeException(
                'CHISIMBA_DENYLIST_REDIS must be host:port, not ' . $address . '.'
            );
        }

        $socket = @fsockopen($pieces[0], (int) $pieces[1], $errno, $errstr, 3);
        if ($socket === false) {
            throw new RuntimeException(
                'The denylist at ' . $address . ' could not be reached (' . $errstr
                . '), so an epoch cannot be published.'
            );
        }

        try {
            $request = '*' . count($parts) . "\r\n";
            foreach ($parts as $part) {
                $request .= '$' . strlen($part) . "\r\n" . $part . "\r\n";
            }
            if (@fwrite($socket, $request) === false) {
                throw new RuntimeException('The denylist connection dropped while writing.');
            }

            $reply = @fgets($socket);
            if ($reply === false) {
                throw new RuntimeException('The denylist closed the connection without replying.');
            }
            return rtrim($reply, "\r\n");
        } finally {
            fclose($socket);
        }
    }
}