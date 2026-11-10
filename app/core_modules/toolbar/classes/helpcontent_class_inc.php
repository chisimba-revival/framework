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
        $text = fn($key) => html_entity_decode($this->getObject('language', 'language')->code2Txt('mod_toolbar_nav_' . $key, 'toolbar'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return array('title' => $text('help_title'), 'summary' => $text('help_summary'),
            'steps' => array($text('help_steps'), $text('page_help'), $text('menu_label_help'), $text('learning_help'), $text('help_groups'), $text('arguments_help'), $text('help_recovery')));
    }
}
