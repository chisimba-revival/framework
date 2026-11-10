<?php
// security check - must be included in all scripts
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

/**
 * Shared native tag-cloud rendering. Owning modules supply authorised tags/URLs;
 * the skin owns their appearance. Rendering never queries or widens access.
 *
 * @category Chisimba
 * @package utilities
 * @author Paul Scott <pscott@uwc.ac.za>
 * @author Derek Keats
 * @copyright AVOIR
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class tagcloud extends ChisimbaObject
{
    private $elements = array();

    public function init() { $this->clearElements(); }
    public function clearElements() { $this->elements = array(); }

    /**
     * Append a caller's tag set, retaining the historical incremental contract.
     * Use a newObject or clearElements() for an independent cloud.
     */
    public function buildCloud($tags)
    {
        foreach ($tags as $tag) {
            $this->addElement($tag['name'], $tag['url'], $tag['weight'], $tag['time'] ?? null);
        }
        return $this->buildAll();
    }

    /**
     * The timestamp argument remains accepted for existing callers. Age no
     * longer fades link colours: all tags use the active skin's readable colour.
     */
    public function addElement($tag, $uri, $weight, $time = null)
    {
        $name = (string) $tag;
        if (trim($name) === '') { return; }
        $count = is_numeric($weight) ? (float) $weight : 0.0;
        if (!is_finite($count) || $count < 0) { $count = 0.0; }
        $this->elements[] = array('name' => $name, 'url' => (string) $uri, 'weight' => $count);
    }

    /** Render an alphabetical list with five bounded, square-root weight bands. */
    public function buildAll()
    {
        if (!$this->elements) { return ''; }
        $tags = $this->elements;
        usort($tags, static function ($a, $b) { return strcmp($a['name'], $b['name']); });
        $weights = array_column($tags, 'weight');
        $minimum = sqrt(min($weights));
        $maximum = sqrt(max($weights));
        $html = '<ul class="chisimba-tag-cloud" role="list">';
        foreach ($tags as $tag) {
            $level = $maximum > $minimum
                ? 1 + (int) round(4 * (sqrt($tag['weight']) - $minimum) / ($maximum - $minimum))
                : 3;
            $label = $this->escape($tag['name']);
            $url = $this->safeUrl($tag['url']);
            $content = $url === null ? '<span>' . $label . '</span>'
                : '<a href="' . $this->escape($url) . '">' . $label . '</a>';
            $html .= '<li class="chisimba-tag-cloud__weight-' . $level . '">' . $content . '</li>';
        }
        return $html . '</ul>';
    }

    /** Historical spelling used by FAQ; delegates to the canonical method. */
    public function biuldAll() { return $this->buildAll(); }

    private function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Only web URLs and relative references may become navigation targets. */
    private function safeUrl($url)
    {
        if ($url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) { return null; }
        $parts = parse_url($url);
        if ($parts === false) { return null; }
        if (isset($parts['scheme'])) {
            if (!in_array(strtolower($parts['scheme']), array('http', 'https'), true)
                || empty($parts['host'])) { return null; }
        }
        return $url;
    }

    /** Retained demonstration entry point, using the same native renderer. */
    public function exampletags()
    {
        foreach (array(
            array('PHP', 'http://www.php.net', 39),
            array('XML', 'http://www.xml.org', 21),
            array('Perl', 'http://www.xml.org', 15),
            array('PEAR', 'http://pear.php.net', 32),
            array('MySQL', 'http://www.mysql.com', 10),
            array('PostgreSQL', 'http://pgsql.com', 6),
        ) as $tag) {
            $this->addElement($tag[0], $tag[1], $tag[2]);
        }
        return $this->buildAll();
    }
}
