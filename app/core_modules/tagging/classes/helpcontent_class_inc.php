<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic) { return $topic === 'suggestions'; }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) return null;
        $t = fn($key) => $this->getObject('language', 'language')->code2Txt('mod_tagging_' . $key, 'tagging');
        return ['title' => $t('tags'), 'summary' => $t('instructions'),
            'steps' => [$t('privacy'), $t('recovery'), $t('demo')]];
    }
}
