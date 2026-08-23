<?php
/**
 * Canonical toolbar menu storage.
 *
 * @category  Chisimba
 * @package   toolbar
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class dbmenu extends dbtable
{
    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorCallback'
    ) {
        parent::init('tbl_menu_category');
        $this->table = 'tbl_menu_category';
    }

    public function getModules($access = 2, $context = true)
    {
        $filter = "category NOT LIKE 'menu_%'"
            . " AND category NOT LIKE 'page_%'";
        if ($access == 2) {
            $filter .= ' AND adminonly != 1';
        }
        if (!$context) {
            $filter .= ' AND dependscontext != 1';
        }
        if (!in_array($this->table, $this->listDbTables())) {
            return false;
        }
        return $this->getArray(
            'SELECT category, module, permissions, dependscontext'
            . ' FROM ' . $this->table
            . ' WHERE ' . $filter
            . ' ORDER BY category, module'
        );
    }

    public function getFlatModules($access = 2, $context = true)
    {
        $sql = 'SELECT * FROM ' . $this->table
            . " WHERE category LIKE 'flat_%'";
        if ($access == 2) {
            $sql .= ' AND adminonly != 1';
        }
        if (!$context) {
            $sql .= ' AND dependscontext != 1';
        }
        return $this->getArray($sql . ' ORDER BY category, module');
    }

    public function getSideMenus($menu = 'user', $access = 2, $context = true)
    {
        $menu = $this->menuName($menu);
        $filter = "category LIKE 'menu_" . $menu . "%'";
        if ($access == 2) {
            $filter .= ' AND adminonly != 1';
        }
        if (!$context) {
            $filter .= ' AND dependscontext != 1';
        }
        if (!in_array($this->table, $this->listDbTables())) {
            return false;
        }
        return $this->getArray(
            'SELECT category, module, permissions, dependscontext'
            . ' FROM ' . $this->table
            . ' WHERE ' . $filter
            . ' ORDER BY category, module'
        );
    }

    public function getPageItems($page = 'lecturer', $context = true)
    {
        $page = $this->menuName($page);
        $filter = "category LIKE 'page_" . $page . "%'";
        if (!$context) {
            $filter .= ' AND dependscontext != 1';
        }
        return $this->getArray(
            'SELECT category, module, permissions, dependscontext'
            . ' FROM ' . $this->table
            . ' WHERE ' . $filter
            . ' ORDER BY category, module'
        );
    }

    public function linksForModule($module)
    {
        $module = $this->moduleName($module);
        $rows = $this->getArray(
            'SELECT * FROM ' . $this->table
            . ' WHERE module = ' . $this->quoteValue($module)
            . ' ORDER BY category, id'
        );
        return is_array($rows) ? $rows : array();
    }

    public function getLinkById($id)
    {
        $id = $this->recordId($id);
        $rows = $this->getArray(
            'SELECT * FROM ' . $this->table
            . ' WHERE id = ' . $this->quoteValue($id)
            . ' LIMIT 2'
        );
        return is_array($rows) && count($rows) === 1
            ? $rows[0]
            : null;
    }

    public function saveLinks($fields, $id = null)
    {
        if (!is_array($fields)) {
            return false;
        }
        if ($id === null || $id === '') {
            return $this->insert($fields) !== false;
        }
        return $this->update(
            'id',
            $this->recordId($id),
            $fields
        ) !== false;
    }

    public function deleteLinkById($id)
    {
        return $this->delete('id', $this->recordId($id)) !== false;
    }

    public function deleteLinksForModule($module)
    {
        return $this->delete(
            'module',
            $this->moduleName($module)
        ) !== false;
    }

    private function quoteValue($value)
    {
        return method_exists($this->_db, 'quote')
            ? $this->_db->quote($value)
            : "'" . addslashes($value) . "'";
    }

    private function moduleName($value)
    {
        $value = strtolower(trim((string) $value));
        if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $value)) {
            throw new InvalidArgumentException('Invalid module identifier');
        }
        return $value;
    }

    private function menuName($value)
    {
        $value = strtolower(trim((string) $value));
        if (!preg_match('/^[a-z][a-z0-9_]{0,31}$/', $value)) {
            throw new InvalidArgumentException('Invalid menu identifier');
        }
        return $value;
    }

    private function recordId($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $value)) {
            throw new InvalidArgumentException('Invalid toolbar record ID');
        }
        return $value;
    }
}
?>
