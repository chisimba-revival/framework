<?php
/**
 * Sole boundary for establishing and clearing authenticated Chisimba state.
 *
 * Implementations must regenerate the session identifier before writing a new
 * authenticated identity. They must not calculate groups, permissions, roles,
 * or administrator status.
 */
interface NativeSessionServiceInterface
{
    /**
     * Establish authenticated identity and regenerate the session identifier.
     *
     * @return bool
     */
    public function establish($userId, array $attributes = array());

    /**
     * Clear authenticated identity without destroying unrelated UI/session
     * preferences such as the selected skin.
     *
     * @return bool
     */
    public function destroy();

    /** @return bool */
    public function regenerateIdentifier();

    /** @return string|null */
    public function getUserId();

    /** @return bool */
    public function isAuthenticated();

    /** @return mixed */
    public function get($name, $default = null);

    /** @return void */
    public function set($name, $value);

    /** @return void */
    public function remove($name);
}
