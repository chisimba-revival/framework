<?php
/**
 * Bounded diagnostic consumer for the canonical identity, groups and
 * authorization proof.
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class canonicalidentityproof extends ChisimbaObject
{
    public function resolve()
    {
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $sessions = $stack['sessions'];
        $userId = $sessions->getUserId();
        $result = array(
            'ok' => false,
            'code' => 'not_authenticated',
            'authenticated' => $sessions->isAuthenticated(),
            'user_id' => $userId,
            'permission_user_id' => null,
            'username' => null,
            'display_name' => null,
            'groups' => array(),
            'site_admin_group_id' => null,
            'canonical_site_admin_member' => false,
            'adapter_site_admin_member' => false,
            'identity_adapter_match' => false,
            'membership_adapter_match' => false,
            'area_id' => null,
            'right_id' => null,
            'modulecatalogue_toolbar_access' => false,
            'legacy_is_admin' => false,
        );
        if (!$result['authenticated'] || $userId === null) {
            return $result;
        }

        $user = $this->getObject('userservice', 'security')->findByUserId($userId);
        if (!is_array($user) || !isset($user['userid'], $user['username'])) {
            $result['code'] = 'canonical_user_not_found';
            return $result;
        }
        $result['username'] = (string) $user['username'];
        $result['display_name'] = trim(
            (isset($user['firstname']) ? $user['firstname'] : '') . ' ' .
            (isset($user['surname']) ? $user['surname'] : '')
        );

        $identity = $this->getObject('identityservice', 'security');
        $result['permission_user_id'] =
            $identity->permissionUserIdForUser($userId);
        if ($result['permission_user_id'] === null) {
            $result['code'] = 'permission_identity_not_resolved';
            return $result;
        }

        $legacyUser = $this->getObject('user', 'security');
        $result['identity_adapter_match'] =
            ((string) $legacyUser->userId() === (string) $userId);
        $result['legacy_is_admin'] = (bool) $legacyUser->isAdmin();

        $groups = $this->getObject('groupservice', 'groupadmin');
        foreach ($groups->listGroups() as $group) {
            if (!is_array($group) || !isset($group['id'], $group['storedName'])) {
                continue;
            }
            if ($groups->isGroupMember($userId, $group['id'])) {
                $result['groups'][] = array(
                    'id' => $group['id'],
                    'name' => $group['storedName'],
                );
            }
        }

        $siteAdminId = $groups->groupIdForName('Site Admin');
        $result['site_admin_group_id'] = $siteAdminId;
        if (is_int($siteAdminId) && $siteAdminId > 0) {
            $result['canonical_site_admin_member'] =
                $groups->isGroupMember($userId, $siteAdminId);
            $legacyGroups = $this->getObject('groupadminmodel', 'groupadmin');
            $result['adapter_site_admin_member'] =
                (bool) $legacyGroups->isGroupMember($userId, $siteAdminId);
            $result['membership_adapter_match'] =
                ($result['canonical_site_admin_member']
                    === $result['adapter_site_admin_member']);
        }

        $permissions = $this->getObject('permissionservice', 'security');
        $areaId = $permissions->areaIdForName('chisimba', 'modulecatalogue');
        $result['area_id'] = $areaId;
        if ($areaId !== null) {
            $rightId = $permissions->rightIdForArea($areaId, 'toolbar_access');
            $result['right_id'] = $rightId;
            if ($rightId !== null) {
                $result['modulecatalogue_toolbar_access'] =
                    $permissions->isGranted($userId, $rightId);
            }
        }

        $result['ok'] =
            $result['identity_adapter_match']
            && $result['membership_adapter_match']
            && $result['area_id'] !== null
            && $result['right_id'] !== null;
        $result['code'] = $result['ok']
            ? 'canonical_chain_resolved'
            : 'canonical_chain_incomplete';
        return $result;
    }
}
?>
