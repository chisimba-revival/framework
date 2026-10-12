<?php
/** Native media rendering; callers remain responsible for file authorisation. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
require_once dirname(__DIR__, 2) . '/utilities/classes/richtextsanitizer_class_inc.php';
class nativeplayer extends ChisimbaObject
{
    public function audio($url) { return $this->render($url, 'audio'); }
    public function video($url, $width = null, $height = null, $unsupported = false)
    {
        return $this->render($url, 'video', $width, $height, $unsupported);
    }
    public function download($url, $unsupported = true)
    {
        return $this->render($url, null, null, null, $unsupported);
    }
    private function render($url, $kind, $width = null, $height = null, $unsupported = false)
    {
        $url = html_entity_decode(trim((string) $url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!richtextsanitizer::safeUrl($url)) return '';
        $escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $unsupported = $unsupported || in_array($extension, ['flv','swf','wmv','avi','mpg','mpeg'], true);
        $language = $this->getObject('language', 'language');
        $link = '<a href="' . $escape($url) . '">' . $escape($language->languageText('mod_files_openmedia', 'files')) . '</a>';
        if ($unsupported || $kind === null) {
            return '<p>' . ($unsupported ? $escape($language->languageText('mod_files_convertmedia', 'files')) . ' ' : '') . $link . '</p>';
        }
        $dimensions = '';
        foreach (['width' => $width, 'height' => $height] as $name => $value) {
            if (ctype_digit((string) $value) && (int) $value > 0 && (int) $value <= 8192) $dimensions .= ' ' . $name . '="' . (int) $value . '"';
        }
        return '<' . $kind . ' class="chisimba-native-media" controls preload="none"'
            . ($kind === 'video' ? ' playsinline' . $dimensions : '') . ' src="' . $escape($url) . '"></' . $kind . '><p>' . $link . '</p>';
    }
}
