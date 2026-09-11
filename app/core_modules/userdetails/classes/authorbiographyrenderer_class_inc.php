<?php
/** Semantic biography display; visual rules belong to the skin. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class authorbiographyrenderer extends ChisimbaObject
{
    private $service;
    private $user;
    private $language;
    private $icons;

    public function init()
    {
        $this->service = $this->getObject('authorbiographyservice', 'userdetails');
        $this->user = $this->getObject('user', 'security');
        $this->language = $this->getObject('language', 'language');
        $this->icons = $this->getObject('iconservice', 'ui');
    }

    /** Public course views omit external links and missing biographies. */
    public function forCourse($contextCode)
    {
        $html = '';
        $missing = array();
        foreach ($this->service->forCourse($contextCode) as $author) {
            if (trim($author['biography']) !== '') {
                $html .= $this->person($author, false);
            } else {
                $missing[] = $author;
            }
        }
        if ($missing && $this->user->isLoggedIn() && ($this->user->isAdmin()
            || $this->user->isContextLecturer($this->user->userId(), $contextCode))) {
            $html .= '<aside class="chisimba-guidance-card"><p>'
                .self::escape($this->language->code2Txt('mod_userdetails_bio_missing', 'userdetails')).'</p><ul>';
            foreach ($missing as $author) {
                $html .= '<li>'.self::escape($author['name']);
                if ($author['userid'] === (string)$this->user->userId()) {
                    $html .= ' <a href="'.self::escape($this->uri(array('action'=>'biography'), 'userdetails')).'">'
                        .self::escape($this->language->code2Txt('mod_userdetails_bio_complete', 'userdetails')).'</a>';
                }
                $html .= '</li>';
            }
            $html .= '</ul></aside>';
        }
        if ($html === '') return '';
        $heading = $this->language->code2Txt('mod_userdetails_about_authors', 'userdetails');
        return '<section class="chisimba-form-section"><h2>'.self::escape($heading).'</h2>'.$html.'</section>';
    }

    /** Shared card used by course descriptions and the owner's preview. */
    public function person(array $author, $showLinks = false)
    {
        $html = '<article class="chisimba-biography-card"><div class="chisimba-biography-portrait">'
            .$this->portrait($author['userid'], $author['name'])
            .'</div><div class="chisimba-biography-copy"><h3>'.self::escape($author['name']).'</h3>';
        foreach (preg_split('/\R\s*\R/u', $author['biography']) as $paragraph) {
            $html .= '<p>'.nl2br(self::escape($paragraph)).'</p>';
        }
        if ($showLinks && !empty($author['links'])) {
            $html .= '<ul class="chisimba-biography-links">';
            foreach ($author['links'] as $link) {
                if (!authorbiographyvalue::safeUrl($link['url'] ?? '')) continue;
                $html .= '<li><a rel="me noopener noreferrer" href="'.self::escape($link['url']).'">'
                    .$this->icons->render('external-link', array('decorative' => true))
                    .'<span>'.self::escape($link['label']).'</span></a></li>';
            }
            $html .= '</ul>';
        }
        return $html.'</div></article>';
    }

    /** Reuse the account portrait, with a native icon when no photo has been uploaded. */
    public function portrait($userId, $name)
    {
        if ($this->user->hasCustomImage($userId)) {
            return $this->user->getUserImage($userId, false, self::escape($name));
        }
        return '<span class="chisimba-biography-placeholder" aria-hidden="true">'
            .$this->icons->render('user', array('decorative' => true)).'</span>';
    }

    private static function escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
