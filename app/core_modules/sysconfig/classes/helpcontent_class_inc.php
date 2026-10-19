<?php
/** Site configuration editor guidance through the shared Help service. */
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic)
    {
        return $topic === 'site-settings' && $this->getObject('user', 'security')->isAdmin();
    }

    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) { return null; }
        $language = $this->getObject('language', 'language');
        $text = static function ($key, $fallback) use ($language) {
            return $language->languageText('mod_sysconfig_help_' . $key, 'sysconfig', $fallback);
        };
        return array(
            'title' => $text('title', 'Edit site settings'),
            'summary' => $text('summary', 'Administrators can change settings that affect the whole site. Check the parameter and value carefully before saving.'),
            'steps' => array(
                $text('open', 'Open the parameter editor from System configuration.'),
                $text('save', 'Enter the required value and select Save. A successful save returns to the parameter list.'),
                $text('failure', 'If saving fails, your entered value remains in the form. Copy it before reloading and reviewing newer settings. Contact the administrator if saving still fails.')
            ),
            'sections' => array(array(
                'heading' => $text('access', 'Access and recovery'),
                'body' => $text('recovery', 'Only administrators can save site settings. An expired form or a change made after you opened it can prevent saving. Reload the editor, review the current value and reapply your intended change.')
            ))
        );
    }
}
