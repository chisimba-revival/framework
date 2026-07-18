<?php

/**
 * Session operations required by native Chisimba authentication.
 */
interface AuthSessionInterface
{
    /**
     * Start or resume the authentication session.
     *
     * @return bool
     */
    public function start();

    /**
     * Establish an authenticated session.
     *
     * Implementations must regenerate the session identifier before storing
     * authenticated state.
     *
     * @param array $identity
     * @return bool
     */
    public function establish(array $identity);

    /**
     * Return the stored authenticated identity.
     *
     * @return array|null
     */
    public function getIdentity();

    /**
     * Destroy authentication state and invalidate the session.
     *
     * @return bool
     */
    public function destroy();
}
