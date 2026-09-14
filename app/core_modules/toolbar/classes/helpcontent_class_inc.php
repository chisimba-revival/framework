<?php
/** Guide for the canonical navigation editor. @author Derek Keats */
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($id)
    {
        return $id === 'navigation' && $this->getObject('toolbarsecuritycontext', 'toolbar')->isSiteAdministrator();
    }
    public function getTopic($id)
    {
        if (!$this->mayViewTopic($id)) return null;
        $text = fn($key) => $this->getObject('language', 'language')->code2Txt('mod_toolbar_nav_' . $key, 'toolbar');
        return array('title' => $text('help_title'), 'summary' => $text('help_summary'),
            'steps' => array($text('help_steps'), $text('help_groups'), $text('help_recovery')));
    }
}
