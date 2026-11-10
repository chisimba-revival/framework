<?php
/**
 * The patch class is used to read and write module version information,
 *  as well as to apply patches to modules
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
 * @author    Nic Appleby <nappleby@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

class patch extends dbtable {
    /** Catalogue services used while applying registered updates. */
    public $objModule;
    public $objModuleAdmin;


    /**
     * Configuration object
     *
     * @var object $objConfig
     */
    public $objConfig;

    /**
     * Object to find module register file
     *
     * @var object $objModFile
     */
    protected $objModFile;

    /**
     * Object to get language elements
     *
     * @var object $objLanguage
     */
    public $objLanguage;

    /**
     * Chisimba init function
     *
     */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback') {
        parent::init('tbl_modules');
        $this->objConfig = $this->getObject('altconfig','config');
        $this->objModFile = $this->getObject('modulefile','modulecatalogue');
        $this->objLanguage = $this->getObject('language','language');
    }

    /**
    * This is a method to return an array of the registered modules
    * that have a more recent version in code than in the database
    * @returns array $modules
    */
    public function checkModules() {
        try {
            $modArray=$this->getAll();
            $modules=array();
            foreach ($modArray as $module) {
                $codeVersion = (float)$this->readVersion($module['module_id']);
                $dbVersion = (float)$module['module_version'];
                // Now compare the two
                //echo "{$module['module_id']} $dbVersion >= $codeVersion<br/>";
                if ($codeVersion>$dbVersion) {
                    //check for xml document
                    $description = $this->readUpdateDesc($module['module_id']);
                    if(empty($description) || $description == '') {
                        $description = $this->objLanguage->languageText('mod_modulecatalogue_newlangitems','modulecatalogue');
                    }
                    if ($updateFile = $this->objModFile->findSqlXML($module['module_id'])) {
                        $check = file_get_contents($updateFile);
                        if(!empty($check)) {
                            if (!$objXml = simplexml_load_file($updateFile)) {
                                throw new Exception($this->objLanguage->code2Txt('mod_modulecatalogue_badxml','modulecatalogue',array('FILENAME' => $updateFile)));
                            }
                            $desc = $objXml->xpath("//update[version='{$codeVersion}']/description");
                            if (isset($desc[0])) {
                                $description .= $desc[0];
                            }
                        } else {
                            log_debug("WARNING: module {$module['module_id']} has an invalid sql_updates.xml document. Ignoring.");
                        }
                    }
                    // else add in the description from the register file

                    $modules[]=array('module_id'=>$module['module_id'],'old_version'=>$dbVersion,'new_version'=>$codeVersion,'desc'=>$description);
                }
            }
            return $modules;
        } catch (customException $e) {
            echo $e->getMessage();
            $e->cleanUp();
            exit(0);
        } catch (Exception $e) {
            echo $e->getMessage();
            exit(0);
        }
    }

    /**
    * This method returns the version of a module in the database
    * ie: The version level of the emodule at the time it was registered.
    * @param  string $module the module to lookup
    * @return string $version the version in the database
    */
    function getVersion($module) {
        $row=$this->selectPrepared('SELECT module_version FROM tbl_modules WHERE module_id=?',[$module])[0]??null;
        return $row['module_version']??false;
    }

    /**
    * This method calls a function to read the XML file
    * and walks through it, processing each update
    * @param string $modname the name of the module
    *
    */
    /** Apply reviewed XML operations, retaining scalar, repeat-safe receipts. */
    public function applyUpdates($modname) {
        if (!is_string($modname) || !preg_match('/^[a-zA-Z0-9_-]+$/D',$modname)) throw new InvalidArgumentException('Invalid module identifier');
        return $this->withDatabaseLock('patch:'.substr(hash('sha256',$modname),0,18),function()use($modname){
            $this->objModule=$this->getObject('modules','modulecatalogue');
            $this->objModuleAdmin=$this->getObject('modulesadmin','modulecatalogue');
            $this->objModFile=$this->getObject('modulefile','modulecatalogue');
            $registration=$this->objModFile->readRegisterFile($this->objModFile->findRegisterFile($modname));
            if (!is_array($registration) || !isset($registration['MODULE_VERSION'])) throw new RuntimeException('Module registration is unavailable: '.$modname);
            $unmet=[];$missing=[];$local=$this->objModFile->getLocalModulelist();
            foreach ($registration['DEPENDS']??[] as $dependency) {
                if (!$this->objModule->checkIfRegistered($dependency)) {
                    if (in_array($dependency,$local,true)) $unmet[]=$dependency; else $missing[]=$dependency;
                }
            }
            if ($unmet || $missing) return ['unMetDep'=>$modname,'modules'=>$unmet,'missing'=>$missing];
            $old=$this->getVersion($modname);
            if ($old===false) throw new RuntimeException('Cannot patch an unregistered module: '.$modname);
            $operations=$this->pendingOperations($this->objModFile->findSqlXML($modname),(float)$old,(float)$registration['MODULE_VERSION']);
            // Validate the entire pending plan before any operation or receipt write.
            foreach ($operations as $operation) {
                if (!isset($operation['data']['deleteRows']) && $this->objModuleAdmin->alterTable($operation['table'],$operation['data'],true)!==true) throw new RuntimeException('Schema validation failed for '.$modname);
            }
            foreach ($operations as $operation) $this->applyOperation($modname,$operation);
            if ($this->objModuleAdmin->loadData($modname)===false) throw new RuntimeException('Default data update failed: '.$modname);
            if (!$this->objModuleAdmin->installModule($registration,true)) throw new RuntimeException('Module registration failed: '.$modname.' ('.$this->objModuleAdmin->getLastErrorCode().')');
            return ['current'=>$this->getVersion($modname),'old'=>(float)$old,'modname'=>$modname];
        });
    }

    /** Parse scalars only; reject malformed XML and unsupported operations. */
    protected function pendingOperations($file,$old,$target) {
        if (!is_string($file) || $file==='' || !is_file($file)) return [];
        $source=file_get_contents($file);
        if ($source===false) throw new RuntimeException('Cannot read module update XML');
        if (trim($source)==='') return [];
        if (stripos($source,'<!DOCTYPE')!==false || stripos($source,'<!ENTITY')!==false) throw new RuntimeException('XML document types and entities are not permitted');
        $previous=libxml_use_internal_errors(true);
        try {$xml=simplexml_load_string($source,'SimpleXMLElement',LIBXML_NONET);}
        finally {libxml_clear_errors();libxml_use_internal_errors($previous);}
        if ($xml===false || $xml->getName()!=='updates') throw new RuntimeException('Invalid module update XML');
        $operations=[];$ordinal=0;
        foreach ($xml->update as $update) {
            $version=trim((string)$update->version);
            if (!preg_match('/^[0-9]+(?:\.[0-9]+)?$/D',$version)) throw new RuntimeException('Invalid XML patch version');
            foreach ($update->data as $data) foreach ($data as $kind=>$value) {
                $position=$ordinal++;
                if ((float)$version<=$old || (float)$version>$target) continue;
                $table=(string)$update->table;
                $this->patchIdentifier($table);
                switch ($kind) {
                    case 'name': $parsed=(string)$value;$this->patchIdentifier($parsed);break;
                    case 'remove': $field=(string)$value;$this->patchIdentifier($field);$parsed=[$field=>[]];break;
                    case 'add':
                        $field=(string)$value->name;$this->patchIdentifier($field);$spec=[];
                        foreach ($value as $key=>$scalar) if ($key!=='name') $spec[$key]=$this->patchScalar($scalar);
                        $parsed=[$field=>$spec];break;
                    case 'change': case 'rename':
                        $parsed=[];
                        foreach ($value as $field=>$spec) {
                            $this->patchIdentifier($field);$parsed[$field]=[];
                            foreach ($spec as $key=>$item) {
                                if ($key==='definition') {
                                    $definition=[];foreach ($item as $option=>$scalar) $definition[$option]=$this->patchScalar($scalar);
                                    $parsed[$field][$key]=$definition;
                                } else $parsed[$field][$key]=$this->patchScalar($item);
                            }
                        }
                        if (!$parsed) throw new RuntimeException('Empty schema alteration');break;
                    case 'insert': case 'deleteRows':
                        $parsed=[];
                        foreach ($value as $field=>$scalar) {$this->patchIdentifier($field);$parsed[$field]=$this->patchScalar($scalar);}
                        if (!$parsed) throw new RuntimeException($kind.' requires conditions or values');break;
                    default: throw new RuntimeException('Unsupported XML patch operation: '.$kind);
                }
                $operations[]=['version'=>$version,'table'=>$table,'data'=>[$kind=>$parsed],'ordinal'=>$position];
            }
        }
        usort($operations,fn($a,$b)=>[(float)$a['version'],$a['ordinal']]<=>[(float)$b['version'],$b['ordinal']]);
        return $operations;
    }
    private function patchIdentifier($value) {
        if (!is_string($value) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D',$value)!==1) throw new RuntimeException('Invalid XML patch identifier');
    }
    private function patchScalar($value) {
        if (count($value->children())!==0) throw new RuntimeException('Expected scalar XML patch value');
        return (string)$value;
    }

    /** DDL implicitly commits in MySQL: an uncertain pending receipt blocks retry.
     * DML and its applied receipt commit together. Never infer success from a column's existence.
     */
    private function applyOperation($module,array $operation) {
        $digest=hash('sha256',json_encode($operation,JSON_THROW_ON_ERROR));
        $id=substr(hash('sha256',$module.':'.$operation['version'].':'.$operation['ordinal']),0,32);
        $receipt=$this->selectPrepared('SELECT patchdata FROM tbl_module_patches WHERE id=?',[$id])[0]??null;
        if ($receipt) {
            $saved=json_decode($receipt['patchdata'],true,512,JSON_THROW_ON_ERROR);
            if (($saved['sha256']??'')!==$digest || ($saved['state']??'')!=='applied') throw new RuntimeException('Patch '.$module.' '.$operation['version'].' has an incomplete receipt; inspect the schema before retrying');
            return;
        }
        foreach ($this->selectPrepared('SELECT patchdata FROM tbl_module_patches WHERE moduleid=? AND version=? AND tablename=?',[$module,$operation['version'],$operation['table']]) as $legacy) {
            $saved=json_decode($legacy['patchdata'],true);
            if (!is_array($saved) || ($saved['format']??null)!==1) throw new RuntimeException('Legacy patch receipt requires review before retrying '.$module.' '.$operation['version']);
        }
        $kind=array_key_first($operation['data']);
        $encoded=fn($state)=>json_encode(['format'=>1,'sha256'=>$digest,'operation'=>$kind,'state'=>$state],JSON_THROW_ON_ERROR);
        $write=function($state)use($id,$module,$operation,$encoded){
            $this->executePrepared('INSERT INTO tbl_module_patches (id,moduleid,version,tablename,patchdata,applied) VALUES (?,?,?,?,?,?)',[$id,$module,$operation['version'],$operation['table'],$encoded($state),$state==='applied'?date('Y-m-d'):null]);
        };
        $execute=function()use($operation){
            if (isset($operation['data']['deleteRows'])) {
                $where=[];$values=[];
                foreach ($operation['data']['deleteRows'] as $field=>$value) {$where[]='`'.$field.'`=?';$values[]=$value;}
                $this->executePrepared('DELETE FROM `'.$operation['table'].'` WHERE '.implode(' AND ', $where),$values);
            } elseif ($this->objModuleAdmin->alterTable($operation['table'],$operation['data'],false)!==true) throw new RuntimeException('Patch operation failed');
        };
        if (in_array($kind,['insert','deleteRows'],true)) {
            foreach ([$operation['table'],'tbl_module_patches'] as $table) {
                $row=$this->selectPrepared('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table])[0]??null;
                if (strcasecmp($row['engine']??'','InnoDB')!==0) throw new RuntimeException('Data patches require transactional tables');
            }
            $this->transactionWithSavepoint(function()use($execute,$write){$execute();$write('applied');});
        } else {
            $write('pending');
            $execute();
            $this->executePrepared('UPDATE tbl_module_patches SET patchdata=?,applied=? WHERE id=?',[$encoded('applied'),date('Y-m-d'),$id]);
        }
    }

    /**
    * This method reads a register.conf file
    * And returns the module version number
    * @param string $module the module id
    * @return string The module version
    */
    private function readVersion($module) {
        try {
            //Check that the register file is there.
            $registerFile = $this->objModFile->findRegisterFile($module);
            if (!is_string($registerFile) || $registerFile === '' || !is_file($registerFile)) {
                return FALSE;
            }
            $regdata = @file($registerFile);
            if ($regdata === false) return FALSE;
            //var_dump($regdata);
            // Now look up the version number from that file
            foreach  ($regdata as $line) {
                $array = explode(': ',$line);
                //var_dump($array); //die();
                switch ($array[0]) {
                    case 'MODULE_VERSION':
                        return $array[1];
                        break;
                    default:
                        break;
                }
            }
        } catch (Exception $e) {
            echo customException::cleanUp($e->getMessage());
            exit(0);
        }
    }

   /**
    * This method reads a register.conf file
    * And returns the module update description
    *
    * @param string $module the module id
    * @return string The module version
    */
    private function readUpdateDesc($module) {
        try {
            //Check that the register file is there.
            if (!$regdata = file($this->objModFile->findRegisterFile($module))) {
                return FALSE;
            }
            //var_dump($regdata);
            // Now look up the description from that file
            foreach  ($regdata as $line) {
                $array = explode(': ',$line);
                //var_dump($array); //die();
                switch ($array[0]) {
                    case 'UPDATE_DESCRIPTION':
                        return $array[1];
                        break;
                    default:
                        break;
                }
            }
        } catch (Exception $e) {
            echo customException::cleanUp($e->getMessage());
            exit(0);
        }
    }
}
?>
