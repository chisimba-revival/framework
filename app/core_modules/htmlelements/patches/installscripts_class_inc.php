<?php
/** Remove only settings for the retired ExtJS theme selector. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class htmlelements_installscripts extends dbTable
{
    public function postinstall($version = null)
    {
        $this->executePrepared('DELETE FROM tbl_sysconfig_properties WHERE LOWER(pname) = ? AND pmodule IN (?, ?)', ['extjs_theme','htmlelements','ext']);
    }
}
