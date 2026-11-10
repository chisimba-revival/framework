<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');

class tagging extends controller
{
    public function dispatch($action = null)
    {
        $user = $this->getObject('user', 'security');
        if (!$user->isLoggedIn()) { http_response_code(403); return null; }
        // Never accept an owner supplied by the browser.
        $owner = (string) $user->userId();
        $tags = $this->getObject('dbtags');
        switch ($action) {
            case 'importblogs':
                // The old GET mutation targeted the paused Blog schema.
                http_response_code(410);
                return null;
            case 'gettags':
                $query = $this->getParam('tag', '');
                $rows = is_string($query) ? $tags->getSimilarTags(trim($query), $owner) : [];
                header('Content-Type: application/json; charset=UTF-8');
                header('Cache-Control: private, no-store');
                echo json_encode(array_column($rows, 'meta_value'), JSON_INVALID_UTF8_SUBSTITUTE);
                return null;
            case 'ajaxtags':
                return 'ajaxtagsdemo_tpl.php';
            default:
                $this->setVar('tags', $tags->getOwnTagCounts($owner));
                return 'sitecloud_tpl.php';
        }
    }
}
