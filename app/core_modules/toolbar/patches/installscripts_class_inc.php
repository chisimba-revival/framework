<?php
/** Additive placement migration through the catalogue's shared schema service. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class toolbar_installscripts extends ChisimbaObject
{
    public function preinstall($version = null)
    {
        $admin = $this->getObject('modulesadmin', 'modulecatalogue');
        $table = 'tbl_toolbar_external_links';
        $tables = $admin->listDbTables();
        if (!is_array($tables)) throw new RuntimeException('Navigation schema inspection failed');
        if (in_array('tbl_menu_category', $tables, true)) {
            $menuFields = $admin->listTblFields('tbl_menu_category');
            if (!is_array($menuFields)) throw new RuntimeException('Menu fields inspection failed');
            if (!in_array('menu_label', $menuFields, true) && $admin->executeSchemaStatement('ALTER TABLE tbl_menu_category ADD menu_label VARCHAR(150) NULL') === false) throw new RuntimeException('Menu label migration failed');
        }
        if (!in_array($table, $tables, true)) return; // Fresh installation uses the declared schema.
        $fields = $admin->listTblFields($table);
        if (!is_array($fields)) throw new RuntimeException('Navigation fields inspection failed');
        if (!in_array('placement', $fields, true)) {
            if ($admin->executeSchemaStatement("ALTER TABLE tbl_toolbar_external_links ADD placement VARCHAR(16) NOT NULL DEFAULT 'site'") === false) {
                throw new RuntimeException('Navigation placement migration failed');
            }
        }
    }
    public function postinstall($version = null)
    {
        $this->preinstall($version);
        $this->getObject('navigationcategories', 'toolbar')->seed(
            $this->getObject('navigationservice', 'toolbar')->rawEditorLinks()
        );
    }
}
