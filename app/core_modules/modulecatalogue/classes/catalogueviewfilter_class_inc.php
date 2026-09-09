<?php
/** Installation-state filtering independent of categories and catalogue refreshes.
 * @author Derek Keats
 * @package modulecatalogue
 */
class catalogueviewfilter extends ChisimbaObject
{
    public function resolve($requested, $legacy, $saved, $legacySaved = false)
    {
        if ($requested !== null) { return in_array($requested, array('all','installed','new'), true) ? $requested : 'all'; }
        if ($legacy !== null) { return (string)$legacy === '1' ? 'installed' : 'all'; }
        if (in_array($saved, array('all','installed','new'), true)) { return $saved; }
        return $legacySaved ? 'installed' : 'all';
    }
    public function includes($filter, $installed)
    {
        return $filter === 'new' ? !$installed : ($filter === 'installed' ? $installed : true);
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
