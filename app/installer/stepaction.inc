<?php
/**
* StepAction
*
* A single action in a step
*
* @author prince mbekwa
* @version $version$ - 1.0
* @package 5ive
* @subpackage installer
*/
class StepAction
{
	/**
	* The name of the parent step of this action.
	* @var string
	*/
	var $parent_step = '';
	
	/**
	* Store whether the action completed successfully. By default all actions are 'true'
	* @var boolean
	*/
	var $success = true;
	
	/**
	* The errors this object has collected
	* @var array
	*/
	var $errors = Array();
	
	/**
	* The number of steps to skip on completion of this action (if any). BE 
	* VERY CAREFUL WITH THIS. If there is more than one action in a step, 
	* this could have really really strange consecquences for the other steps.
	* @var int
	*/
	var $skip_steps = 0;
	
	/**
	* Defines a step to jump to after processing, normally left blank
	* @var string
	*/
	var $next_step = '';
	
	/**
	* Is this action required (can it be skipped?)
	* @var boolean
	*/
	var $required = true;
	
	
	/**
	* Save the name of the parent step if given
	*
	* @param string $step_name The name of the parent step
	* @access public
	*/
				/**
		 * PHP 8 constructor wrapper for the legacy StepAction() constructor.
		 */
		public function __construct()
		{
		    call_user_func_array(
		        [$this, 'StepAction'],
		        func_get_args()
		    );
		}

function StepAction($step_name='')
	{
		$this->parent_step = $step_name;

	}//end StepAction
	
	
	/**
	* Perform this step action
	* 
	* @return boolean Did the action complete successfully?
	* @access public
	*/
	function processAction()
	{
		return $this->success;
	}//end performAction()
	
	
	/**
	* Paint the step action
	* 
	* @return void
	* @access public
	*/
	function paintAction()
	{
	}//end paintAction()
	
	
	/**
	* If a step requires confirmation, paint out the confirm screen
	* 
	* @return void
	* @access public
	*/
	function confirmAction()
	{
	}//end confirmAction()
	
	
	/**
	* Rollback whatever this action did in processAction
	* 
	* @return boolean Whether rollback completed successfully or not.
	* @access public
	*/
	function rollback()
	{
		return true;
	}//end rollback()
	
	
	/**
	* Return whether this action is required or not
	* 
	* @return boolean if this action is required to complete the step.
	* @access public
	*/
	function isRequired()
	{
		return $this->required;
	}//end isRequired()
	
	

    /**
     * Build the canonical public site root selected by the installer.
     *
     * CHISIMBA_INSTALLER_CANONICAL_SITE_ROOT
     *
     * The server field may contain an explicit http:// or https:// scheme.
     * When it does not, preserve the current request scheme, including the
     * standard reverse-proxy X-Forwarded-Proto header.
     *
     * @param string $serverUrl Host[:port], or an absolute HTTP(S) origin.
     * @param string $sitePath  Installation path below the origin.
     *
     * @return string|false Canonical absolute site root with trailing slash.
     */
    protected function buildCanonicalSiteRoot($serverUrl, $sitePath)
    {
        $serverUrl = trim((string) $serverUrl);
        $sitePath = trim((string) $sitePath);

        if ($serverUrl === '') {
            $serverUrl = isset($_SERVER['HTTP_HOST'])
                ? trim((string) $_SERVER['HTTP_HOST'])
                : '';
        }

        if (!preg_match('#^https?://#i', $serverUrl)) {
            $serverUrl = $this->getInstallerRequestScheme()
                . '://'
                . ltrim($serverUrl, '/');
        }

        $parts = parse_url($serverUrl);
        if (
            !is_array($parts)
            || !isset($parts['scheme'])
            || !isset($parts['host'])
            || !in_array(
                strtolower($parts['scheme']),
                array('http', 'https'),
                true
            )
        ) {
            return false;
        }

        $origin = strtolower($parts['scheme']) . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $origin .= ':' . (int) $parts['port'];
        }

        $serverPath = isset($parts['path'])
            ? trim((string) $parts['path'], '/')
            : '';
        $sitePath = trim($sitePath, '/');

        $paths = array();
        if ($serverPath !== '') {
            $paths[] = $serverPath;
        }
        if ($sitePath !== '') {
            $paths[] = $sitePath;
        }

        return $origin
            . (empty($paths) ? '/' : '/' . implode('/', $paths) . '/');
    }

    /**
     * Resolve the public request scheme used by the installer.
     *
     * @return string
     */
    protected function getInstallerRequestScheme()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $forwarded = strtolower(
                trim(
                    explode(
                        ',',
                        (string) $_SERVER['HTTP_X_FORWARDED_PROTO']
                    )[0]
                )
            );
            if ($forwarded === 'http' || $forwarded === 'https') {
                return $forwarded;
            }
        }

        if (
            isset($_SERVER['HTTPS'])
            && strtolower((string) $_SERVER['HTTPS']) !== 'off'
            && (string) $_SERVER['HTTPS'] !== ''
        ) {
            return 'https';
        }

        if (
            isset($_SERVER['REQUEST_SCHEME'])
            && in_array(
                strtolower((string) $_SERVER['REQUEST_SCHEME']),
                array('http', 'https'),
                true
            )
        ) {
            return strtolower((string) $_SERVER['REQUEST_SCHEME']);
        }

        return 'http';
    }


}//end class

?>