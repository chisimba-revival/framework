<?php
/**
 * Retirement response for the historical remote module distribution service.
 *
 * Module installation now uses the maintained deployment and catalogue paths.
 * Keep this route explicit so old clients cannot trigger downloads or extraction.
 *
 * @author Paul Scott <pscott@uwc.ac.za>
 * @author Prince Mbekwa <pmbekwa@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license GPL
 * @package packages
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class packages extends controller
{
    public function init()
    {
        // No remote client, server or archive handler is loaded.
    }

    public function dispatch($action = null)
    {
        http_response_code(410);
        $this->setPageTemplate(null);
        $this->setLayoutTemplate(null);
        $this->setVar('pageSuppressToolbar', true);
        return 'service_retired_tpl.php';
    }

    public function requiresLogin($action = null)
    {
        return false;
    }
}
