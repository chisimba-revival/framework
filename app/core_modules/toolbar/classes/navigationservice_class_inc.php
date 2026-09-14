<?php
/**
 * Resolve site navigation from the canonical toolbar link store.
 * The editor, registrar and renderer share these records and access rules.
 * @package toolbar
 * @author Derek Keats
 */
class navigationservice extends ChisimbaObject
{
    public function usesSiteProfile()
    {
        return strtolower((string) $this->getObject('dbsysconfig', 'sysconfig')
            ->getValue('TOOLBAR_TYPE', 'toolbar', 'dropdown')) === 'site';
    }

    /** Malformed or unavailable destinations fail closed without affecting others. */
    public function links()
    {
        if (!$this->usesSiteProfile()) return array();
        $security = $this->getObject('toolbarsecuritycontext', 'toolbar');
        $context = $this->getObject('dbcontext', 'context');
        $modules = $this->getObject('modules', 'modulecatalogue');
        $language = $this->getObject('language', 'language');
        $links = array();
        foreach ($this->getObject('dbmenu', 'toolbar')->siteLinks() as $row) {
            $parts = explode('|', $row['category']);
            if (count($parts) < 5 || !preg_match('/^site_([0-9]{1,3})$/D', $parts[0], $order)
                || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $row['module'])
                || !preg_match('/^[a-zA-Z0-9_.-]{0,80}$/D', $parts[2])
                || !preg_match('/^[a-zA-Z0-9_.-]{0,80}$/D', $parts[3])
                || !preg_match('/^[a-zA-Z0-9_.-]{1,120}$/D', $parts[4])
                || !preg_match('/^[a-zA-Z0-9_.-]{0,80}$/D', $parts[5] ?? '')) continue;
            if (!$modules->checkIfRegistered($row['module'])
                || (!empty($row['adminonly']) && !$security->isSiteAdministrator())
                || (!empty($row['dependscontext']) && !$context->isInContext())
                || !$security->mayUseRight($row['permissions'])) continue;
            if (!empty($row['dependscontext']) && !$this->getObject('dbcontextmodules', 'context')->isContextPlugin($context->getContextCode(), $row['module'])) continue;
            $links[] = array(
                'id' => $row['id'], 'module' => $row['module'], 'action' => $parts[2],
                'order' => (int) $order[1], 'icon' => $parts[3],
                'label' => ucfirst($language->code2Txt($parts[4], $row['module'])),
                'group' => $parts[5] ?? '',
                'groupLabel' => empty($parts[5]) ? '' : ucfirst($language->code2Txt($parts[5], $row['module'])),
                'url' => html_entity_decode($this->uri(array('action' => $parts[2]), $row['module']), ENT_QUOTES, 'UTF-8'),
                'active' => $this->getParam('module', '') === $row['module']
                    && $this->getParam('action', '') === $parts[2],
            );
        }
        usort($links, static fn($a, $b) => [$a['order'], $a['module'], $a['id']] <=> [$b['order'], $b['module'], $b['id']]);
        return $links;
    }

    /** Modules can omit a duplicate local menu only when all its routes are present. */
    public function provides($module, array $actions)
    {
        if (!$this->usesSiteProfile()) return false;
        $shown = array();
        foreach ($this->links() as $link) if ($link['module'] === $module) $shown[] = $link['action'];
        return !array_diff($actions, $shown);
    }
}
