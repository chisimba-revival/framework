<?php
/** Native translation lookup over Chisimba's language store.
 * @author Derek Keats <derek@dkeats.com>
 */
final class ChisimbaTranslation
{
    private $store;
    private $language = 'en';
    private $page = 'system';
    private $pages = [];
    private $revision = null;

    public function __construct($store) { $this->store = $store; }
    public function setLang($language) { $this->language = $this->normalise($language); return true; }
    public function setPageID($page = null) { $this->page = $page; return $this; }
    private function normalise($language)
    {
        if (!is_string($language) || !preg_match('/^[a-z]{2,3}(?:_[a-z0-9]{2,8})*$/iD', $language)) { return 'en'; }
        $language = strtolower($language);
        return $language === 'en' || isset($this->store->languages()[$language]) ? $language : 'en';
    }
    private function page($language, $page)
    {
        if ($this->revision !== $this->store->revision()) {
            $this->pages = []; $this->revision = $this->store->revision();
        }
        $key = json_encode([$language, $page], JSON_THROW_ON_ERROR);
        if (!array_key_exists($key, $this->pages)) { $this->pages[$key] = $this->store->page($language, $page); }
        return $this->pages[$key];
    }
    /** Retain the historical HTML-entity boundary, without lossy Latin-1 decoding. */
    public function get($id, $page = 'translation2_default_pageID', $language = null, $default = '')
    {
        $page = $page === 'translation2_default_pageID' ? $this->page : $page;
        $language = $language === null ? $this->language : $this->normalise($language);
        $value = $this->page($language, $page)[$id] ?? null;
        if (($value === null || $value === '') && $language !== 'en') { $value = $this->page('en', $page)[$id] ?? null; }
        if ($value === null || $value === '') { return $default !== '' && $default !== null && $default !== false ? $default : $id; }
        return htmlentities((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    public function getLangs($format = 'name')
    {
        $rows = $this->store->languages(); $result = [];
        foreach ($rows as $id => $row) {
            if ($format === 'array') { $result[$id] = $row + ['lang_id' => $id]; }
            elseif ($format === 'id' || $format === 'ids') { $result[] = $id; }
            elseif ($format === 'encoding' || $format === 'encodings') { $result[] = $row['encoding'] ?? null; }
            else { $result[$id] = $row['name']; }
        }
        return $result;
    }
    public function getLang($language = null, $format = 'name')
    {
        $id = $language === null ? $this->language : $this->normalise($language);
        $row = $this->store->languages()[$id] ?? ['id'=>'en','name'=>'English','encoding'=>'UTF-8'];
        return $format === 'array' ? $row : ($row[$format] ?? $row['name']);
    }
}
