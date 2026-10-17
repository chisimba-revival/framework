<?php
/** Help for the protected information form. */
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topicId)
    {
        return $topicId === 'information' && $this->getObject('contextsettingsaccess', 'context')->canEdit($this->getSession('contextCode'));
    }
    public function getTopic($topicId)
    {
        if ($topicId !== 'information') return null;
        $language = $this->getObject('language', 'language');
        $text = static function ($key) use ($language) { return $language->code2Txt('mod_contextadmin_information_' . $key, 'contextadmin'); };
        return ['title' => $text('title'), 'summary' => $text('summary'),
            'steps' => [$text('edit'), $text('image'), $text('save')],
            'sections' => [['heading' => $text('recovery'), 'body' => $text('recoverybody')]]];
    }
}
