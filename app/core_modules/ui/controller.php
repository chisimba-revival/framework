<?php
/**
 * ui controller
 *
 * Provides a minimal demonstration endpoint for the Chisimba native UI
 * component layer.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   ui
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   GNU GPL version 2 or later
 */

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * UI module controller.
 *
 * @category Chisimba
 * @package  ui
 * @author   Derek Keats
 */
class ui extends Controller
{
    /**
     * Initialise the controller.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Dispatch the requested action.
     *
     * @return string
     */
    public function dispatch()
    {
        $action = $this->getParam('action', 'demo');

        switch ($action) {
            case 'demo':
            default:
                return 'demo_tpl.php';
        }
    }
}
