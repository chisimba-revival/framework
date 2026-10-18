<?php
/** Help for the protected information form. */
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topicId)
    {
        return in_array($topicId, ['information','wizard-final'], true) && $this->getObject('contextsettingsaccess', 'context')->canEdit($this->getSession('contextCode'));
    }
    public function getTopic($topicId)
    {
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
