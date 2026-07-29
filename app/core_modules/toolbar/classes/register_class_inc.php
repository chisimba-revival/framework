<?php
/**
 * Canonical dynamic-navigation registration.
 *
 * @package toolbar
 * @author Derek Keats
 * @copyright 2026 Derek Keats
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class register extends ChisimbaObject
{
    public function init()
    {
        $this->objFileReader = $this->getObject(
            'modulefile',
            'modulecatalogue'
        );
        $this->objModules = $this->getObject(
            'modules',
            'modulecatalogue'
        );
        $this->objModulesAdmin = $this->getObject(
            'modulesadmin',
            'modulecatalogue'
        );
        $this->objDbMenu = $this->getObject('dbmenu');
        $this->objPermissionService = $this->getObject(
            'permissionservice',
            'security'
        );
        $this->objGroupService = $this->getObject(
            'groupservice',
            'groupadmin'
        );
    }

    public function updateMenus()
    {
        $this->objModulesAdmin->executeModSQL(
            'TRUNCATE TABLE tbl_menu_category'
        );
        $modules = $this->objModules->getModules(2);
        foreach ($modules as $module) {
            $this->restoreDefaults($module['module_id']);
        }
    }

    public function setDefaultPermissions($module)
    {
        $data = $this->getModuleData($module);
        return $this->canonicalRightForRegistration(
            $data,
            $module,
            'default'
        );
    }

    public function updatePermissions()
    {
        $modules = $this->objModules->getModules(2);
        foreach ($modules as $module) {
            $this->setDefaultPermissions($module['module_id']);
        }
    }

    public function getModuleData($module)
    {
        $filepath = $this->objFileReader->findRegisterFile($module);
        return $this->objFileReader->readRegisterFile($filepath, false);
    }

    public function restoreDefaults($module)
    {
        $this->readData($this->getModuleData($module));
    }

    public function restoreDefaultPerms($module)
    {
        return $this->canonicalRightForRegistration(
            $this->getModuleData($module),
            $module,
            'default'
        );
    }

    /**
     * Return canonical registration information for administration views.
     */
    public function readModuleData($module)
    {
        $data = $this->getModuleData($module);
        return array(
            'rightId' => $this->canonicalRightForRegistration(
                $data,
                $module,
                'default'
            ),
            'contextRoles' => $this->contextRoles($data),
            'groups' => $this->siteGroups($data),
        );
    }

    public function readData($regData)
    {
        if (!is_array($regData) || empty($regData['MODULE_ID'])) {
            throw new RuntimeException('Invalid toolbar registration data');
        }

        $moduleId = $regData['MODULE_ID'];
        $isAdmin = isset($regData['MODULE_ISADMIN'])
            ? (int) $regData['MODULE_ISADMIN']
            : 0;
        $isContext = isset($regData['DEPENDS_CONTEXT'])
            ? (int) $regData['DEPENDS_CONTEXT']
            : 0;
        $defaultRight = $this->canonicalRightForRegistration(
            $regData,
            $moduleId,
            'default'
        );

        if (!empty($regData['MENU_CATEGORY'])) {
            foreach ($regData['MENU_CATEGORY'] as $category) {
                $this->sql(
                    strtolower($category),
                    $moduleId,
                    $isAdmin,
                    $defaultRight,
                    $isContext
                );
            }
        }

        if (!empty($regData['SIDEMENU'])) {
            foreach ($regData['SIDEMENU'] as $declaration) {
                list($category, $access) = $this->splitMenuDeclaration(
                    $declaration
                );
                $rightId = $access === array()
                    ? $defaultRight
                    : $this->canonicalRightForAccessList(
                        $moduleId,
                        'side:' . $category,
                        $access
                    );
                $this->sql(
                    'menu_' . strtolower($category),
                    $moduleId,
                    $access === array() ? $isAdmin : 0,
                    $rightId,
                    $isContext
                );
            }
        }

        if (!empty($regData['PAGE'])) {
            foreach ($regData['PAGE'] as $page) {
                $admin = stripos($page, 'admin') !== false ? 1 : 0;
                if (stripos($page, 'lecturer') !== false) {
                    $admin = 0;
                }
                $this->sql(
                    'page_' . $page,
                    $moduleId,
                    $admin,
                    $defaultRight,
                    $isContext
                );
            }
        }
    }

    /**
     * Define the one default toolbar-access right for a module.
     *
     * Empty declarations are explicitly public and return an empty marker.
     */
    public function canonicalRightForRegistration(
        $regData,
        $moduleId,
        $scope = 'default'
    ) {
        $access = array();
        if (!empty($regData['ACL'])) {
            foreach ($regData['ACL'] as $declaration) {
                $parts = explode('|', $declaration, 2);
                if (!empty($parts[0])) {
                    $access[] = 'acl_' . trim($parts[0]);
                }
                if (!empty($parts[1])) {
                    foreach (explode(',', $parts[1]) as $group) {
                        $access[] = trim($group);
                    }
                }
            }
        }
        foreach ($this->siteGroups($regData) as $group) {
            $access[] = $group;
        }
        foreach ($this->contextRoles($regData) as $role) {
            $access[] = 'con_' . $role;
        }

        return $this->canonicalRightForAccessList(
            $moduleId,
            $scope,
            $access
        );
    }

    /**
     * Convert one declaration into a canonical right and exact grants.
     */
    public function canonicalRightForAccessList(
        $moduleId,
        $scope,
        $access
    ) {
        $moduleId = trim((string) $moduleId);
        if ($moduleId === '' || !is_array($access)) {
            throw new RuntimeException('Invalid toolbar permission declaration');
        }

        $siteGroups = array();
        $contextRoles = array();
        $permissionNames = array();
        foreach ($access as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            if (strtolower($value) === 'site') {
                return '';
            }
            if (strpos($value, 'con_') === 0) {
                $contextRoles[] = substr($value, 4);
            } elseif (strpos($value, 'acl_') === 0) {
                $permissionNames[] = substr($value, 4);
            } else {
                $siteGroups[] = $value;
            }
        }
        if ($siteGroups === array()
            && $contextRoles === array()
            && $permissionNames === array()) {
            return '';
        }

        $areaId = $this->objPermissionService->ensureArea(
            'chisimba',
            $moduleId
        );
        $administratorGroupId = $this->objGroupService->groupIdForName(
            'Site Admin'
        );
        $rightName = $scope === 'default'
            ? 'toolbar_access'
            : 'toolbar_' . substr(sha1($scope), 0, 20);
        $rightId = $this->objPermissionService->ensureRight(
            $areaId,
            $rightName,
            $administratorGroupId
        );
        if (!is_int($rightId) || $rightId < 1) {
            throw new RuntimeException('Canonical toolbar right creation failed');
        }

        foreach (array_unique($siteGroups) as $groupName) {
            $groupId = $this->ensureSiteGroup($groupName, $moduleId);
            if (!$this->objPermissionService->ensureGroupGrant(
                $groupId,
                $rightId
            )) {
                throw new RuntimeException(
                    'Canonical toolbar group grant failed'
                );
            }
        }
        foreach (array_unique($contextRoles) as $roleName) {
            if (!$this->objPermissionService
                ->ensureContextRoleGrantTemplate($rightId, $roleName)) {
                throw new RuntimeException(
                    'Canonical toolbar context grant failed'
                );
            }
        }

        /*
         * ACL names identify the canonical module right but are not a second
         * grant mechanism. Their group declarations above determine grants.
         */
        return $rightId;
    }

    private function ensureSiteGroup($groupName, $moduleId)
    {
        $groupName = trim((string) $groupName);
        $result = $this->objGroupService->ensureGroups(array(array(
            'name' => $groupName,
            'description' => $moduleId . ' ' . $groupName,
        )));
        if (!is_array($result)
            || empty($result['ok'])
            || empty($result['groups'][$groupName])) {
            throw new RuntimeException('Canonical toolbar group creation failed');
        }
        return (int) $result['groups'][$groupName];
    }

    private function siteGroups($regData)
    {
        $groups = array();
        if (!empty($regData['USE_GROUPS'])) {
            foreach ($regData['USE_GROUPS'] as $declaration) {
                $parts = explode('|', $declaration, 2);
                if (trim($parts[0]) !== '') {
                    $groups[] = trim($parts[0]);
                }
            }
        }
        return $groups;
    }

    private function contextRoles($regData)
    {
        $roles = array();
        if (!empty($regData['USE_CONTEXT_GROUPS'])) {
            foreach ($regData['USE_CONTEXT_GROUPS'] as $declaration) {
                foreach (explode(',', $declaration) as $role) {
                    if (trim($role) !== '') {
                        $roles[] = trim($role);
                    }
                }
            }
        }
        return $roles;
    }

    private function splitMenuDeclaration($declaration)
    {
        $parts = explode('|', $declaration, 2);
        $category = strtolower(rtrim($parts[0], '|'));
        $access = isset($parts[1])
            ? array_filter(array_map('trim', explode(',', $parts[1])))
            : array();
        return array($category, array_values($access));
    }

    private function sql($category, $module, $admin, $rightId, $context)
    {
        if ($rightId !== '' && (!is_int($rightId) || $rightId < 1)) {
            throw new RuntimeException('Invalid canonical toolbar right');
        }
        $fields = array(
            'category' => $category,
            'module' => $module,
            'adminonly' => (int) $admin,
            'permissions' => $rightId === '' ? '' : (string) $rightId,
            'dependscontext' => (int) $context,
        );
        $this->objDbMenu->saveLinks($fields);
    }
}
?>
