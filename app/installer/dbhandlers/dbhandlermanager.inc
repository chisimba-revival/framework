<?php
/**
* Class to encapsulate management operations on [a] database(s).
* It is highly recommended that you create a derived version
* of this class for each table, rather than using it directly.
*
* @author Prince Mbekwa based on the methods from dbtablemanager_class_inc.php
* @filesource
* @copyright (c) 2000-2006, GNU/GPL AVOIR UWC
* @package Installer
* @subpackage dbhandler
* @version 0.1
* @since 14 July 2006
* @example dbdumpexample.php
*
* $Id: dbHandlermanager.inc
*/


// Installer shares the same checked schema implementation as Module Catalogue.
$applicationRoot=dirname(__DIR__,2);
set_include_path($applicationRoot.PATH_SEPARATOR.get_include_path());
$GLOBALS['kewl_entry_point_run']=true;
require_once $applicationRoot.'/classes/core/object_class_inc.php';
require_once $applicationRoot.'/classes/core/chisimbadatabaseconnection.php';
require_once $applicationRoot.'/classes/core/chisimbadatabasedsn.php';
require_once $applicationRoot.'/classes/core/dbtable_class_inc.php';
require_once $applicationRoot.'/classes/core/chisimbadatabaseschema.php';
require_once $applicationRoot.'/classes/core/dbtablemanager_class_inc.php';
class dbHandlermanager extends dbTableManager
{
    public function __construct($connection) { $this->useConnection($connection); }
}
