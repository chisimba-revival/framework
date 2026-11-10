<?php

/**
 * Permissions interface class
 *
 * Class with commonly used permissions methods to be used in all modules that need to
 * access permissions.
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
 * @package   permissions
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: $
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */


/**
 * Permissions interface class
 *
 * Commonly used permissions checks and balances
 *
 * @category  Chisimba
 * @package   permissions
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class perms extends ChisimbaObject
{

    public $objLanguage;
    public $objUser;

    /**
    * Standard Constructor
    */
    public function init()
    {
        $this->objLanguage = $this->getObject('language', 'language');
    }

    /**
     * Method to check permissions.
     *
     * Call statically to check that a certain user has a right to do an action
     *
     * @param string $action - Rule to check
     * @return boolean True on success
     */
     public function checkRule($rule, $module = NULL) {
         $module = $module === NULL ? $this->moduleName : $module;
         $permissions = $this->getObject('permissionservice', 'security');
         $user = $this->getObject('user', 'security');
         $areaId = $permissions->areaIdForName('chisimba', $module);
         if ($areaId === null) {
             return false;
         }
         $rightId = $permissions->rightIdForArea($areaId, $rule);
         return $rightId !== null
             && $permissions->isGranted($user->userId(), $rightId);
     }

     /**
      * Method to dump all rights to an array
      *
      * @param void
      * @return array
      */
     public function getAllRights() {
         return array();
     }

     public function outputRights() {
         // Native callers resolve rights explicitly through checkRule().
         // Runtime constants were a LiveUser compatibility mechanism.
         return array();
     }

     public function isContextMember($group = 'Lecturers') {
        $this->objUser = $this->getObject('user', 'security');
        $this->objGroups = $this->getObject('groupservice', 'groupadmin');
        $grid = $this->objGroups->groupIdForName($group);
        $userId = $this->objUser->userId();
        $ret = $this->objGroups->isGroupMember($userId, $grid);

        return $ret;
     }

     public function isMember($group = 'Lecturers') {
        $this->objUser = $this->getObject('user', 'security');
        $this->objGroups = $this->getObject('groupservice', 'groupadmin');
        $grid = $this->objGroups->groupIdForName($group);
        $userId = $this->objUser->userId();
        $ret = $this->objGroups->isGroupMember($userId, $grid);
     }
}
?>
