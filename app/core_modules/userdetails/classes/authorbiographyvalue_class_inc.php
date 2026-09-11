<?php
/** Validates the public biography independently of storage and presentation. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class authorbiographyvalue
{
    /** Return normalised data and field errors; never silently truncate submitted content. */
    public static function validate(array $input)
    {
        $bio = is_string($input['biography'] ?? null) ? trim($input['biography']) : '';
        $errors = array();
        if ($bio === '' || mb_strlen($bio) > 6000) $errors['biography'] = 'bio_invalid';
        $links = array();
        $rows = $input['links'] ?? array();
        if (!is_array($rows) || count($rows) > 5) {
            $errors['links'] = 'links_invalid';
            $rows = array();
        }
        foreach ($rows as $row) {
            if (!is_array($row)) { $errors['links'] = 'links_invalid'; continue; }
            $label = is_string($row['label'] ?? null) ? trim($row['label']) : '';
            $url = is_string($row['url'] ?? null) ? trim($row['url']) : '';
            if ($label === '' && $url === '') continue;
            if ($label === '' || mb_strlen($label) > 80 || strlen($url) > 2048 || !self::safeUrl($url)) {
                $errors['links'] = 'links_invalid';
                continue;
            }
            $links[] = array('label' => $label, 'url' => $url);
        }
        return array('value' => array('biography' => $bio, 'links' => $links), 'errors' => $errors);
    }

    /** External destinations are links only; credentials and executable schemes are rejected. */
    public static function safeUrl($url)
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) return false;
        $parts = parse_url($url);
        return strtolower($parts['scheme'] ?? '') === 'https'
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
    }
}
