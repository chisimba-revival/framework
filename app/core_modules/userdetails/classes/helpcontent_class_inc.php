<?php
/** Contextual help for the public biography workflow. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class helpcontent extends ChisimbaObject
{
    private $language;
    private $user;
    public function init()
    {
        $this->language=$this->getObject('language','language');
        $this->user=$this->getObject('user','security');
    }
    public function mayViewTopic($topicId)
    {
        return $topicId==='author-biography' && $this->user->isLoggedIn();
    }
    public function getTopic($topicId)
    {
        if ($topicId!=='author-biography') return null;
        $text = fn($suffix) => ucfirst($this->language->code2Txt('mod_userdetails_help_bio_' . $suffix, 'userdetails'));
        return array(
            'title'=>$text('title'), 'summary'=>$text('summary'),
            'steps'=>array($text('step_open'),$text('step_write'),$text('step_photo'),$text('step_links'),$text('step_save')),
            'sections'=>array(
                array('heading'=>$text('reuse_heading'),'body'=>$text('reuse_body')),
                array('heading'=>$text('privacy_heading'),'body'=>$text('privacy_body')),
                array('heading'=>$text('draft_heading'),'body'=>$text('draft_body')),
                array('heading'=>$text('missing_heading'),'body'=>$text('missing_body')),
            ),
        );
    }
}
