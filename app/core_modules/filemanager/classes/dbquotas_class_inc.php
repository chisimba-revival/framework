<?php
/* CHISIMBA_PHP8_ZERO_ARG_MKTIME: time() with no arguments is time() on PHP 8. */

/**
 * Class to handle file quotas
 *
 * This handles quota functionality in the file manager, setting custom quotas,
 * how much left users can upload, etc.
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
 * @package   filemanager
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: dbfiletags_class_inc.php 2804 2007-08-03 06:33:05Z paulscott $
 * @link      http://avoir.uwc.ac.za
 * @see
 */


/**
 * Class to handle file quotas
 *
 * This handles quota functionality in the file manager, setting custom quotas,
 * how much left users can upload, etc.
 *
 * @category  Chisimba
 * @package   filemanager
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see
 */
/*
 * CHISIMBA_PHP82_MKTIME_ZERO_ARGS
 *
 * PHP 8 requires at least one argument for time(). This class used
 * zero-argument time() only to obtain the current Unix timestamp,
 * so time() is the direct behavioural equivalent.
 */
class dbquotas extends dbTable {

    // State used by authenticated editing and file selection.
    public $objLanguage;
    public $objUser;


    /**
     * Constructor
     */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback') {
        parent::init('tbl_files_quotas');
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->loadClass('formatfilesize', 'files');
    }

    /**
     * Method to return the default User Quota
     * @return int
     */
    public function getDefaultUserQuota() {
        $objSysconfig = $this->getObject('dbsysconfig', 'sysconfig');
        return $objSysconfig->getValue('USERQUOTA', 'filemanager');
    }

    /**
     * Method to return the default Context Quota
     * @return int
     */
    public function getDefaultContextQuota() {
        $objSysconfig = $this->getObject('dbsysconfig', 'sysconfig');
        return $objSysconfig->getValue('CONTEXTQUOTA', 'filemanager');
    }

    /**
     * Method to get a quota by providing the record id
     * @param string $id Record Id
     * @return array|FALSE
     */
    public function getQuotaFromId($id) {
        return $this->getRow('id', $id);
    }

    /**
     * Method to get a quota for a path
     * @param string $path Path to get quota for
     * @return array Details of Quota
     */
    public function getQuota($path) {
        $path = $this->checkPath($path);

        // Reject quota request if path is not valid
        if ($path == FALSE) {
            return FALSE;
        }

        $rc = (int)$this->getRecordCount("WHERE path='$path'");
        if ($rc == 0 || $rc > 1) {
            if ($rc > 1) {
                $this->delete('path', $path);
            }
            $quota = $this->setupQuotaFirstRun($path);
        } else {
            // $rc == 1
            $quota = $this->getRow('path', $path);
        }

        if ($quota != FALSE) {
            if ($quota['usedefault'] == 'Y') {

                if (substr($path, 0, 5) == 'users') {
                    $value = $this->getDefaultUserQuota();
                } else {
                    $value = $this->getDefaultContextQuota();
                }

                $quota['quota'] = $value;
            }

            $quota['quota'] = $quota['quota']*1024*1024; // Convert to Megabytes
        }

        return $quota;
    }

    /**
     * Method to setup Quota for the first time.
     * @param string $path Path
     */
    public function setupQuotaFirstRun($path, $userid = NULL) {
        $usage = $this->getFileUsage($path);

        $result = $this->addQuota($path, $usage, $userid);

        if ($result != FALSE) {
            return $this->getRow('path', $path);
        } else {
            return FALSE;
        }
    }

    /**
     * Method to add quota to the database
     * @param string $path Path for which quota is applicable
     * @param string $usage Current Usage
     */
    private function addQuota($path, $usage, $creatorid = NULL) {
        if($creatorid == NULL) {
            $creatorid = $this->objUser->userId();
            $modifierid = $creatorid;
        }

        //for ldap users, the id will still be null, so get it from the path
        if($creatorid == NULL) {
            $xpath=explode("/" , $path);
            $creatorid = $xpath[1];
            $modifierid = $creatorid;
        }
        //still, just in case
        if($creatorid == NULL) {
            return FALSE;
        }
        return $this->insert(
                array(
                'path' => $path,
                'usedefault' => 'Y',
                'quota' => 0,
                'quotausage' => $usage,
                'creatorid' => $creatorid,
                'datecreated' => date('Y-m-d H:i:s'),
                'modifierid' => $creatorid,
                'datemodified' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Method to run an update on the usage for a path
     * @param string $path Path for which quota is applicable
     * @return boolean Result of update
     */
    public function updateUsage($path) {
        $path = $this->checkPath($path);

        $usage = $this->getFileUsage($path);

        $result = $this->update('path', $path, array(
                'quotausage'=>$usage,
                'modifierid' => $this->objUser->userId(),
                'datemodified' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Method to get the file usage for a path
     *
     * This gets a list of all files in a particular path, and calculates
     * sum of file sizes
     *
     * @param string $path Path for which quota is applicable
     * @return int Current Sum of file sizes in path
     */
    private function getFileUsage($path) {
        $folderParts = explode('/', $path);

        $fileSize = 0;

        $this->objFiles = $this->getObject('dbfile');
        $fileList = $this->objFiles->getPathFiles($folderParts[0], $folderParts[1]);

        if ((is_countable($fileList) ? count($fileList) : 0) > 0) {
            foreach ($fileList as $file) {
                $fileSize += $file['filesize'];
            }
        }
        return $fileSize;
    }

    /**
     * Method to check path for quota
     * Quotas is based on the first two parts. Eg. users/1, contexts/bio101
     *
     * If path is longer, return the first two. If less than 1, return FALSE
     * as path is not correct
     */
    private function checkPath($path) {
        $folderParts = explode('/', $path);

        if ((is_countable($folderParts) ? count($folderParts) : 0) == 2) {
            return $path;
        } else if ($folderParts == 1) {
            return FALSE;
        } else {
            return $folderParts[0].'/'.$folderParts[1];
        }
    }

    /**
     * Method to get a graph of file usage for a given path
     * @param string $path Path to get the graph for
     * @param boolean $includeFreeSpace Include a summary of space left
     * @return string
     */
    public function getQuotaGraph($path, $includeFreeSpace=TRUE) {
        $quota = $this->getQuota($path);

        if ($quota['quotausage'] > $quota['quota']) {
            $freeSpace = 0;
        } else {
            $freeSpace = $quota['quota'] - $quota['quotausage'];
        }

        if ($includeFreeSpace) {
            $objFileSize = new formatfilesize();
            $freeSpaceStr = $this->objLanguage->languageText('mod_filemanager_freespace', 'filemanager', 'Free Space').': '.$objFileSize->formatsize($freeSpace);
        } else {
            $freeSpaceStr = '';
        }

        return $this->generateQuotaGraph($quota['quota'], $quota['quotausage']).$freeSpaceStr;
    }

    /**
     * Method to generate a graph of file usage by passing values
     * @param int $quota Total size of allocated quota
     * @param int $usage Size of quota used
     * @return string Generated Graph
     */
    public function generateQuotaGraph($quota, $usage) {
        if ($usage > $quota) {
            $cssClass = 'quotaup';
            $width = 100;
        } else {
            $cssClass = 'innerquota';

            if ($quota == 0) {
                $width = 100;
            } else {
                $width = round($usage / $quota * 100);
            }

        }

        return '<div class="outerquota"><div class="'.$cssClass.'" style="width: '.$width.'%">'.$width.'%</div></div>';
    }

    /** Build only allowlisted identifiers; all search text remains bound data. */
    private function quotaSearch($searchType, $searchField, $searchFor, $orderBy)
    {
        $context = $searchType === 'context';
        $fields = $context ? ['title', 'contextcode'] : ['firstname', 'surname'];
        $searchField = is_string($searchField) && in_array($searchField, $fields, true) ? $searchField : $fields[0];
        $orders = ['quotausage_desc' => 'tbl_files_quotas.quotausage DESC', 'quotausage' => 'tbl_files_quotas.quotausage ASC'];
        foreach ($fields as $field) $orders[$field] = $field . ' ASC';
        $order = is_string($orderBy) && isset($orders[$orderBy]) ? $orders[$orderBy] : $orders['quotausage_desc'];
        $substring = $this->getSubstring('tbl_files_quotas.path', $context ? 9 : 7);
        $from = $context
            ? ' FROM tbl_files_quotas JOIN tbl_context ON contextcode = ' . $substring
            : ' FROM tbl_files_quotas JOIN tbl_users ON userId = ' . $substring;
        $from .= ' WHERE tbl_files_quotas.path LIKE ?';
        $params = [$context ? 'context/%' : 'users/%'];
        $searchFor = is_scalar($searchFor) ? trim((string)$searchFor) : '';
        if ($searchFor !== '') {
            $from .= ' AND ' . $searchField . ' LIKE ?';
            $params[] = $searchFor . '%';
        }
        return [$from, $params, $order];
    }

    /** Fetch a stable, bounded page of allocations for the quota administrator. */
    public function getResults($searchType, $searchField, $searchFor, $orderBy='', $numItemsPerPage=20, $page=0)
    {
        [$from, $params, $order] = $this->quotaSearch($searchType, $searchField, $searchFor, $orderBy);
        $limit = max(1, min(100, (int)$numItemsPerPage));
        $offset = max(0, min(1000000, (int)$page)) * $limit;
        $fields = $searchType === 'context' ? 'title, contextcode' : 'firstName AS firstname, surname';
        return $this->selectPrepared('SELECT tbl_files_quotas.*, ' . $fields . $from
            . ' ORDER BY ' . $order . ', tbl_files_quotas.id ASC LIMIT ' . $limit . ' OFFSET ' . $offset, $params);
    }

    public function getNumResults($searchType, $searchField, $searchFor, $orderBy='')
    {
        [$from, $params] = $this->quotaSearch($searchType, $searchField, $searchFor, $orderBy);
        $results = $this->selectPrepared('SELECT COUNT(tbl_files_quotas.id) AS thecount' . $from, $params);
        return (int)($results[0]['thecount'] ?? 0);
    }

    public function getNumPages($searchType, $searchField, $searchFor, $orderBy, $numItemsPerPage)
    {
        $limit = max(1, min(100, (int)$numItemsPerPage));
        return max(1, (int)ceil($this->getNumResults($searchType, $searchField, $searchFor, $orderBy) / $limit));
    }

    /** Compare the loaded allocation atomically so a concurrent edit is not overwritten. */
    public function saveQuotaChecked(array $original, $type, $size)
    {
        $size = $type === 'Y' ? (int)$original['quota'] : $size;
        if ($type === $original['usedefault'] && $size === (int)$original['quota']) return true;
        return $this->executePrepared('UPDATE tbl_files_quotas SET usedefault = ?, quota = ?, modifierid = ?, datemodified = ?'
            . ' WHERE id = ? AND usedefault = ? AND quota = ? AND datemodified <=> ?',
            [$type, $size, $this->objUser->userId(), date('Y-m-d H:i:s'), $original['id'],
                $original['usedefault'], $original['quota'], $original['datemodified']]) === 1;
    }

    /**
     * Method set an item to use the default quota
     * @param string $id Record Id
     * @return boolean
     */
    public function setToUseDefaultQuota($id) {
        return $this->update('id', $id, array(
                'usedefault'=> 'Y',
                'modifierid' => $this->objUser->userId(),
                'datemodified' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Method set an item to use a custom quota
     * @param string $id Record Id
     * @param int $size Size of Custom Quota in MB
     * @return boolean
     */
    public function setToUseCustomQuota($id, $size) {
        return $this->update('id', $id, array(
                'usedefault'=> 'N',
                'quota'=> $size,
                'modifierid' => $this->objUser->userId(),
                'datemodified' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Method to determine the amount of space a user has left in his quota
     */
    public function getRemainingSpaceUser($userId) {
        $quota = $this->getQuota('users/'.$userId);

        if ($quota['quotausage'] >= $quota['quota']) {
            return 0;
        } else {
            return $quota['quota'] - $quota['quotausage'];
        }
    }

}

?>
