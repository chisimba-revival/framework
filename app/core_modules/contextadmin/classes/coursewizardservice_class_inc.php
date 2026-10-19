<?php
/** Current-authority and request boundary for the final wizard steps. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class coursewizardservice extends ChisimbaObject
{
    public function purpose($code, $step) { return 'course_wizard_' . $step . '_' . substr(hash('sha256', $code), 0, 32); }
    public function csrf() { return $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']; }
    public function canEdit($code) { return $this->getObject('contextsettingsaccess', 'context')->canEdit($code); }
    public function offeredPlugins() { return $this->getObject('modules', 'modulecatalogue')->getListContextPlugins(); }
    public function submit($code, $step, array $input, $method)
    {
        if (!in_array($step, ['outcomes','plugins'], true) || !$this->canEdit($code) || ($input['contextCode'] ?? null) !== $code) return 'denied';
        if ($method !== 'POST') return 'method';
        if (!is_string($input['csrf_token'] ?? null) || !$this->csrf()->consume($this->purpose($code, $step), $input['csrf_token'])) return 'expired';
        if (!is_array($input['changes'] ?? null)) return 'invalid';
        if (($input['operation'] ?? 'save') === 'add' && $step === 'outcomes') return 'add';
        try {
            $allowed = $step === 'plugins' ? array_column($this->offeredPlugins(), 'module_id') : [];
            $this->getObject('coursewizardstore', 'contextadmin')->save($code, $step, $input['revision'] ?? null, $input['changes'], $allowed);
            return '';
        } catch (Throwable $error) {
            return in_array($error->getMessage(), ['stale','invalid','linked','missing','denied'], true) ? $error->getMessage() : 'save';
        }
    }
}
