<?php


require_once INSTALL_DIR.'/stepaction.inc';
//require_once 'MDB2/Schema.php';

/**
* 5iveStepAction
*
* contains library style functions that 5ive installer actions use.
*
* @author prince mbekwa
* @version $version$ - 1.0
* @package 5ive
* @subpackage installer
*/
class FiveStepAction extends StepAction
{


	/**
	* Test whether a DSN can be connected to
	*
	* @param string $dsn the dsn to test connection to.
	* @return boolean whether the dsn is accessible.
	* @access private
	*/
    function _testDSN($dsn)
    {
        require_once __DIR__.'/dbhandlers/dbhandlermanager.inc';
        try {
            $parsed = ChisimbaDatabaseDsn::parse($dsn);
            $parsed['database'] = 'information_schema';
            $connection = ChisimbaDatabaseConnection::fromLegacyDsn($parsed);
            $connection->query('SELECT 1');
            return true;
        } catch (Throwable $error) {
            $this->errors[] = 'Cannot connect to MySQL using PDO. Check the host, credentials and pdo_mysql extension.';
            return false;
        }
    }


	/**
	* Test if a directory is writable or not
	*
	* @param string $check_dir the directory to test
	* @return boolean if the directory is writable or not
	* @access protected
	*/
	function _dirIsWritable($check_dir)
	{
		$success = true;
		// create a tmp file to see if there's write access.
		$fp = @fopen($check_dir.'/tmpinstallfile', "w");
		if ($fp === FALSE) {
			$success = false;
			$this->errors[] = 'Could not open '.$check_dir.' for writing. ';
			$this->errors[] = 'Please change permissions on the '.$check_dir.' directory '.
								'to have write access by the webserver by executing the following ';
			$this->errors[] = '<pre>chmod 700 '.$check_dir.'</pre>';
			@fclose($fp);
		} else {
			@fclose($fp);
			// delete the temp file that was just created
			unlink($check_dir.'/tmpinstallfile');
		}
		return $success;

	}//end _dirIsWritable()


}//end class

?>