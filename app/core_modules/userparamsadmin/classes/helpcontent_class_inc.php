<?php
/** Preference guidance served through the shared contextual Help module. */
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic) { return $topic==='preferences' && $this->getObject('user','security')->isLoggedIn(); }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) { return null; }
        $lang=$this->getObject('language','language');
        $text=static fn($key,$default)=>$lang->languageText('mod_userparamsadmin_help_'.$key,'userparamsadmin',$default);
        return array('title'=>$text('title','Your user preferences'),
            'summary'=>$text('summary','Manage values used by modules for your account. Only add or change a parameter when you know what it controls.'),
            'steps'=>array($text('edit','Choose Add New Parameter or Edit, enter the value and select Save. Commas and quotation marks are supported.'),
                $text('delete','Delete removes only the selected parameter. Add it again if you need to restore it.'),
                $text('recover','If saving fails, your draft stays in the form. Copy it before reloading, review the current value, and retry. An expired form or newer settings can prevent a save.')),
            'sections'=>array(array('heading'=>$text('access','Access'), 'body'=>$text('accessbody','This editor changes your own preferences. Administrators can manage another account only through an authorised administration service.'))));
    }
}
