<?php
/** Context-sensitive help renderer. @package help */
class contextualhelp extends ChisimbaObject
{
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->icons = $this->getObject('iconservice', 'ui');
    }

    public function getTopic($module, $topicId)
    {
        if (!preg_match('/^[a-z0-9_-]+$/', (string) $module)
            || !preg_match('/^[a-z0-9_-]+$/', (string) $topicId)) {
            return null;
        }
        try {
            $provider = $this->getObject('helpcontent', $module);
        } catch (Throwable $exception) {
            return null;
        }
        if (!method_exists($provider, 'getTopic')
            || !method_exists($provider, 'mayViewTopic')
            || !$provider->mayViewTopic($topicId)) {
            return null;
        }
        $topic = $provider->getTopic($topicId);
        if (!is_array($topic) || empty($topic['title']) || empty($topic['summary'])) {
            return null;
        }
        $topic['module'] = $module;
        $topic['id'] = $topicId;
        return $topic;
    }

    public function show($module, $topicId)
    {
        $topic = $this->getTopic($module, $topicId);
        if ($topic === null) { return ''; }
        $escape = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        $label = $this->language->languageText('mod_help_contextual_label', 'help');
        $quick = $this->language->languageText('mod_help_contextual_quick', 'help');
        $full = $this->language->languageText('mod_help_contextual_full', 'help');
        $url = $this->uri(array(
            'action' => 'topic', 'rootModule' => $module, 'helpid' => $topicId
        ), 'help');
        $html = '<details class="chisimba-contextual-help chisimba-card">'
            . '<summary>' . $this->icons->render('circle-help', array('decorative' => true))
            . '<span>' . $escape($label) . '</span></summary>'
            . '<div class="chisimba-contextual-help__body"><p class="chisimba-eyebrow">'
            . $escape($quick) . '</p><h2>' . $escape($topic['title']) . '</h2><p>'
            . $escape($topic['summary']) . '</p>';
        if (!empty($topic['steps'])) {
            $html .= '<ol>';
            foreach ($topic['steps'] as $step) { $html .= '<li>' . $escape($step) . '</li>'; }
            $html .= '</ol>';
        }
        $html .= '<p><a class="button chisimba-button-secondary" href="'
            . $escape($url) . '">' . $this->icons->render('book-open', array('decorative' => true))
            . $escape($full) . '</a></p></div></details>';
        return $html;
    }
}
?>
