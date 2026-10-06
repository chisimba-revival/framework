<?php
/** Semantic site menu; appearance belongs to the active skin. @author Derek Keats */
class sitenavigation extends ChisimbaObject
{
    private function escape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
    private function text($key) { return ucfirst($this->getObject('language', 'language')->code2Txt('mod_toolbar_nav_' . $key, 'toolbar')); }
    private function link($label, $icon, $url, $active = false)
    {
        return '<a href="' . $this->escape(html_entity_decode($url, ENT_QUOTES, 'UTF-8')) . '"'
            . ($active ? ' aria-current="page"' : '') . '>'
            . $this->getObject('iconservice', 'ui')->render($icon ?: 'link', array('decorative' => true))
            . '<span>' . $this->escape($label) . '</span></a>';
    }
    public function show()
    {
        $service = $this->getObject('navigationservice', 'toolbar');
        if (!$service->usesSiteProfile()) return '';
        $groups = array();
        foreach ($service->links() as $link) {
            $key = $link['group'] === '' ? 'link:' . $link['id'] : 'group:' . $link['group'];
            $groups[$key][] = $link;
        }
        $html = '<nav class="chisimba-site-navigation" aria-label="' . $this->escape($this->text('site')) . '">'
            . '<details class="chisimba-site-navigation__shell" open><summary>'
            . $this->getObject('iconservice', 'ui')->render('menu', array('decorative' => true))
            . $this->escape($this->text('menu')) . '</summary><div class="chisimba-site-navigation__body"><ul class="chisimba-site-navigation__links">';
        foreach ($groups as $links) {
            $grouped = $links[0]['group'] !== '';
            $html .= '<li>';
            if ($grouped) $html .= '<details class="chisimba-site-navigation__group"><summary>' . $this->escape($links[0]['groupLabel']) . '</summary><ul>';
            foreach ($links as $link) {
                if ($grouped) $html .= '<li>';
                $html .= $this->link($link['label'], $link['icon'], $link['url'], $link['active']);
                if ($grouped) $html .= '</li>';
            }
            if ($grouped) $html .= '</ul></details>';
            $html .= '</li>';
        }
        $html .= '</ul><ul class="chisimba-site-navigation__account">';
        $security = $this->getObject('toolbarsecuritycontext', 'toolbar');
        if ($security->isAuthenticated()) {
            $html .= $this->getObject('notificationmenu', 'toolbar')->show();
            $html .= '<li><details class="chisimba-site-navigation__group"><summary>'
                . $this->getObject('iconservice', 'ui')->render('user', array('decorative' => true))
                . $this->escape($this->text('account')) . '</summary><ul>';
            if ($this->getObject('modules', 'modulecatalogue')->checkIfRegistered('userdetails')) {
                $html .= '<li>' . $this->link($this->text('profilelink'), 'user', $this->uri(null, 'userdetails')) . '</li>';
            }
            if ($security->isSiteAdministrator()) {
                $html .= '<li>' . $this->link($this->text('admin'), 'settings', $this->uri(null, 'toolbar')) . '</li>';
                $html .= '<li>' . $this->link($this->text('configure'), 'panel-top', $this->uri(array('action' => 'editlinks'), 'toolbar')) . '</li>';
            }
            $html .= '<li>' . $security->logoutForm($this->getObject('language','language')->code2Txt('word_logout','system')) . '</li></ul></details></li>';
        } else {
            // Authentication validates this relative return target before using it.
            $html .= '<li>' . $this->link($this->text('signin'), 'log-in', $this->uri(array('action' => 'showlogin', 'return_to' => $_SERVER['REQUEST_URI'] ?? ''), 'security')) . '</li>';
        }
        $html .= '</ul></div></details></nav>';
        $html .= '<script defer src="' . $this->escape($this->getResourceUri('site-navigation.js', 'toolbar')) . '"></script>';
        return $html;
    }
}
