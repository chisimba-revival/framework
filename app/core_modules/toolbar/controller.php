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
    // Explicit controller state; avoid PHP dynamic-property deprecations.
    public $objPage, $objRegister, $objDbMenu, $objModules, $objContext, $objLanguage, $objSecurity, $objPermissionService, $context;
    private $csrf;

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
            case 'addcategory':
                return $this->categoryForm();
            case 'editcategory':
                return $this->categoryForm($this->recordId($this->getParam('id', '')));
            case 'savecategory':
                return $this->saveCategory();
            case 'deletecategory':
                $this->requireMutation();
                try {
                    $this->getObject('navigationcategories', 'toolbar')->removeCategory($this->recordId($this->getParam('id', '')));
                } catch (InvalidArgumentException $error) {
                    $this->setVar('categoryError', $error->getMessage());
                    return $this->editLinks('toolbar');
                }
                return $this->nextAction('editlinks');
            case 'addexternal':
                return $this->externalForm();
            case 'editexternal':
                return $this->externalForm($this->recordId($this->getParam('id','')));
            case 'saveexternal':
                return $this->saveExternal();
            case 'deleteexternal':
                $this->requireMutation();
                $this->getObject('externalnavigation','toolbar')->removeLink($this->recordId($this->getParam('id','')));
                return $this->nextAction('editlinks');
            case 'saveprofile':
                $this->requireMutation();
                $profile = $this->enumValue($this->getParam('profile', ''), array('dropdown', 'site', 'flat', 'elearning'));
                $this->getObject('dbsysconfig', 'sysconfig')->changeParam('TOOLBAR_TYPE', 'toolbar', $profile);
                return $this->nextAction('editlinks', array('modulename' => 'toolbar'));
            case 'addsite':
                if ($this->getParam('modulename', '') === '') return $this->chooseSiteModule();
                return $this->prepareMenuForm(false, false, true);
            case 'editsite':
                return $this->prepareMenuForm(true, false, true);
            case 'savesite':
                try { $this->requireMutation(); }
                catch (RuntimeException $error) {
                    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw $error;
                    return $this->siteFormError('external_expired');
                }
                try { return $this->saveSiteLink(); }
                catch (InvalidArgumentException $error) { return $this->siteFormError('invalid'); }
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
                $contextTitle = $this->context
                    ? $this->objContext->getTitle()
                    : '';
                $this->setVarByRef('modules', $modules);
                $this->setVar('adminInContext', $this->context);
                $this->setVar('adminContextTitle', $contextTitle);
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
        $module = $this->moduleId($this->getParam('modulename', $this->getParam('moduleName', '')));
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

    private function chooseSiteModule()
    {
        $choices = [];
        $defaults = $this->getObject('modulelinkdefaults', 'toolbar');
        foreach ($this->objModules->getAll('WHERE isVisible = 1 ORDER BY module_id') as $module) {
            $choices[$module['module_id']] = $defaults->moduleName($module['module_id']);
        }
        asort($choices, SORT_NATURAL | SORT_FLAG_CASE);
        $this->setVar('siteModuleChoices', $choices);
        return 'module_link_choose_tpl.php';
    }

    private function prepareMenuForm($editing, $page, $site = false)
    {
        $module = $this->moduleId($this->getParam('modulename', $this->getParam('moduleName', '')));
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
        $this->setVar('site', $site);
        if ($site) {
            $this->setVar('siteGroups', $this->getObject('navigationservice', 'toolbar')->editorGroups());
            $defaults = $this->getObject('modulelinkdefaults', 'toolbar');
            $this->setVar('siteModuleTitle', $defaults->moduleName($module));
            $this->setVar('siteDestinations', $defaults->destinations($module, $data));
        }
        $this->setVar('mode', $editing ? 'edit' : 'add');
        $this->prepareFormSecurity($module, $data);
        return $site ? 'module_link_tpl.php' : 'addmenu_tpl.php';
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
        $this->setVar('navigationCategories', $this->getObject('navigationcategories', 'toolbar')->categories());
        $this->setVar('navigationInventory', $this->getObject('navigationservice','toolbar')->editorLinks());
        $this->setVar('toolbarProfile', strtolower((string) $this->getObject('dbsysconfig', 'sysconfig')->getValue('TOOLBAR_TYPE', 'toolbar', 'dropdown')));
        $this->setVarByRef('moduleList', $moduleList);
        $this->setVarByRef('moduleName', $module);
        $this->setVarByRef('data', $data);
        $this->setVarByRef('toolbarCsrf', $token);
        return 'editlinks_tpl.php';
    }

    private function categoryForm($id = null, $submitted = null, $error = false)
    {
        $row = $id === null ? [] : $this->getObject('navigationcategories', 'toolbar')->category($id);
        if ($id !== null && !$row) throw new RuntimeException('Navigation category not found');
        $this->setVar('category', $submitted ?? $row);
        $this->setVar('categoryId', $id ?? '');
        $this->setVar('categoryError', $error);
        $this->setVar('toolbarCsrf', $this->csrf->issue(self::CSRF_CONTEXT));
        return 'navigation_category_tpl.php';
    }

    private function saveCategory()
    {
        $input = [];
        foreach (['label', 'position'] as $key) {
            $value = $this->getParam($key, '');
            $input[$key] = is_string($value) ? $value : '';
        }
        $id = $this->optionalRecordId($this->getParam('id', ''));
        try { $this->requireMutation(); }
        catch (RuntimeException $error) {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw $error;
            return $this->categoryForm($id, $input, 'external_expired');
        }
        try { $this->getObject('navigationcategories', 'toolbar')->saveCategory($input, $id); }
        catch (InvalidArgumentException $error) { return $this->categoryForm($id, $input, $error->getMessage()); }
        return $this->nextAction('editlinks');
    }

    private function externalForm($id = null, $submitted = null, $error = false)
    {
        $row = $id === null ? [] : $this->getObject('externalnavigation', 'toolbar')->link($id);
        if ($id !== null && !$row) throw new RuntimeException('External link not found');
        if ($id === null && $submitted === null) {
            $row['placement'] = $this->getParam('placement', 'site') === 'learning' ? 'learning' : 'site';
        }
        $this->setVar('externalLink', $submitted ?? $row);
        $this->setVar('externalId', $id ?? '');
        $this->setVar('externalError', $error);
        $this->setVar('externalGroups', $this->getObject('navigationservice', 'toolbar')->editorGroups());
        $this->setVar('toolbarCsrf', $this->csrf->issue(self::CSRF_CONTEXT));
        return 'external_link_tpl.php';
    }

    private function saveExternal()
    {
        $input = [];
        foreach (['placement', 'label', 'url', 'group_key', 'new_group', 'position', 'audience'] as $key) {
            $value = $this->getParam($key, $key === 'placement' ? 'site' : '');
            $input[$key] = is_string($value) ? $value : '';
            if ($key === 'audience' && $input[$key] === '' && ($input['placement'] ?? '') === 'learning') $input[$key] = 'members';
        }
        $id = $this->optionalRecordId($this->getParam('id', ''));
        // Keep entered values and issue a new token when a form expires.
        try {
            $this->requireMutation();
        } catch (RuntimeException $error) {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw $error;
            return $this->externalForm($id, $input, 'expired');
        }
        try {
            $this->getObject('externalnavigation', 'toolbar')->saveLink(
                $input, $id
            );
        } catch (InvalidArgumentException $error) {
            return $this->externalForm($id, $input, 'invalid');
        }
        return $this->nextAction('editlinks');
    }

    private function siteFormError($error)
    {
        $values = [];
        foreach (['position','destination','groupCode','menuLabel','adminOnly','dependsContext','permissions'] as $key) {
            $value = $this->getParam($key, '');
            $values[$key] = is_string($value) ? $value : '';
        }
        $this->setVar('siteSubmitted', $values);
        $this->setVar('siteInputError', $error);
        return $this->prepareMenuForm($this->getParam('id', '') !== '', false, true);
    }

    /** Extend the existing editor, ownership checks and permission selection. */
    private function saveSiteLink()
    {
        $module = $this->moduleId($this->getParam('moduleName', ''));
        if ($this->isBackRequest()) return $this->nextAction('editlinks', array('modulename' => $module));
        return $this->getObject('navigationcategories', 'toolbar')->mutate(function () use ($module) {
            $group = $this->getParam('groupCode', '');
            if (!is_string($group)) throw new InvalidArgumentException('Invalid category');
            $order = $this->getObject('navigationservice', 'toolbar')->linkPosition(
                $this->getParam('position', ''), 'site', $group, $this->optionalRecordId($this->getParam('id', ''))
            );
            $id = $this->optionalRecordId($this->getParam('id', ''));
            $existing = $id === null ? null : $this->requiredOwnedLink($module);
            $destinations = $this->getObject('modulelinkdefaults', 'toolbar')->destinations($module, $existing);
            $selected = $this->getParam('destination', $existing === null ? 'default' : 'current');
            if (!is_string($selected) || !isset($destinations[$selected])) throw new InvalidArgumentException('Invalid destination');
            $destination = $destinations[$selected];
            $category = 'site_' . sprintf('%03d', $order) . '||'
                . $destination['action'] . '|' . $destination['icon'] . '|' . $destination['code'] . '|' . $group;
            $arguments = $this->getObject('navigationservice', 'toolbar')->destinationArguments($destination['arguments']);
            if ($arguments) $category .= '|' . http_build_query($arguments, '', '&', PHP_QUERY_RFC3986);
            if (strlen($category) > 120) throw new InvalidArgumentException('Site navigation declaration exceeds 120 characters');
            $label = $this->getParam('menuLabel', '');
            if (!is_string($label) || !preg_match('//u', $label) || mb_strlen($label) > 150 || preg_match('/[\x00-\x1f\x7f]/', $label)) throw new InvalidArgumentException('Invalid menu label');
            return $this->save($module, $category, trim($label));
        });
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

    private function save($module, $category, $menuLabel = null)
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
        if ($menuLabel !== null) $fields['menu_label'] = $menuLabel === '' ? null : $menuLabel;
        if (!$this->objDbMenu->saveLinks($fields, $id)) throw new RuntimeException('Toolbar link save failed');
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
