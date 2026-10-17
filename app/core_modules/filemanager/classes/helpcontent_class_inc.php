<?php
/** Quota administration guidance through the shared Help module. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic)
    {
        $user = $this->getObject('user', 'security');
        if ($topic === 'taggedfiles') return $user->isLoggedIn();
        return $topic === 'quotas' && $user->lookupAdmin($user->userId());
    }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) return null;
        if ($topic === 'taggedfiles') {
            $t = fn($key) => $this->getObject('language', 'language')->code2Txt('mod_filemanager_tagged_' . $key, 'filemanager');
            return ['title' => $t('title'), 'summary' => $t('summary'), 'steps' => [$t('read'), $t('recover')]];
        }
        $t = fn($key) => $this->getObject('language', 'language')->code2Txt('mod_filemanager_quota_' . $key, 'filemanager');
        return ['title' => $t('help_title'), 'summary' => $t('help_summary'),
            'steps' => [$t('help_search'), $t('help_save'), $t('help_recovery')]];
    }
}
