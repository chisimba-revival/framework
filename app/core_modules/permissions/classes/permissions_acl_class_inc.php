<?php
// security check - must be included in all scripts
if ( !$GLOBALS['kewl_entry_point_run'] ) {
    die( "You cannot view this page directly" );
}
/**
* @copyright (c) 2000-2004, Kewl.NextGen ( http://kngforge.uwc.ac.za )
* @package permissions
* @subpackage access
* @version 0.1
* @since 22 November 2004
* @author Jonathan Abrahams
* @filesource
*/

/**
* The permissions acl class processes and maintains all acl data.
* <PRE>
* Public Inteface:
* ACL table
*    addAclUser        - To assign a user to an existing acl.
*    deleteAclUser     - To unassign a user from an acl.
*    addAclGroup       - To assign a group to an acl.
*    deleteAclGroup    - To unassign a group from an acl.
*    getAclUsers       - To get all the assigned users for this acl.
*    getAclGroups      - To get all the assigned groups for this acl.
*    getUserAcls       - To get this users assigned acls.
* </PRE>
*
* @author Jonathan Abrahams
*/
class permissions_acl extends dbTable
{
    /**
    * @var user an object reference.
    */
    var $objUser;

    /**
    * @var identityservice canonical logical-to-permission identity resolver.
    */
    var $objIdentity;

