<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');

/** Native, progressively enhanced comma-separated tag input. */
class ajaxtags extends ChisimbaObject
{
    public $name = 'tags';
    public $defaultValue = '';
    private static $sequence = 0;

    public function show()
    {
        $escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $id = 'ajaxtags_' . (++self::$sequence);
        $url = html_entity_decode($this->uri(['action' => 'gettags'], 'tagging'), ENT_QUOTES, 'UTF-8');
        $this->appendArrayVar('headerParams', $this->getJavaScriptFile('native-tags.js', 'tagging'));
        return '<input type="text" name="' . $escape($this->name) . '" id="' . $id
            . '" value="' . $escape($this->defaultValue) . '" data-tag-suggestions="' . $escape($url)
            . '" list="' . $id . '_suggestions" autocomplete="off">'
            . '<datalist id="' . $id . '_suggestions"></datalist>';
    }
}
