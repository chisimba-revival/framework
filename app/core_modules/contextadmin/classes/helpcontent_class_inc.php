<?php
/** Help for the protected information form. */
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topicId)
    {
        if ($topicId === 'deletion') return $this->getObject('user','security')->isLoggedIn();
        return in_array($topicId, ['information','wizard-final'], true) && $this->getObject('contextsettingsaccess', 'context')->canEdit($this->getSession('contextCode'));
    }
    public function getTopic($topicId)
    {
        if ($topicId === 'deletion') {
            $l=$this->getObject('language','language');
            return ['title'=>$l->code2Txt('mod_contextadmin_deletecontext','contextadmin'),'summary'=>$l->code2Txt('mod_contextadmin_delete_help','contextadmin'),'steps'=>[$l->code2Txt('mod_contextadmin_delete_recovery','contextadmin')]];
        }
        if ($topicId === 'wizard-final') {
            $language = $this->getObject('language', 'language');
            $t = static fn($key) => $language->code2Txt('mod_contextadmin_wizard_help_'.$key, 'contextadmin');
            return ['title'=>$t('title'),'summary'=>$t('summary'),'steps'=>[$t('edit'),$t('plugins'),$t('save')],
                'sections'=>[['heading'=>$t('recovery'),'body'=>$t('recoverybody')]]];
        }
        if ($topicId !== 'information') return null;
        $language = $this->getObject('language', 'language');
        $text = static function ($key) use ($language) { return $language->code2Txt('mod_contextadmin_information_' . $key, 'contextadmin'); };
        return ['title' => $text('title'), 'summary' => $text('summary'),
            'steps' => [$text('edit'), $text('image'), $text('save')],
            'sections' => [['heading' => $text('recovery'), 'body' => $text('recoverybody')]]];
    }
}