    /**
    * Method to initialise an object.
    */
    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        $this->objUser = $this->getObject( 'user', 'security' );
        $this->objIdentity = $this->getObject( 'identityservice', 'security' );
        parent::init( 'tbl_permissions_acl' );
    }

    /**
    * Method to assign a user to an existing acl.
    *
    * @param string $acl The unique ID for the access control list.
    * @param string $userId The unique ID of an existing user. NB use PKid( userId ) method in user class
    * @return string|false the newly generated unique id for this acl row if successful, otherwise false.
    */
    function addAclUser( $acl, $userId )
    {
        $row = array();
        $row['acl_id'] = $acl;
        $row['user_id'] = $userId;
        $row['group_id'] = NULL;
        $row['last_updated'] = date( "Y:m:d H:i:s" );
        $row['last_updated_by'] = $this->objUser->userId();
        return parent::insert( $row );
    }

    /**
    * Method to unassign a user from an acl.
    *
    * @param string $aclId The unique ID for the access control list.
    * @param string $userId The stored user primary key used by the ACL row.
    * @return true|false TRUE on success, FALSE on failure
    */
    function deleteAclUser( $aclId, $userId )
    {
        return parent::delete( 'user_id', "$userId' AND acl_id = '$aclId" );
    }

    /**
    * Method to assign a group to an acl.
    *
    * @param string $aclId The unique ID of an existing acl.
    * @param string $groupId The unique ID of an existing group.
    * @return true|false TRUE on success, FALSE on failure
    */
    function addAclGroup( $aclId, $groupId )
    {
        $row = array();
        $row['acl_id'] = $aclId;
        $row['user_id'] = NULL;
        $row['group_id'] = $groupId;
        $row['last_updated'] = date( "Y:m:d H:i:s" );
        $row['last_updated_by'] = $this->objUser->userId();
        return $this->insert( $row );
    }

    /**
    * Method to unassign a group from an acl.
    *
    * @param string $aclId The unique ID of an existing acl.
    * @param string $groupId The unique ID of an existing group.
    * @return true|false TRUE on success, FALSE on failure
    */
    function deleteAclGroup( $aclId, $groupId )
    {
        return parent::delete( 'group_id', "$groupId' AND acl_id = '$aclId" );
    }

    /**
    * Method to get all the assigned users for this acl.
    *
    * @param string $aclId The unique ID for this access control list.
    * @param string $fields ( optional )
    * @return array|false The user rows as an array of associate arrays, or FALSE on failure
    */
    function getAclUsers( $aclId, $fields = null )
    {
        $permission_aclDb = $this->_tableName;
        $usersDb = 'tbl_users';

        $sql = "SELECT ";
        $sql .= $fields ? implode( ",", $fields ) : " $usersDb.id, $usersDb.firstName as firstname, $usersDb.surname ";
        $sql .= " FROM $permission_aclDb";
        $join = " INNER JOIN $usersDb";
        $join .= " ON ( user_id = $usersDb.id )";
        $filter = " WHERE acl_id = '$aclId'";

        $data1=$this->getArray( $sql . $join . $filter );
        if (!is_null($fields)){
            // Jump out here if non-default fields were asked for
            return $data1;
        }
        // Else build a new array with the needed fields
        $data2=array();
        foreach ($data1 as $line){
            $data2[]=array('id'=>$line['id'],'fullName'=>$line['firstname'].' '.$line['surname']);
        }
        return $data2;
    }

    /**
    * Method to get all the assigned groups for this acl.
    *
    * @param string $aclId The unique ID for this access control list.
    * @param string $fields ( optional )
    * @return array|false The group rows as an array of associate arrays, or FALSE on failure
    */
    function getAclGroups( $aclId, $fields = null )
    {
        $permission_aclDb = $this->_tableName;
        $groupsDb = 'tbl_groupadmin_group';

        $sql = "SELECT ";
        $sql .= $fields ? implode( ",", $fields ) : "$groupsDb.id, name";
        $sql .= " FROM $permission_aclDb";
        $join = " INNER JOIN $groupsDb";
        $join .= " ON ( group_id = $groupsDb.id )";
        $filter = " WHERE acl_id = '$aclId'";

        return $this->getArray( $sql . $join . $filter );
    }

    /**
    * Method to get this user's assigned acls.
    *
    * @param string $userId The logical user ID used by the security service.
    * @return array The list of unique ID for acls as an array.
    */
    function getUserAcls( $userId )
    {
        if (!is_scalar($userId) || trim((string) $userId) === '') {
            return array();
        }

        $logicalUserId = trim((string) $userId);
        $permissionUserId = $this->objIdentity->permissionUserIdForUser($logicalUserId);
        if ($permissionUserId === null
            || !preg_match('/^[0-9]+$/', (string) $permissionUserId)) {
            return array();
        }

        // Resolve direct groups and all their ancestors. ACLs attached to a
        // parent group therefore apply to members of its nested groups too.
        $groupIds = array();
        $rows = parent::getArray(
            'SELECT group_id FROM tbl_perms_groupusers'
            . ' WHERE perm_user_id = ' . (int) $permissionUserId
        );
        if (!is_array($rows)) {
            return array();
        }
        foreach ($rows as $row) {
            $groupIds[(int) $row['group_id']] = true;
        }

        $frontier = array_keys($groupIds);
        for ($depth = 0; $depth < 32 && !empty($frontier); $depth++) {
            $parentRows = parent::getArray(
                'SELECT group_id FROM tbl_perms_group_subgroups'
                . ' WHERE subgroup_id IN ('
                . implode(',', array_map('intval', $frontier)) . ')'
            );
            if (!is_array($parentRows)) {
                return array();
            }
            $frontier = array();
            foreach ($parentRows as $row) {
                $parentId = (int) $row['group_id'];
                if ($parentId > 0 && !isset($groupIds[$parentId])) {
                    $groupIds[$parentId] = true;
                    $frontier[] = $parentId;
                }
            }
        }

        // Legacy direct ACL rows store tbl_users.id. During identity migration
        // some installations used perm_user_id, so accept both representations.
        $directIds = array();
        $storageUserId = $this->objUser->PKId($logicalUserId);
        foreach (array($storageUserId, $permissionUserId) as $directId) {
            if (is_scalar($directId) && trim((string) $directId) !== '') {
                $directIds[] = $this->quoteValue((string) $directId);
            }
        }

        $conditions = array();
        if (!empty($directIds)) {
            $conditions[] = 'user_id IN (' . implode(',', array_unique($directIds)) . ')';
        }
        if (!empty($groupIds)) {
            $conditions[] = 'group_id IN ('
                . implode(',', array_map('intval', array_keys($groupIds))) . ')';
        }
        if (empty($conditions)) {
            return array();
        }

        $aclRows = parent::getArray(
            'SELECT DISTINCT acl_id FROM ' . $this->_tableName
            . ' WHERE ' . implode(' OR ', $conditions)
        );
        if (!is_array($aclRows)) {
            return array();
        }

        $result = array();
        foreach ($aclRows as $acl) {
            if (isset($acl['acl_id'])) {
                $result[(string) $acl['acl_id']] = true;
            }
        }
        return array_keys($result);
    }

    private function quoteValue($value)
    {
        $db = $this->objEngine->getDbObj();
        return method_exists($db, 'quoteSmart')
            ? $db->quoteSmart($value)
            : "'" . str_replace("'", "''", $value) . "'";
    }
}

?>
