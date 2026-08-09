<?php
/** Canonical data access for tbl_cpd_categories. @author Derek Keats @package cpd */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class cpdcategories extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_cpd_categories', $pearDb, $errorCallback);
    }
}
?>
