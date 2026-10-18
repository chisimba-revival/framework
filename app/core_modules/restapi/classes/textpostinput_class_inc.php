<?php
/** Plain-text request translation for the initial mobile publishing contract.
 * @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class TextPostInput
{
    /** Keep ownership, media, rich text and scope out of the client contract. */
    public static function normalise(array $data, string $contentType, bool $hasFiles): array
    {
        if (strtolower(trim(explode(';', $contentType, 2)[0])) !== 'application/json'
            || $hasFiles || array_diff(array_keys($data), ['title', 'content', 'status'])) {
            throw new DomainException('invalid');
        }
        foreach (['title', 'content'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])
                || !mb_check_encoding($data[$field], 'UTF-8')
                || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $data[$field])) {
                throw new DomainException('invalid');
            }
        }
        $title = trim($data['title']);
        $text = trim(str_replace(["\r\n", "\r"], "\n", $data['content']));
        $status = array_key_exists('status', $data) ? $data['status'] : 'draft';
        if ($title === '' || mb_strlen($title) > 250 || $text === ''
            || strlen($text) > 50000 || !in_array($status, ['draft', 'posted'], true)) {
            throw new DomainException('invalid');
        }
        // Escape before entering SimpleBlog's HTML boundary. Newlines remain
        // visible; markup, image tags and embeds can never become active content.
        $html = '<p>' . nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) . '</p>';
        return ['title' => $title, 'content' => $html, 'tags' => '', 'status' => $status];
    }
}
