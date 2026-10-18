<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject {
    public function mayViewTopic($topic) { return $topic === 'reading'; }
    public function getTopic($topic) {
        if (!$this->mayViewTopic($topic)) return null;
        $t=fn($key)=>$this->getObject('language','language')->languageText('mod_stories_native_help_'.$key,'stories');
        return ['title'=>$t('title'),'summary'=>$t('summary'),'steps'=>[$t('admin'),$t('recovery')]];
    }
}
