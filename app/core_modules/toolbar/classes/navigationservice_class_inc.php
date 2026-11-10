<?php
/**
 * Resolve site navigation from the canonical toolbar link store.
 * The editor, registrar and renderer share these records and access rules.
 * @package toolbar
 * @author Derek Keats
 */
class navigationservice extends ChisimbaObject
{
    /** Optional scalar destination arguments, kept inside the existing declaration. */
    public static function destinationArguments($query)
    {
        if (!is_string($query) || strlen($query) > 120) throw new InvalidArgumentException('Invalid destination');
        if ($query === '') return [];
        $result = [];
        foreach (explode('&', $query) as $pair) {
            $parts = explode('=', $pair, 2);
            $key = rawurldecode($parts[0]); $value = rawurldecode($parts[1] ?? '');
            if (count($parts) !== 2 || !preg_match('/^[a-z][a-z0-9_]{0,31}$/D', $key)
                || in_array($key, ['module', 'action', 'csrf_token', 'password', 'token'], true)
                || isset($result[$key]) || !preg_match('/^[a-zA-Z0-9_.-]{1,80}$/D', $value)) {
                throw new InvalidArgumentException('Invalid destination');
            }
            $result[$key] = $value;
        }
        return $result;
    }

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
        $rows = $this->getObject('dbmenu', 'toolbar')->siteLinks();
        $routeArguments = [];
        foreach ($rows as $candidate) {
            $fields = explode('|', $candidate['category']);
            try { $keys = array_keys(self::destinationArguments($fields[6] ?? '')); }
            catch (InvalidArgumentException $error) { continue; }
            $route = $candidate['module'].'|'.($fields[2] ?? '');
            $routeArguments[$route] = array_unique(array_merge($routeArguments[$route] ?? [], $keys));
        }
        foreach ($rows as $row) {
            $parts = explode('|', $row['category']);
            if (count($parts) < 5 || !preg_match('/^site_([0-9]{1,3})$/D', $parts[0], $order)
                || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $row['module'])
                || !preg_match('/^[a-zA-Z0-9_.-]{0,80}$/D', $parts[2])
                || !preg_match('/^[a-zA-Z0-9_.-]{0,80}$/D', $parts[3])
                || !preg_match('/^[a-zA-Z0-9_.-]{1,120}$/D', $parts[4])
                || !preg_match('/^[a-zA-Z0-9_:.-]{0,100}$/D', $parts[5] ?? '')) continue;
            try { $arguments = self::destinationArguments($parts[6] ?? ''); }
            catch (InvalidArgumentException $error) { continue; }
            if (count($parts) > 7) continue;
            if (!$modules->checkIfRegistered($row['module'])
                || (!empty($row['adminonly']) && !$security->isSiteAdministrator())
                || (!empty($row['dependscontext']) && !$context->isInContext())
                || !$security->mayUseRight($row['permissions'])) continue;
            if (!empty($row['dependscontext']) && !$this->getObject('dbcontextmodules', 'context')->isContextPlugin($context->getContextCode(), $row['module'])) continue;
            if ($row['module'] === 'sitepages' && str_starts_with($parts[4], 'page_')) {
                $page = $this->getObject('modulelinkdefaults', 'toolbar')->page($parts[4]);
                if (!$page || $page['status'] !== 'published') continue;
                $parts[2] = 'view';
                $arguments = ['slug' => $page['slug']];
                $routeArguments['sitepages|view'] = ['slug'];
            }
            $activeArguments = true;
            foreach ($routeArguments[$row['module'].'|'.$parts[2]] ?? [] as $key) {
                if ($this->getParam($key, '') !== ($arguments[$key] ?? '')) $activeArguments = false;
            }
            $links[] = array(
                'id' => $row['id'], 'module' => $row['module'], 'action' => $parts[2],
                'order' => (int) $order[1], 'icon' => $parts[3],
                'label' => (trim($row['menu_label'] ?? '') !== '' ? $row['menu_label'] : $this->getObject('modulelinkdefaults','toolbar')->label($parts[4], $row['module'])),
                'group' => $parts[5] ?? '',
                'groupLabel' => empty($parts[5]) ? '' : ucfirst($language->code2Txt($parts[5], $row['module'])),
                'url' => html_entity_decode($this->uri(array('action' => $parts[2]) + $arguments, $row['module']), ENT_QUOTES, 'UTF-8'),
                'active' => $this->getParam('module', '') === $row['module']
                    && $this->getParam('action', '') === $parts[2]
                    && $activeArguments,
            );
        }
        foreach ($this->getObject('externalnavigation', 'toolbar')->links() as $row) {
            if (($row['placement'] ?? 'site') !== 'site' || !externalnavigation::validUrl($row['url'])) continue;
            $authenticated=$security->isAuthenticated();
            if (!in_array($row['audience'],['everyone','visitors','members'],true)
                || ($row['audience']==='visitors' && $authenticated)
                || ($row['audience']==='members' && !$authenticated)) continue;
            $links[]=['id'=>$row['id'],'module'=>'','action'=>'','order'=>(int)$row['position'],
                'icon'=>'external-link','label'=>$row['label'],'group'=>$row['group_key'],
                'groupLabel'=>$row['group_label'],'url'=>$row['url'],'active'=>false];
        }
        return $this->getObject('navigationcategories', 'toolbar')->arrange($links);
    }

    /** Editor inventory includes hidden links so administrators can manage them. */
    public function rawEditorLinks()
    {
        $language=$this->getObject('language','language');
        $links=[];
        foreach($this->getObject('dbmenu','toolbar')->siteLinks() as $row){
            $p=explode('|',$row['category']);
            $links[]=['id'=>$row['id'],'module'=>$row['module'],'external'=>false,'placement'=>'site',
                'label'=>(trim($row['menu_label']??'') !== '' ? $row['menu_label'] : $this->getObject('modulelinkdefaults','toolbar')->label($p[4]??'', $row['module'])),
                'group'=>$p[5]??'', 'groupLabel'=>empty($p[5])?'':ucfirst($language->code2Txt($p[5],$row['module'])),
                'order'=>(int)substr($p[0],5)];
        }
        foreach($this->getObject('externalnavigation','toolbar')->links() as $row){
            $links[]=['id'=>$row['id'],'module'=>'toolbar','external'=>true,'placement'=>$row['placement']??'site','label'=>$row['label'],
                'url'=>$row['url'],'audience'=>$row['audience'],'group'=>$row['group_key'],
                'groupLabel'=>$row['group_label'],'order'=>(int)$row['position']];
        }
        usort($links,static fn($a,$b)=>[$a['order'],$a['label'],$a['id']]<=>[$b['order'],$b['label'],$b['id']]);
        return $links;
    }

    public function editorLinks()
    {
        return $this->getObject('navigationcategories', 'toolbar')->arrange($this->rawEditorLinks());
    }

    public function editorGroups()
    {
        return array_column($this->getObject('navigationcategories', 'toolbar')->categories(), 'label', 'group_key');
    }

    /** Blank order appends to the selected bucket; it never writes category order. */
    public function linkPosition($value, $placement, $group, $excludeId = null)
    {
        if ($placement === 'site' && $group !== '' && !isset($this->editorGroups()[$group])) throw new InvalidArgumentException('invalid');
        if ($value === '') {
            $positions = [];
            foreach ($this->rawEditorLinks() as $link) {
                if ($link['id'] !== $excludeId && ($link['placement'] ?? 'site') === $placement && $link['group'] === $group) $positions[] = $link['order'];
            }
            if ($placement === 'site' && $group === '') {
                $positions = array_merge($positions, array_column($this->getObject('navigationcategories', 'toolbar')->categories(), 'position'));
            }
            $value = $positions ? max($positions) + 1 : 10;
        }
        $position = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>999]]);
        if ($position === false) throw new InvalidArgumentException('invalid');
        return $position;
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
