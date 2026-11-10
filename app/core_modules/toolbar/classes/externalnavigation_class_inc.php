<?php
/** Configured external links, independent of module defaults. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');

class externalnavigation extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_toolbar_external_links', $pearDb, $errorCallback);
    }

    /** Only explicit web destinations, without embedded credentials or control bytes. */
    public static function validUrl($url)
    {
        if (!is_string($url) || strlen($url) > 2048
            || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            || preg_match('/%(?:0[0-9a-f]|1[0-9a-f]|7f)/i', $url)) {
            return false;
        }
        $parts = parse_url($url);
        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass'])
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function links()
    {
        return $this->selectExactPrepared('SELECT * FROM tbl_toolbar_external_links ORDER BY position,label,id');
    }

    /** Learning entries never appear to anonymous visitors or in another menu. */
    public function learningLinks($authenticated)
    {
        if (!$authenticated) return [];
        return array_values(array_filter($this->links(), static fn($row) =>
            ($row['placement'] ?? 'site') === 'learning'
            && $row['audience'] === 'members' && self::validUrl($row['url'])));
    }

    public function link($id)
    {
        $rows = $this->selectExactPrepared('SELECT * FROM tbl_toolbar_external_links WHERE id=?', [$id]);
        return $rows[0] ?? null;
    }

    public function saveLink(array $input, $id = null)
    {
        return $this->getObject('navigationcategories', 'toolbar')->mutate(fn() => $this->writeLink($input, $id));
    }

    private function writeLink(array $input, $id)
    {
        $groups = $this->getObject('navigationservice', 'toolbar')->editorGroups();
        foreach (['placement', 'label', 'url', 'group_key', 'new_group', 'position', 'audience'] as $key) {
            if (isset($input[$key]) && !is_scalar($input[$key])) {
                throw new InvalidArgumentException('invalid');
            }
        }
        $placement = $input['placement'] ?? 'site';
        if (!in_array($placement, ['site', 'learning'], true)) throw new InvalidArgumentException('invalid');
        $label = trim((string) ($input['label'] ?? ''));
        $url = trim((string) ($input['url'] ?? ''));
        $group = trim((string) ($input['group_key'] ?? ''));
        $newGroup = trim((string) ($input['new_group'] ?? ''));
        $position = $input['position'] ?? '';
        $audience = $input['audience'] ?? 'everyone';
        if ($label === '' || mb_strlen($label) > 150 || !self::validUrl($url)
            || !in_array($audience, ['everyone', 'visitors', 'members'], true)
            || mb_strlen($newGroup) > 150 || preg_match('/[\x00-\x1f\x7f]/', $label.$newGroup)) {
            throw new InvalidArgumentException('invalid');
        }

        if ($placement === 'learning') {
            $group = '';
            $newGroup = '';
            $audience = 'members';
        }
        if ($newGroup !== '') throw new InvalidArgumentException('invalid');
        if ($group !== '' && !isset($groups[$group])) throw new InvalidArgumentException('invalid');
        // Retained only for rollback to pre-category releases; never used as the heading authority.
        $groupLabel = $groups[$group] ?? '';
        $position = $this->getObject('navigationservice', 'toolbar')->linkPosition($position, $placement, $group, $id);
        if (mb_strlen($groupLabel) > 150 || strlen($group) > 100) {
            throw new InvalidArgumentException('invalid');
        }
        $fields = ['label' => $label, 'url' => $url, 'group_key' => $group,
            'group_label' => $groupLabel, 'position' => $position, 'audience' => $audience, 'placement' => $placement];
        if ($id !== null && !$this->link($id)) throw new RuntimeException('External link not found');
        // Exact bindings retain the empty strings used for an ungrouped link.
        // Legacy CRUD intentionally converts empty strings to NULL.
        if ($id === null) {
            $this->executeExactPrepared(
                'INSERT INTO tbl_toolbar_external_links (id,label,url,group_key,group_label,position,audience,placement) VALUES (?,?,?,?,?,?,?,?)',
                [bin2hex(random_bytes(16)), ...array_values($fields)]
            );
        } else {
            $this->executeExactPrepared(
                'UPDATE tbl_toolbar_external_links SET label=?,url=?,group_key=?,group_label=?,position=?,audience=?,placement=? WHERE id=?',
                [...array_values($fields), $id]
            );
        }
    }

    public function removeLink($id)
    {
        if (!$this->link($id)) throw new RuntimeException('External link not found');
        if ($this->delete('id', $id) === false) throw new RuntimeException('External link deletion failed');
    }
}
