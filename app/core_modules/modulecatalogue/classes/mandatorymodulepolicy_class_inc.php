<?php
/**
 * Mandatory baseline-module policy.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Reads the installer baseline as the single source of mandatory modules.
 *
 * @category Chisimba
 * @package  modulecatalogue
 * @author   Derek Keats
 */
class mandatorymodulepolicy extends ChisimbaObject
{
    /** @var string */
    private $policyFile;

    /**
     * Resolve the authoritative installer policy file.
     *
     * @return void
     */
    public function init()
    {
        $objConfig = $this->getObject('altconfig', 'config');
        $this->policyFile = $objConfig->getSiteRootPath()
            . 'installer/dbhandlers/systemtypes.xml';
    }

    /**
     * Determine whether a module is mandatory for every installation.
     *
     * Failure to read the policy fails closed so a damaged policy cannot
     * authorize destructive removal of core modules.
     *
     * @param string $moduleId Module identifier.
     * @return bool
     */
    public function isMandatory($moduleId)
    {
        if (!is_string($moduleId) || trim($moduleId) === '') {
            return true;
        }
        if (!is_file($this->policyFile)) {
            return true;
        }
        $xml = simplexml_load_file($this->policyFile);
        if ($xml === false) {
            return true;
        }
        $categories = $xml->xpath(
            "//category[categoryname='Basic System Only']"
        );
        if (!is_array($categories) || count($categories) !== 1) {
            return true;
        }
        foreach ($categories[0]->module as $module) {
            if (trim((string) $module) === trim($moduleId)) {
                return true;
            }
        }
        return false;
    }
}
?>
