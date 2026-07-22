<?php
/**
 * uiservice_class_inc.php
 *
 * Shared service for Chisimba-native UI assets.
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
 * Shared UI asset service.
 *
 * The service is deliberately small. It loads only the common native UI
 * stylesheet and script, and prevents duplicate inclusion during a request.
 *
 * @category Chisimba
 * @package  ui
 * @author   Derek Keats
 */
class uiservice extends ChisimbaObject
{
    /**
     * Whether the common UI assets have already been added.
     *
     * @var bool
     */
    protected static $loaded = false;

    /**
     * Initialise the service.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Load the common native UI assets once.
     *
     * @return void
     */
    public function show()
    {
        if (self::$loaded) {
            return;
        }

        $css = '<link rel="stylesheet" href="'
            . $this->getResourceUri('css/ui.css', 'ui')
            . '" type="text/css" />';

        $js = '<script src="'
            . $this->getResourceUri('js/ui.js', 'ui')
            . '" type="text/javascript" defer></script>';

        $this->appendArrayVar('headerParams', $css);
        $this->appendArrayVar('headerParams', $js);

        self::$loaded = true;
    }
}
