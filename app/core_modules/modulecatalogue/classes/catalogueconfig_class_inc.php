<?php
/**
 * Class for building and reading the xml catalogue used by modulecatalogue.
 * It uses the shared native configuration document boundary
 *
 * This class will provide the catalogue configuration for module registration
 *
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Prince Mbekwa <pmbekwa@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

// security check - must be included in all scripts
if (!
/**
 * Description for $GLOBALS
 * @global unknown $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run']){
    die("You cannot view this page directly");
}

/**
 * Native configuration adapter for module discovery
 *
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Prince Mbekwa <pmbekwa@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

//grab the pear::Config properties
// include class
require_once dirname(__DIR__, 2) . '/config/classes/configurationdocument.php';

class catalogueconfig extends ChisimbaObject {


    /**
     * The path of the files to be read or written
     * @access public
     * @var    string
     */
    public $_path = null;
    /**
     * The root object for configs read
     *
     * @access private
     * @var    string
    */
    protected $_root;
    /**
     * The root object for properties read
     *
     * @access private
     * @var    string
    */
    protected $_property;
    /**
     * The options value for altconfig read / write
     *
     * @access private
     * @var    string
    */
    protected $_options;

    /**
     * The catalogueconfig object for catalogueconfig storage
     *
     * @access private
     * @var    array
     */
    protected $_catalogueconfigVars;


    /**
     * The site configuration object
     *
     * @var object $config
     */
    public $config;


    /**
    * Method to construct the class.
    */
    public function init()
    {
        // instantiate object
        try{
            $this->objConfig = $this->getObject('altconfig','config');
            $this->objLanguage = $this->getObject('language','language');
        }catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * Method to parse catalogue lists.
     * For use when reading configuration options
     *
     * @access protected
     * @param  string    $config   xml file or PHPArray to parse
     * @param  string    $property used to set property value of incoming config string
     *                             $property can either be:
     *                             1. PHPArray
     *                             2. XML
     * @return boolean   True/False result.
     *
     */
    protected function readCatalogue($property)
    {
        if (strtoupper($property) !== 'XML') { throw new RuntimeException('Unsupported catalogue format.'); }
        $document = new ChisimbaConfigurationDocument($this->cataloguePath(), 'settings');
        return $this->_root = $document->root;
    }

    private function cataloguePath()
    {
        return rtrim($this->objConfig->getsiteRootPath(), '/') . '/config/catalogue.xml';
    }

    /** Preserve SimpleXML return values for existing catalogue consumers. */
    private function catalogueXml()
    {
        $root = $this->readCatalogue('XML');
        $xml = new ChisimbaConfigurationXml();
        return simplexml_load_string($xml->render($root, 'settings', 'UTF-8'), 'SimpleXMLElement', LIBXML_NONET);
    }
    /**
     * Method to wirte catalogue options.
     * For use when writing catalogue options
     *
     * @access public
     * @param  string  values   to be saved
     * @param  string  property used to set property value of incoming catalogue string
     * @return boolean TRUE for success / FALSE fail .
     *
     */
    public function writeCatalogue()
    {
        $files = new ChisimbaConfigurationFile();
        return $files->synchronise($this->cataloguePath() . '.refresh', function () {
            // Capture the revision before discovery; never publish an incomplete scan.
            $document = new ChisimbaConfigurationDocument($this->cataloguePath(), 'settings', true);
            $source = $this->getObject('modulefile', 'modulecatalogue');
            $modules = $source->getLocalModuleList();
            if (!is_array($modules) || !$modules) { throw new RuntimeException('Catalogue discovery was incomplete.'); }
            $rows = array(); $ids = array();
            foreach ($modules as $module) {
                $registerFile = $source->findregisterfile($module);
                $reg = $registerFile ? $source->readRegisterFile($registerFile) : false;
                if (!is_array($reg) || empty($reg['MODULE_ID']) || !is_string($reg['MODULE_ID'])
                    || !preg_match('/^[A-Za-z0-9_-]+$/D', $reg['MODULE_ID']) || isset($ids[$reg['MODULE_ID']])) {
                    throw new RuntimeException('Catalogue discovery was incomplete or ambiguous.');
                }
                $ids[$reg['MODULE_ID']] = true;
                $row = array('id'=>(string)(count($rows)+1), 'module_id'=>$reg['MODULE_ID'],
                    'module_name'=>$reg['MODULE_NAME'] ?? $reg['MODULE_ID'],
                    'module_icon'=>$reg['MODULE_ICON'] ?? 'puzzle',
                    'module_authors'=>$reg['MODULE_AUTHORS'] ?? '',
                    'module_releasedate'=>$reg['MODULE_RELEASEDATE'] ?? '',
                    'module_description'=>$reg['MODULE_DESCRIPTION'] ?? '',
                    'module_version'=>$reg['MODULE_VERSION'] ?? '',
                    'module_tags'=>isset($reg['TAGS']) ? implode(', ', (array)$reg['TAGS']) . ', ' : '',
                    'module_dependency'=>isset($reg['DEPENDS']) ? implode(', ', (array)$reg['DEPENDS']) . ', ' : '',
                    'module_status'=>$reg['MODULE_STATUS'] ?? 'pre-alpha');
                if (!empty($reg['MODULE_CATEGORY'])) { $row['module_category']=array_values((array)$reg['MODULE_CATEGORY']); }
                $rows[]=$row;
            }
            $candidate=$document->fromArray(array(
                '@'=>array('xsi:noNamespaceSchemaLocation'=>'catalogue.xsd'),
                'module'=>$rows, 'engine_version'=>$this->objEngine->version));
            $document->save($candidate);
            $this->_root=$document->root;
            // The refresh lock includes reconciliation so an older scan cannot
            // reconcile after a newer refresh. Database failures propagate.
            $removed=$this->getObject('modules','modulecatalogue')->reconcileAvailableModules(array_keys($ids));
            return array('discovered'=>count($ids),'removed'=>$removed,'reconciled'=>true);
        });
    }

    /**
    * Method to get modulelist for catalogue categories.
    *
    * @var    string $pname The name of the parameter being set
    * @return $value
    */
    public function getModulelist($pname)
    {
        try {

            $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";

            $xml = $this->catalogueXml();
            if($pname !="all"){
                $query = "//module[module_category=" . $this->xpathLiteral($pname) . "]";
            }else{
                $query = "//module";

            }
            $entries = $xml->xpath($query);


            foreach ($entries as $module) {
                $moduleName = $this->objLanguage->abstractText((string)$module->module_name);
                if (empty($moduleName)) {
                    $result[(string)$module->module_id] = ucwords((string)$module->module_id);
                } else {
                    $result[(string)$module->module_id] = ucwords($moduleName);
                }
            }
            if (!isset($result)) {
                return FALSE;
            }else {
                return $result;
            }

        }catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * Method to get basic module data for all modules.
    *
    * @return array of key module_id with values being the module name and description
    */
    public function getModuleDetails()
    {
        try {

            $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";

            $xml = $this->catalogueXml();
            $entries = $xml->xpath("//module");

            foreach ($entries as $module) {
                $moduleDesc = $this->objLanguage->abstractText((string)$module->module_description);
                $moduleName = $this->objLanguage->abstractText((string)$module->module_name);
                $moduleVer  = (string)$module->module_version;
                $moduleStatus = (string)$module->module_status;
                if (empty($moduleName)) {
                    $result[] = array((string)$module->module_id,ucfirst((string)$module->module_id),ucfirst((string)$module->module_id), $module->module_status);
                } else {
                    $result[] = array((string)$module->module_id,ucwords($moduleName),ucfirst($moduleDesc),$moduleVer, $moduleStatus);
                }
            }
            if (!isset($result)) {
                return FALSE;
            }else {
                return $result;
            }

        }catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }


    /**
    * Method to get basic module tags for all modules.
    *
    * @return array of key module_tasg with values being the module tags
    */
    public function getModuleTags()
    {
        try {
            $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";
            $xml = $this->catalogueXml();
            $entries = $xml->xpath("//module");
            foreach ($entries as $moduletags) {
                $moduleName = $this->objLanguage->abstractText((string)$moduletags->module_name);
                $moduleTag = $this->objLanguage->abstractText((string)$moduletags->module_tags);
                if (empty($moduleName)) {
                    $result[] = array('id' => (string)$moduletags->module_id,
                    'tags' => (string)$moduletags->module_tags,
                    'name' => (string)$moduletags->module_name
                    );
                } else {
                    $result[] = array('id' => (string)$moduletags->module_id,'name' => ucwords($moduleName),'tags' => (string)$moduleTag);
                }
            }
            if (!isset($result)) {
                return FALSE;
            }else {
                return $result;
            }

        }catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * Method to get basic module dependencies.
    *
    * @return array of key module_deps with values being the module deps
    */
    public function getModuleDeps($module)
    {
        $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";
        $xml = $this->catalogueXml();
        $entries = $xml->xpath("//module[module_id=" . $this->xpathLiteral($module) . "]");
        //log_debug($entries[0]->module_dependency);
        return $entries[0]->module_dependency ?? false;
    }

    /**
    * Method to get module status.
    *
    * @return array of key module_status
    */
    public function getModuleStatus($module)
    {
        $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";
        $xml = $this->catalogueXml();
        $status = $xml->xpath("//module[module_id=" . $this->xpathLiteral($module) . "]");
        //var_dump($status[0]->module_status);
        return $status[0]->module_status ?? false;
    }

    /**
    * Method to get modulelist for catalogue categories.
    *
    * @var    string $pname The name of the parameter being set
    * @var    string $type either search module_id,description or both
    * @return $value
    */
    public function searchModulelist($str,$type)
    {
        try {
            $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";
            //echo "$str $type<br/>";
            $str = strtolower($str);
            $xml = $this->catalogueXml();
            switch ($type) {
                case 'name':
                    $query = "//module[contains(translate(module_id, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')," . $this->xpathLiteral($str) . ") or contains(translate(module_name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')," . $this->xpathLiteral($str) . ")]";
                    break;
                case 'description':
                    $query = "//module[contains(translate(module_description, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')," . $this->xpathLiteral($str) . ")]";
                    break;
                case 'tags':
                    $query = "//module[contains(translate(module_tags, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), " . $this->xpathLiteral($str) . ")]";
                    break;
                default:
                    $query = "//module[contains(translate(module_id, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')," . $this->xpathLiteral($str) . ") or contains(translate(module_description, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')," . $this->xpathLiteral($str) . ") or contains(translate(module_name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')," . $this->xpathLiteral($str) . ")]";
                    break;
            }
            $entries = $xml->xpath($query);

            foreach ($entries as $module) {
                $moduleName = $this->objLanguage->abstractText((string)$module->module_name);
                if (empty($moduleName)) {
                    $result[(string)$module->module_id] = ucwords((string)$module->module_id);
                } else {
                    $result[(string)$module->module_id] = ucwords($moduleName);
                }
            }
            if (!isset($result)) {
                return FALSE;
            }else {
                return $result;
            }

        } catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }


    /**
     * Method to get module description from the catalogue
     *
     * @author Nic Appleby
     * @param  string $modname module name
     * @return string module description|FALSE if none exists
     */
    public function getModuleDescription($modname) {
        try {
            $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";
            $xml = $this->catalogueXml();
            $query = "//module[module_id=" . $this->xpathLiteral($modname) . "]/module_description";
            $entries = $xml->xpath($query);

            if (!isset($entries)) {
                return FALSE;
            } else {
                return $entries;
            }
        } catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * Method to get module name from the catalogue
     *
     * @author Nic Appleby
     * @param  string $moduleId module id
     * @return string module name|FALSE if none exists
     */
    public function getModuleName($moduleId) {
        try {
            $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";
            $xml = $this->catalogueXml();
            $query = "//module[module_id=" . $this->xpathLiteral($moduleId) . "]/module_name";
            $entries = $xml->xpath($query);

            if (!isset($entries)) {
                return FALSE;
            } else {
                return $entries;
            }
        } catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * Method to get a system configuration parameter.
    *
    * @var    string $pmodule The module code of the module owning the config item
    * @var    string $pname The name of the parameter being set, use UPPER_CASE
    * @return string $value The value of the config parameter
    */
    public function getNavParam($pmodule)
    {
        $settings=$this->readCatalogue('XML')->getItem('section','settings');
        $navigation=$settings->getItem('section','catalogue');
        return $navigation ? $navigation->toArray() : false;
    }

    private function xpathLiteral($value)
    {
        if (!str_contains($value, "'")) { return "'" . $value . "'"; }
        if (!str_contains($value, '"')) { return '"' . $value . '"'; }
        return "concat('" . implode("',\"'\",'", explode("'", $value)) . "')";
    }

    /**
     * Method to return the list of categories stored in
     * the catalogue xml document
     *
     * @return array Categories within the document
     * @access public
     */
    public function getCategories() {
        try {
            if(!file_exists($this->objConfig->getsiteRootPath()."config/systemtypes.xml"))
            {
                copy($this->objConfig->getsiteRootPath()."installer/dbhandlers/systemtypes.xml", $this->objConfig->getsiteRootPath()."config/systemtypes.xml");
                //unlink($this->objConfig->getsiteRootPath()."installer/dbhandlers/systemtypes.xml");
            }
            $sysTypes = $this->objConfig->getsiteRootPath()."config/systemtypes.xml";
            // $sysTypes = $this->objConfig->getsiteRootPath()."installer/dbhandlers/systemtypes.xml";
            $doc = simplexml_load_file($sysTypes);
            $types = array();
            for ($i=1;$i<count($doc->systemtypes->category);$i++) {
                $types[] = (string)$doc->systemtypes->category[$i];
            }
            return $types;
        } catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * This method returns a list of all the modules present in
     * a specified category
     *
     * @param  string $category The category in question.
     * @return array  List of modules
     * @access public
     */
    public function getCategoryList($category) {
        try {
            $path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";
            $cat = $this->catalogueXml();
            $types = array();
            if ($category == 'all') {
                $modules = $cat->xpath("//module");
                foreach($modules as $mod) {
                    $types[(string)$mod->module_id] = $this->objLanguage->abstractText((string)$mod->module_name);
                }
            } else {
                if(!file_exists($this->objConfig->getsiteRootPath()."config/systemtypes.xml"))
                {
                    copy($this->objConfig->getsiteRootPath()."installer/dbhandlers/systemtypes.xml", $this->objConfig->getsiteRootPath()."config/systemtypes.xml");
                    //unlink($this->objConfig->getsiteRootPath()."installer/dbhandlers/systemtypes.xml");
                }
                $sysTypes = $this->objConfig->getsiteRootPath()."config/systemtypes.xml";
                $doc = simplexml_load_file($sysTypes);
                $modules = $doc->xpath("//category[categoryname=" . $this->xpathLiteral($category) . "]");
                if (isset($modules[0]->module)) {
                    if (count($modules[0]->module) > 0) {
                        foreach ($modules[0]->module as $mod) {
                            $moduleId = (string)$mod;
                            $mn = $cat->xpath("//module[module_id=" . $this->xpathLiteral($moduleId) . "]/module_name");
                            if (!$mn && (file_exists($this->objConfig->getModulePath().$moduleId) || file_exists($this->objConfig->getsiteRootPath()."core_modules/$moduleId"))) {
                                log_debug("Could not find $moduleId in the catalogue. Rewriting catalogue.");
                                $this->writeCatalogue();
                                $cat = $this->catalogueXml();
                                $mn = $cat->xpath("//module[module_id=" . $this->xpathLiteral($moduleId) . "]/module_name");
                            }
                            if (isset($mn[0])) {
                                $types[$moduleId] = ucwords($this->objLanguage->abstractText((string)$mn[0]));
                            } else {
                                $types[$moduleId] = NULL;
                            }
                        }
                    }
                }
            }
            return $types;
        } catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * The error callback function, defers to configured error handler
     *
     * @param  string $error
     * @return void
     */
    public function errorCallback($exception)
    {
        echo customException::cleanUp($exception);
        exit();
    }

    public function skinRemoter($skins)
    {
        $path = $this->objConfig->getskinRoot();
        chdir($path);
        $lSkins = NULL;
        foreach(glob('*') as $s)
        {
            if($s == NULL)
            {
                continue;
            }
            else {
                $lSkins .= $s."|";
            }
        }
        $lSkins = explode("|", $lSkins);
        $lSkins = array_filter($lSkins);
        foreach($lSkins as $lskin)
        {
            if($lskin == 'CVS' || $lskin == 'CVSROOT' || $lskin == '_common' || $lskin == 'cache.config' || $lskin == 'error_log' || $lskin == 'icons2')
            {
                unset($lskin);
            }
            if (!empty($lskin))
            {
                $skinner[] = $lskin;
            }
        }
        if(empty($skinner))
        {
            $skinner = array();
        }
        $lSkin = array_filter($skinner);

        $this->loadClass('checkbox','htmlelements');
        $this->loadClass('link','htmlelements');

        $objH = $this->getObject('htmlheading','htmlelements');
        $objH->type=2;
        $objH->str = $this->objLanguage->languageText('mod_modulecatalogue_heading','modulecatalogue');

        $objH2 = $this->newObject('htmlheading','htmlelements');
        $objH2->type=3;
        $objH2->str = $this->objLanguage->languageText('mod_modulecatalogue_remoteskinheading','modulecatalogue');

        $hTable = $this->getObject('htmltable','htmlelements');
        $hTable->cellpadding = 2;
        $hTable->id = 'unpadded';
        $hTable->width='100%';
        $hTable->startRow();
        $hTable->addCell($objH->show());
        $hTable->endRow();
        $hTable->startRow();
        $hTable->addCell($objH2->show());
        $hTable->endRow();
        $hTable->startRow();
        $hTable->addCell('&nbsp;');
        $hTable->endRow();

        sort($skins);

        $objTable = $this->newObject('htmltable','htmlelements');
        $objTable->cellpadding = 2;
        $objTable->id = 'unpadded1';
        $objTable->width='100%';

        $masterCheck = new checkbox('arrayList[]');
        //$masterCheck->extra = 'onclick="javascript:baseChecked(this);"';

        $head = array('&nbsp', '&nbsp;',$this->objLanguage->languageText('mod_modulecatalogue_skinname','modulecatalogue'),
        $this->objLanguage->languageText('mod_modulecatalogue_install','modulecatalogue'));
        $objTable->addHeader($head,'heading','align="left"');
        $newMods = array();
        $class = 'odd';

        $link = new link();
        $link->link = $this->objLanguage->languageText('mod_modulecatalogue_dlandinstall','modulecatalogue');
        $icon = '&nbsp;'; //$this->newObject('getIcon','htmlelements');
        foreach ($skins as $skin) {
            if (!in_array($skin,$lSkins)) {
                $link->link('javascript:;');
                $link->extra = "onclick = 'javascript:downloadSkin(\"{$skin}\");'";
                $class = ($class == 'even')? 'odd' : 'even';
                $newMods[] = $skin;
                //$icon->setModuleIcon($module['id']);
                //$modCheck = new checkbox('arrayList[]');
                //$modCheck->cssId = 'checkbox_'.$skin;
                //$modCheck->setValue($skin);
                //$modCheck->extra = 'onclick="javascript:toggleChecked(this);"';

                $objTable->startRow();
                $objTable->addCell('&nbsp;',20,null,null,$class);
                $objTable->addCell('&nbsp;',30,null,null,$class);
                $objTable->addCell("<div id='link_{$skin}'><b>{$skin}</b></div>",null,null,null,$class);
                $objTable->addCell("<div id='download_{$skin}'>".$link->show()."</div>",'40%',null,null,$class);
                $objTable->endRow();
                /*$objTable->startRow();
                $objTable->addCell('&nbsp;',20,null,'left',$class);
                $objTable->addCell('&nbsp;',30,null,'left',$class);
                $objTable->addCell('&nbsp;'.'<br />&nbsp;',null,null,'left',$class, 'colspan="2"');
                $objTable->endRow();*/
            }
        }

        if (empty($newMods)) {
            $objTable->startRow();
            $objTable->addCell("<span class='empty'>".$this->objLanguage->languageText('mod_modulecatalogue_noremoteskins','modulecatalogue').'</span>',null,null,'left',null, 'colspan="4"');
            $objTable->endRow();
        }

        return $hTable->show()."<br />".$objTable->show();
    }
    
    /**
     * Method to get engine version from the catalogue
     *
     * @author Paul Scott <pscott@uwc.ac.za>
     * @return  string $enginever engine version
     */
    public function getEngineVer() {
        try {
            $this->_path = $this->objConfig->getsiteRootPath()."config/catalogue.xml";
            $xml = $this->catalogueXml();
            $query = "//engine_version";
            $enginever = $xml->xpath($query);

            if (!isset($enginever)) {
                return FALSE;
            } else {
                return $enginever;
            }
        } catch (Exception $e){
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

}
?>
