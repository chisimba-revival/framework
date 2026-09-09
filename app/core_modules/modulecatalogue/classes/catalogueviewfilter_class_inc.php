<?php
/** Installation-state filtering independent of categories and catalogue refreshes.
 * @author Derek Keats
 * @package modulecatalogue
 */
class catalogueviewfilter extends ChisimbaObject
{
    public function resolve($requested, $legacy, $saved, $legacySaved = false)
    {
        if ($requested !== null) { return in_array($requested, array('all','installed','uninstalled','new'), true) ? $requested : 'all'; }
        if ($legacy !== null) { return (string)$legacy === '1' ? 'installed' : 'all'; }
        if (in_array($saved, array('all','installed','uninstalled','new'), true)) { return $saved; }
        return $legacySaved ? 'installed' : 'all';
    }
    public function includes($filter, $installed, $created = null, $today = null)
    {
        if ($filter === 'new') { return $this->isRecent($created, $today); }
        return $filter === 'uninstalled' ? !$installed : ($filter === 'installed' ? $installed : true);
    }
    /** Immutable first-release date, never file modification or latest release date. */
    public function isRecent($created, $today = null)
    {
        if (!is_string($created)) { return false; }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $created);
        if (!$date || $date->format('Y-m-d') !== $created) { return false; }
        $today = $today ?? new DateTimeImmutable('today');
        return $date <= $today && $date > $today->modify('-30 days');
    }
    public function createdDate($moduleId)
    {
        $config = $this->getObject('altconfig', 'config');
        foreach (array($config->getsiteRootPath().'core_modules/', $config->getModulePath()) as $root) {
            $path = $root.$moduleId.'/register.conf';
            if (is_file($path)) {
                $data = $this->getObject('modulefile', 'modulecatalogue')->readRegisterFile($path);
                return $data['MODULE_CREATEDATE'] ?? null;
            }
        }
        return null;
    }
    /** Support both catalogue APIs: lists of IDs and ID-to-name maps. */
    public function localIds(array $modules)
    {
        $ids = array();
        foreach ($modules as $key => $value) {
            $id = is_int($key) || ctype_digit((string)$key) ? (string)$value : (string)$key;
            if ($id !== '') { $ids[] = $id; }
        }
        return array_values(array_unique($ids));
    }
}
?>
