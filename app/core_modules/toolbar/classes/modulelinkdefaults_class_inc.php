<?php
/** Registered module destinations for the operator-facing navigation editor. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class modulelinkdefaults extends ChisimbaObject
{
    private array $registrations = [];

    private function registration($module)
    {
        if (!isset($this->registrations[$module])) {
            $data = $this->getObject('register', 'toolbar')->getModuleData($module);
            // Retain an editable inventory label when a previously linked module is absent.
            $this->registrations[$module] = is_array($data) ? $data : ['MODULE_NAME'=>ucfirst($module)];
        }
        return $this->registrations[$module];
    }

    /** Resolve stored page identities afresh, so renaming a page keeps its menu link. */
    public function page($code)
    {
        if (!preg_match('/^page_([a-zA-Z0-9_-]{1,64})$/D', $code, $match)
            || !$this->getObject('modules', 'modulecatalogue')->checkIfRegistered('sitepages')) return null;
        return $this->getObject('dbsitepages', 'sitepages')->find($match[1]) ?: null;
    }

    public function label($code, $module)
    {
        if ($module === 'sitepages' && str_starts_with($code, 'page_')) {
            $page = $this->page($code);
            return $page ? $page['title'] : $this->getObject('language', 'language')->code2Txt('mod_toolbar_nav_page_unavailable', 'toolbar');
        }
        $data = $this->registration($module);
        return ucfirst($this->getObject('language', 'language')->languageText(
            $code, $module, $data['MODULE_NAME'] ?? ucfirst($module)
        ));
    }

    public function moduleName($module)
    {
        return $this->label('mod_'.$module.'_name', $module);
    }

    /** Values come from registration or the owned saved row, never posted action/icon/code fields. */
    public function destinations($module, $existing = null)
    {
        $data = $this->registration($module);
        $texts = [];
        foreach ($data['TEXT'] ?? [] as $text) $texts[explode('|', $text, 2)[0]] = true;
        $code = isset($texts['mod_'.$module.'_title']) ? 'mod_'.$module.'_title' : 'mod_'.$module.'_name';
        $icon = $data['MODULE_ICON'] ?? 'link';
        if (!preg_match('/^[a-zA-Z0-9_.-]{1,80}$/D', $icon)) $icon = 'link';
        $options = ['default'=>['action'=>'','icon'=>$icon,'code'=>$code,'arguments'=>'','label'=>$this->label($code,$module)]];
        foreach ($data['SITE_NAV'] ?? [] as $declaration) {
            $p = explode('|', $declaration);
            if (count($p)<5 || !preg_match('/^[a-zA-Z0-9_.-]{0,80}$/D',$p[2])
                || !preg_match('/^[a-zA-Z0-9_.-]{0,80}$/D',$p[3]) || !preg_match('/^[a-zA-Z0-9_.-]{1,120}$/D',$p[4])) continue;
            $arguments=$p[6]??'';
            if (in_array($arguments,['site','context'],true)) $arguments='';
            try { navigationservice::destinationArguments($arguments); } catch (InvalidArgumentException $error) { continue; }
            $key='route_'.substr(hash('sha256',$p[2].'|'.$arguments),0,16);
            $options[$key]=['action'=>$p[2],'icon'=>$p[3]?:$icon,'code'=>$p[4],'arguments'=>$arguments,'label'=>$this->label($p[4],$module)];
        }
        if ($module === 'sitepages' && $this->getObject('modules', 'modulecatalogue')->checkIfRegistered('sitepages')) {
            $options = [];
            foreach ($this->getObject('dbsitepages', 'sitepages')->activeRows() as $page) {
                if ($page['status'] !== 'published') continue;
                // The page ID is the authority; the renderer resolves its current web address.
                $key = 'page_'.$page['id'];
                if (!preg_match('/^page_[a-zA-Z0-9_-]{1,64}$/D', $key)) continue;
                $options[$key] = ['action'=>'view','icon'=>'file-text','code'=>$key,'arguments'=>'','label'=>$page['title']];
            }
        }
        if ($existing !== null) {
            if ($existing['module'] !== $module || !str_starts_with($existing['category'],'site_')) throw new InvalidArgumentException('Invalid site link');
            $p=explode('|',$existing['category']);
            $savedCode=$p[4]??$code;
            // Old forms allowed a locale (e.g. en) to be mistaken for a text identifier.
            if (!str_contains($savedCode,'_')) $savedCode=$code;
            $options=['current'=>['action'=>$p[2]??'','icon'=>$p[3]??$icon,'code'=>$savedCode,'arguments'=>$p[6]??'','label'=>$this->label($savedCode,$module)]]+$options;
        }
        return $options;
    }
}
