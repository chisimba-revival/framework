<?php
/**
 * Canonical toolbar administration controller.
 *
 * @category  Chisimba
 * @package   toolbar
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class toolbar extends controller
{
    const CSRF_CONTEXT = 'toolbar_admin_mutation';

    public function init()
    {
        $this->objPage = $this->getObject('page');
        $this->objRegister = $this->getObject('register');
        $this->objDbMenu = $this->getObject('dbmenu');
        $this->objModules = $this->getObject('modules', 'modulecatalogue');
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objSecurity = $this->getObject('toolbarsecuritycontext');
        $this->objPermissionService = $this->getObject(
            'permissionservice',
            'security'
        );
        $stack = $this->getObject(
            'nativeauthwebcomposition',
            'security'
        )->build();
        $this->csrf = $stack['csrf'];
        $this->context = $this->objContext->isInContext();
    }

    public function dispatch($action)
    {
        $this->setVar('pageSuppressXML', true);
        $this->requireAdministrator();

        switch ($action) {
            case 'editlinks':
                return $this->editLinks(
                    $this->moduleId($this->getParam('modulename', 'toolbar'))
                );
            case 'addtool':
                return $this->prepareToolForm(false);
            case 'edittool':
                return $this->prepareToolForm(true);
            case 'addmenu':
                return $this->prepareMenuForm(false, false);
            case 'editmenu':
                return $this->prepareMenuForm(true, false);
            case 'addpage':
                return $this->prepareMenuForm(false, true);
            case 'editpage':
                return $this->prepareMenuForm(true, true);
            case 'savetool':
                $this->requireMutation();
                return $this->saveToolLink();
            case 'savemenu':
                $this->requireMutation();
                return $this->saveMenuLink(false);
            case 'savepage':
                $this->requireMutation();
                return $this->saveMenuLink(true);
            case 'delete':
                $this->requireMutation();
                return $this->deleteLink();
            case 'restore':
                $this->requireMutation();
                return $this->restoreDefaults();
            case 'updatemenus':
                $this->requireMutation();
                $this->objRegister->updateMenus();
                return $this->nextAction('');
            case 'updateperms':
                $this->requireMutation();
                $this->objRegister->updatePermissions();
                return $this->nextAction('');
            default:
                $modules = $this->objPage->getPage(
                    'admin',
                    $this->context
                );
                $this->setVarByRef('modules', $modules);
                return 'admin_tpl.php';
        }
    }

    private function requireAdministrator()
    {
        if (!$this->objSecurity->isAuthenticated()
            || !$this->objSecurity->isSiteAdministrator()) {
            throw new RuntimeException('Toolbar administration denied');
        }
    }

    private function requireMutation()
    {
        if (strtoupper(isset($_SERVER['REQUEST_METHOD'])
            ? $_SERVER['REQUEST_METHOD']
            : '') !== 'POST') {
            throw new RuntimeException('Toolbar mutations require POST');
        }
        $token = $this->getParam('toolbar_csrf', '');
        if (!$this->csrf->consume(self::CSRF_CONTEXT, $token)) {
            throw new RuntimeException('Invalid toolbar CSRF token');
        }
    }

    private function prepareToolForm($editing)
    {
        $module = $this->moduleId($this->getParam('modulename', ''));
        $data = null;
        if ($editing) {
            $data = $this->requiredOwnedLink($module);
            $module = $data['module'];
        }
        $moduleData = $editing
            ? array()
            : $this->objRegister->readModuleData($module);
        $this->setVarByRef('modData', $moduleData);
        $this->setVarByRef('data', $data);
        $this->setVarByRef('moduleName', $module);
        $this->setVar('mode', $editing ? 'edit' : 'add');
        $this->prepareFormSecurity($module, $data);
        return 'addtool_tpl.php';
    }

    private function prepareMenuForm($editing, $page)
    {
        $module = $this->moduleId($this->getParam('modulename', ''));
        $data = null;
        if ($editing) {
            $data = $this->requiredOwnedLink($module);
            $module = $data['module'];
        }
        $moduleData = $editing
            ? array()
            : $this->objRegister->readModuleData($module);
        $this->setVarByRef('modData', $moduleData);
        $this->setVarByRef('data', $data);
        $this->setVarByRef('moduleName', $module);
        $this->setVar('page', $page);
        $this->setVar('mode', $editing ? 'edit' : 'add');
        $this->prepareFormSecurity($module, $data);
        return 'addmenu_tpl.php';
    }

    private function prepareFormSecurity($module, $data)
    {
        $rights = $this->rightsForModule($module);
        $selected = is_array($data) && isset($data['permissions'])
            ? (string) $data['permissions']
            : '';
        if (!$this->validRightSelection($selected, $rights)) {
            $selected = '';
        }
        $token = $this->csrf->issue(self::CSRF_CONTEXT);
        $this->setVarByRef('rightOptions', $rights);
        $this->setVarByRef('selectedRight', $selected);
        $this->setVarByRef('toolbarCsrf', $token);
    }

    private function editLinks($module)
    {
        $data = $this->objDbMenu->linksForModule($module);
        $moduleList = $this->objModules->getAll(
            'WHERE isVisible = 1 ORDER BY module_id'
        );
        $token = $this->csrf->issue(self::CSRF_CONTEXT);
        $this->setVarByRef('moduleList', $moduleList);
        $this->setVarByRef('moduleName', $module);
        $this->setVarByRef('data', $data);
        $this->setVarByRef('toolbarCsrf', $token);
        return 'editlinks_tpl.php';
    }

    private function saveToolLink()
    {
        $module = $this->moduleId($this->getParam('moduleName', ''));
        if ($this->isBackRequest()) {
            return $this->nextAction(
                'editlinks',
                array('modulename' => $module)
            );
        }
        $allowed = array(
            'organise', 'communicate', 'learn', 'admin', 'about',
            'postgrad', 'user', 'course', 'assessment', 'site', 'sems',
        );
        $category = $this->enumValue(
            $this->getParam('category', ''),
            $allowed
        );
        return $this->save($module, $category);
    }

    private function saveMenuLink($page)
    {
        $module = $this->moduleId($this->getParam('moduleName', ''));
        if ($this->isBackRequest()) {
            return $this->nextAction(
                'editlinks',
                array('modulename' => $module)
            );
        }
        if ($page) {
            $menu = $this->enumValue(
                $this->getParam('menu', ''),
                array('lecturer', 'admin', 'manage')
            );
            $position = $this->enumValue(
                $this->getParam('position', ''),
                array('users', 'content', 'organise', 'site', 'develop', 'assign')
            );
            $category = 'page_' . $menu . '_' . $position;
            $category .= '|' . $this->identifier('actionName', true);
            $category .= '|' . $this->identifier('icon', true);
            $category .= '|' . $this->languageCode();
        } else {
            $menu = $this->enumValue(
                $this->getParam('menu', ''),
                array('user', 'alumni', 'context', 'postlogin', 'postgrad')
            );
            $position = $this->enumValue(
                $this->getParam('position', ''),
                array('1', '2', '3', '4', '5')
            );
            $category = 'menu_' . $menu . '-' . $position;
            $category .= '||' . $this->identifier('actionName', true);
            $category .= '|' . $this->identifier('icon', true);
            $category .= '|' . $this->languageCode();
        }
        return $this->save($module, $category);
    }

    private function save($module, $category)
    {
        $id = $this->optionalRecordId($this->getParam('id', ''));
        if ($id !== null) {
            $existing = $this->objDbMenu->getLinkById($id);
            if (!is_array($existing)
                || !isset($existing['module'])
                || $existing['module'] !== $module) {
                throw new RuntimeException('Toolbar link ownership mismatch');
            }
        }
        $right = trim((string) $this->getParam('permissions', ''));
        $rights = $this->rightsForModule($module);
        if (!$this->validRightSelection($right, $rights)) {
            throw new RuntimeException('Invalid canonical toolbar right');
        }
        $fields = array(
            'category' => $category,
            'module' => $module,
            'adminOnly' => $this->booleanParam('adminOnly'),
            'permissions' => $right,
            'dependsContext' => $this->booleanParam('dependsContext'),
        );
        $this->objDbMenu->saveLinks($fields, $id);
        return $this->nextAction(
            'editlinks',
            array('modulename' => $module)
        );
    }

    private function deleteLink()
    {
        $module = $this->moduleId($this->getParam('modulename', ''));
        $id = $this->recordId($this->getParam('id', ''));
        $row = $this->objDbMenu->getLinkById($id);
        if (!is_array($row)
            || !isset($row['module'])
            || $row['module'] !== $module) {
            throw new RuntimeException('Toolbar link ownership mismatch');
        }
        if (!$this->objDbMenu->deleteLinkById($id)) {
            throw new RuntimeException('Toolbar link deletion failed');
        }
        return $this->nextAction(
            'editlinks',
            array('modulename' => $module)
        );
    }

    private function restoreDefaults()
    {
        $module = $this->moduleId($this->getParam('modulename', ''));
        if (!$this->objDbMenu->deleteLinksForModule($module)) {
            throw new RuntimeException('Toolbar default restore failed');
        }
        $this->objRegister->restoreDefaults($module);
        return $this->nextAction(
            'editlinks',
            array('modulename' => $module)
        );
    }

    private function requiredOwnedLink($module)
    {
        $id = $this->recordId($this->getParam('id', ''));
        $row = $this->objDbMenu->getLinkById($id);
        if (!is_array($row)) {
            throw new RuntimeException('Toolbar link not found');
        }
        if ($module !== '' && isset($row['module'])
            && $row['module'] !== $module) {
            throw new RuntimeException('Toolbar link ownership mismatch');
        }
        return $row;
    }

    private function rightsForModule($module)
    {
        $areaId = $this->objPermissionService->areaIdForName(
            'chisimba',
            $module
        );
        return $areaId === null
            ? array()
            : $this->objPermissionService->rightsForArea($areaId);
    }

    private function validRightSelection($right, array $rights)
    {
        if ($right === '') {
            return true;
        }
        if (!preg_match('/^[1-9][0-9]*$/', $right)) {
            return false;
        }
        foreach ($rights as $candidate) {
            if (isset($candidate['rightId'])
                && (string) $candidate['rightId'] === $right) {
                return true;
            }
        }
        return false;
    }

    private function moduleId($value)
    {
        $value = strtolower(trim((string) $value));
        if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $value)) {
            throw new InvalidArgumentException('Invalid module identifier');
        }
        return $value;
    }

    private function recordId($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $value)) {
            throw new InvalidArgumentException('Invalid toolbar record ID');
        }
        return $value;
    }

    private function optionalRecordId($value)
    {
        return trim((string) $value) === '' ? null : $this->recordId($value);
    }

    private function enumValue($value, array $allowed)
    {
        $value = strtolower(trim((string) $value));
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException('Invalid toolbar option');
        }
        return $value;
    }

    private function identifier($name, $allowEmpty)
    {
        $value = trim((string) $this->getParam($name, ''));
        if ($allowEmpty && $value === '') {
            return '';
        }
        if (!preg_match('/^[a-zA-Z0-9_.-]{1,80}$/', $value)) {
            throw new InvalidArgumentException('Invalid toolbar identifier');
        }
        return $value;
    }

    private function languageCode()
    {
        $value = trim((string) $this->getParam('code', ''));
        if (!preg_match('/^[a-zA-Z0-9_.-]{1,120}$/', $value)) {
            throw new InvalidArgumentException('Invalid language code');
        }
        return $value;
    }

    private function booleanParam($name)
    {
        return $this->getParam($name, '') === '' ? 0 : 1;
    }

    private function isBackRequest()
    {
        return $this->getParam('save', '') === $this->objLanguage
            ->languageText('word_back', 'security', 'Back');
    }
}
?>
