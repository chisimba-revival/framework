<?php
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

 /**
 * Class for reading and writing arrays in XML
 * Native array/scalar XML metadata wrapper using PHP DOM.
 * Object deserialisation and DTDs are deliberately unsupported.
 *
 
 * @category  Chisimba
 * @package   utilities
 * @author James Scoble
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General
Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

require_once __DIR__.'/nativexmlvalue.php';

class xmlserial extends ChisimbaObject
{
    /**
    * Basic Object init function
    */
    function init()
    {
    }

    /**
    * Method to read in XML, and return an array
    * using the native array/scalar metadata contract
    * @param string $file the XML to read
    * @param bool $type true for a local filename, false for XML text
    * @return mixed array or scalar metadata
    */
    function readXML($file,$type=TRUE)
    {
        if ($type) {
            if (!is_string($file) || str_contains($file, '://') || !is_file($file) || !is_readable($file) || filesize($file) > 8388608) {
                throw new InvalidArgumentException('Readable local XML metadata file required.');
            }
            $file = file_get_contents($file, false, null, 0, 8388609);
            if ($file === false) throw new RuntimeException('Cannot read XML metadata.');
        }
        return ChisimbaXmlValue::read($file);
    }

    /**
    * Method to export an array as XML
    * using the native array/scalar metadata contract
    * @param array $data
    * @return string XML metadata
    */
    function writeXML($data)
    {
        return ChisimbaXmlValue::write($data);
    }

}
?>