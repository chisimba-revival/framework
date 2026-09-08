<?php
/** Contextual Help topics owned by Context. @package context */
class helpcontent extends ChisimbaObject
{
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->user = $this->getObject('user', 'security');
        $this->context = $this->getObject('dbcontext', 'context');
        $this->groups = $this->getObject('managegroups', 'contextgroups');
    }

    public function mayViewTopic($topicId)
    {
        if ($topicId !== 'managing-the-context-home' || !$this->user->isLoggedIn()) {
            return false;
        }
        $contextCode = (string)$this->context->getContextCode();
        return $contextCode !== '' && ($this->user->isAdmin() || $this->groups->isContextLecturer());
    }

    public function getTopic($topicId)
    {
        if ($topicId !== 'managing-the-context-home') { return null; }
        $defaults = array(
            'title' => 'Manage the [-context-] home page',
            'summary' => 'Use blocks to shape the landing page that welcomes people to this [-context-], while keeping chapter learning content in [-context-] Content.',
            'step_editing' => 'Select Turn Editing On to reveal controls for adding, moving and removing blocks.',
            'step_create' => 'Select Manage [-context-] landing content to create rich text or a welcome video for this landing page.',
            'step_add' => 'Return to the [-context-] home page, choose the new item in the main Add a Block list, and select Add Block.',
            'step_arrange' => 'Use the arrow controls to place blocks in a useful reading order. Removing a block from the page does not delete its saved content.',
            'step_review' => 'Turn editing off and review the finished landing page as a learner will encounter it.',
            'content_heading' => 'Landing content and learning content',
            'content_body' => 'Landing-page text and welcome videos introduce the [-context-] and are not attached to chapters. Pages, assessments and the learning sequence remain in [-context-] Content.',
            'blocks_heading' => 'What the existing blocks do',
            'blocks_body' => 'About [-context-] comes from the main [-context-] settings. Learning outcomes comes from the saved outcomes. Your Learning Journey gives each person the appropriate starting or continuation action.',
            'permissions_heading' => 'Who can manage this page',
            'permissions_body' => 'The block controls and landing-content editor are available to administrators and people who manage this [-context-]. Learners see the completed page without editing controls.',
        );
        $text = function ($suffix) use ($defaults) {
            return $this->language->code2Txt(
                'mod_context_help_home_' . $suffix,
                'context',
                null,
                $defaults[$suffix]
            );
        };
        return array(
            'title' => $text('title'),
            'summary' => $text('summary'),
            'steps' => array(
                $text('step_editing'),
                $text('step_create'),
                $text('step_add'),
                $text('step_arrange'),
                $text('step_review'),
            ),
            'sections' => array(
                array('heading' => $text('content_heading'), 'body' => $text('content_body')),
                array('heading' => $text('blocks_heading'), 'body' => $text('blocks_body')),
                array('heading' => $text('permissions_heading'), 'body' => $text('permissions_body')),
            ),
        );
    }
}
?>
