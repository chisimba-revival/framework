<?php
/** Contextual guide to local catalogue discovery and recovery. */
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic) { return $topic==='refresh' && $this->getObject('user','security')->isAdmin(); }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) { return null; }
        $language=$this->getObject('language','language');
        $text=static fn($key,$fallback)=>$language->languageText('mod_modulecatalogue_help_'.$key,'modulecatalogue',$fallback);
        return array('title'=>$text('title','Refresh the local catalogue'),
            'summary'=>$text('summary','Administrators can refresh the catalogue after adding or removing module source files. Refreshing discovers modules; installing or updating them is a separate action.'),
            'steps'=>array($text('scan','Select Refresh catalogue. Every available module registration is checked before the new catalogue is saved.'),
                $text('reconcile','After a complete scan is saved, registrations for modules whose source is no longer present are removed from the installed module list.'),
                $text('recover','If refreshing fails, reload and try again. Check missing module directories, incomplete registration files and configuration permissions before retrying. Do not remove module files merely to clear an error.')));
    }
}
