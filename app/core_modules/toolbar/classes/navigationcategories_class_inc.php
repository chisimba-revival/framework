<?php
/** Independent site menu headings and ordering. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class navigationcategories extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_toolbar_categories', $pearDb, $errorCallback);
    }

    public function categories()
    {
        return $this->selectExactPrepared('SELECT * FROM tbl_toolbar_categories ORDER BY position,sort_module,sort_id,group_key');
    }

    public function category($id)
    {
        return $this->selectExactPrepared('SELECT * FROM tbl_toolbar_categories WHERE id=?', [$id])[0] ?? null;
    }

    /** All heading/link mutations share this lock, including empty-category deletion. */
    public function mutate(callable $operation)
    {
        return $this->withDatabaseLock('toolbar-navigation', $operation);
    }

    /** Catalogue-only migration. Existing headings are never re-positioned by children. */
    public function seed(array $links)
    {
        return $this->mutate(function () use ($links) {
            $known = array_column($this->categories(), null, 'group_key');
            usort($links, static fn($a, $b) => self::linkKey($a) <=> self::linkKey($b));
            foreach ($links as $link) {
                $key = $link['group'];
                if (($link['placement'] ?? 'site') !== 'site' || $key === '' || isset($known[$key])) continue;
                $sort = self::linkKey($link);
                $this->executeExactPrepared('INSERT INTO tbl_toolbar_categories (id,group_key,label,position,sort_module,sort_id) VALUES (?,?,?,?,?,?)',
                    [bin2hex(random_bytes(16)), $key, $link['groupLabel'], $sort[0], $sort[1], $sort[2]]);
                $known[$key] = true;
            }
        });
    }

    public static function linkKey(array $link)
    {
        return [(int)$link['order'], !empty($link['external']) ? '' : $link['module'], $link['id']];
    }

    /** Preserve legacy tie order while making the root order independent of child order. */
    public function arrange(array $links)
    {
        $categories = array_column($this->categories(), null, 'group_key');
        $root = static function ($link) use ($categories) {
            if ($link['group'] === '') return self::linkKey($link);
            $category = $categories[$link['group']] ?? null;
            // Unregistered module headings sort deterministically last until catalogue registration.
            return $category ? [(int)$category['position'], $category['sort_module'], $category['sort_id']]
                : [100000, '', $link['group']];
        };
        foreach ($links as &$link) {
            if (isset($categories[$link['group']])) $link['groupLabel'] = $categories[$link['group']]['label'];
        }
        unset($link);
        usort($links, static fn($a, $b) => [$root($a), $a['group'], self::linkKey($a)] <=> [$root($b), $b['group'], self::linkKey($b)]);
        return $links;
    }

    public function saveCategory(array $input, $id = null)
    {
        return $this->mutate(function () use ($input, $id) {
            $label = $input['label'] ?? '';
            $position = $input['position'] ?? '';
            if (!is_string($label) || !is_scalar($position)) throw new InvalidArgumentException('category_invalid');
            $label = trim($label);
            $rows = $this->categories();
            $existing = $id === null ? null : $this->category($id);
            if ($id !== null && !$existing) throw new RuntimeException('Navigation category not found');
            if ($label === '' || mb_strlen($label) > 150 || preg_match('/[\x00-\x1f\x7f]/', $label)) throw new InvalidArgumentException('category_invalid');
            foreach ($rows as $row) {
                if ($row['id'] !== $id && mb_strtolower($row['label']) === mb_strtolower($label)) throw new InvalidArgumentException('category_duplicate');
            }
            if ($position === '') {
                $positions = array_column($rows, 'position');
                foreach ($this->getObject('navigationservice', 'toolbar')->rawEditorLinks() as $link) {
                    if (($link['placement'] ?? 'site') === 'site' && $link['group'] === '') $positions[] = $link['order'];
                }
                $position = $positions ? max($positions) + 1 : 10;
            }
            $position = filter_var($position, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>999]]);
            if ($position === false) throw new InvalidArgumentException('category_invalid');
            if ($existing) {
                $this->executeExactPrepared('UPDATE tbl_toolbar_categories SET label=?,position=? WHERE id=?', [$label,$position,$id]);
                return $existing['group_key'];
            }
            $id = bin2hex(random_bytes(16));
            $key = 'nav_'.substr($id, 0, 16);
            $this->executeExactPrepared('INSERT INTO tbl_toolbar_categories (id,group_key,label,position,sort_module,sort_id) VALUES (?,?,?,?,?,?)', [$id,$key,$label,$position,'',$id]);
            return $key;
        });
    }

    public function removeCategory($id)
    {
        return $this->mutate(function () use ($id) {
            $category = $this->category($id);
            if (!$category) throw new RuntimeException('Navigation category not found');
            foreach ($this->getObject('navigationservice', 'toolbar')->rawEditorLinks() as $link) {
                if (($link['placement'] ?? 'site') === 'site' && $link['group'] === $category['group_key']) throw new InvalidArgumentException('category_in_use');
            }
            $this->executeExactPrepared('DELETE FROM tbl_toolbar_categories WHERE id=?', [$id]);
        });
    }
}
