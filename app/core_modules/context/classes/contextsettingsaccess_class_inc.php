<?php
/** Current course settings authority and purpose-separated form tokens. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class contextsettingsaccess extends ChisimbaObject
{
    public function init() {}
    public function canEdit($code): bool
    {
        if (!is_string($code) || $code === '' || $code === 'root') return false;
        $user = $this->getObject('user', 'security');
        return $user->isLoggedIn() && ($user->lookupAdmin($user->userId())
            || $this->getObject('contextauthorservice', 'contextadmin')->isAuthor($code, $user->userId()));
    }
    public function purpose($code): string { return 'context_settings_'.substr(hash('sha256', $code), 0, 32); }
    public function csrf() { return $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']; }
    public function check($scope, array $input, $method): string
    {
        if (!$this->canEdit($scope) || ($input['contextcode'] ?? null) !== $scope) return 'denied';
        if ($method !== 'POST') return 'method';
        if (!is_string($input['csrf_token'] ?? null) || !$this->csrf()->consume($this->purpose($scope), $input['csrf_token'])) return 'csrf';
        return '';
    }
}
