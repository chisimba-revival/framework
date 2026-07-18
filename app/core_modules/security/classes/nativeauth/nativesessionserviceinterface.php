<?php
/**
 * Adapter contract around Chisimba's existing session conventions.
 */
interface NativeSessionServiceInterface
{
    /** @return bool */
    public function establish($userId, array $attributes = array());

    /** @return bool */
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
