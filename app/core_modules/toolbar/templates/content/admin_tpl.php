<?php
/** Task-oriented Site Administration workbench. */
$this->setLayoutTemplate('admin_layout_tpl.php');

$language = $this->getObject('language', 'language');
$icons = $this->getObject('iconservice', 'ui');
$moduleIcons = $this->getObject('moduleiconresolver', 'modulecatalogue');
$moduleFiles = $this->getObject('modulefile', 'modulecatalogue');
$langArray = array('context' => 'course', 'contexts' => 'courses',
    'author' => 'lecturer', 'authors' => 'lecturers',
    'readonly' => 'student', 'readonlys' => 'students');
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$taskName = static function ($line) use ($language, $langArray) {
    $code = isset($line['name']) && $line['name'] !== ''
        ? $line['name'] : 'mod_' . $line['module'] . '_name';
    return ucfirst($language->code2Txt($code, $line['module'], $langArray));
};
$taskKeywords = static function ($moduleId) use ($moduleFiles) {
    static $cache = array();
    if (isset($cache[$moduleId])) return $cache[$moduleId];
    $path = $moduleFiles->findRegisterFile($moduleId);
    if ($path === false) return $cache[$moduleId] = '';
    $metadata = $moduleFiles->readRegisterFile($path);
    return $cache[$moduleId] = isset($metadata['ADMIN_SEARCH'])
        ? $metadata['ADMIN_SEARCH'] : '';
};
$taskUrl = function ($line) {
    $params = array();
    if (isset($line['action']) && $line['action'] !== '') {
        $params['action'] = $line['action'];
    }
    return $this->uri($params, $line['module']);
};
$taskIcon = static function ($line, $cssClass) use ($icons, $moduleIcons) {
    if (isset($line['icon']) && $line['icon'] !== '') {
        try {
            return $icons->render($line['icon'], array(
                'decorative' => true, 'class' => $cssClass));
        } catch (Throwable $exception) {
            // Invalid legacy declarations fall through to module metadata.
        }
    }
    return $moduleIcons->render($line['module'], '', $cssClass);
};
$renderTask = static function ($line, $category, $compact = false) use (
    $escape, $taskName, $taskUrl, $taskIcon, $taskKeywords, $language
) {
    $name = $taskName($line);
    $search = strtolower($name . ' ' . $line['module'] . ' ' . $category
        . ' ' . $taskKeywords($line['module']));
    $label = $language->code2Txt('mod_toolbar_opentask', 'toolbar',
        array('task' => $name), 'Open [-TASK-]');
    $class = 'admin-workbench-task'
        . ($compact ? ' admin-workbench-task--compact' : '');
    return '<a class="' . $class . '" href="' . $escape($taskUrl($line))
        . '" data-admin-task data-admin-search="' . $escape($search)
        . '" aria-label="' . $escape($label) . '">'
        . '<span class="admin-workbench-task__icon">'
        . $taskIcon($line, 'adminmenu-icon') . '</span>'
        . '<span class="admin-workbench-task__label">' . $escape($name)
        . '</span></a>';
};

$modules = is_array($modules) ? $modules : array();
$commonTasks = isset($modules['common']) ? $modules['common'] : array();
$currentTasks = isset($modules['current']) ? $modules['current'] : array();
unset($modules['common'], $modules['current']);
$categoryOrder = array('courses', 'people', 'shared', 'appearance',
    'system', 'operations', 'advanced');
$orderedModules = array();
foreach ($categoryOrder as $category) {
    if (!empty($modules[$category])) {
        $orderedModules[$category] = $modules[$category];
        unset($modules[$category]);
    }
}
foreach ($modules as $category => $items) {
    if (!empty($items)) $orderedModules[$category] = $items;
}
$primaryModules = array();
$secondaryModules = array();
foreach ($orderedModules as $category => $items) {
    if (in_array($category, array('courses', 'people', 'shared', 'appearance'), true)) {
        $primaryModules[$category] = $items;
    } else {
        $secondaryModules[$category] = $items;
    }
}
$title = $language->languageText('mod_toolbar_siteadmin', 'toolbar',
    'Site Administration');
$intro = $language->languageText('mod_toolbar_adminintro', 'toolbar',
    'Find the task you need without having to know which module provides it.');
$searchLabel = $language->languageText('mod_toolbar_adminsearchlabel',
    'toolbar', 'Search administration');
$searchHint = $language->languageText('mod_toolbar_adminsearchhint',
    'toolbar', 'Try users, email, logo, course owner, permissions or workers.');
$noResults = $language->languageText('mod_toolbar_adminnoresults', 'toolbar',
    'No administration tasks match your search.');
?>
<main class="admin-workbench" data-admin-workbench>
    <header class="admin-workbench-hero">
        <div>
            <h1><?php echo $escape($title); ?></h1>
            <p><?php echo $escape($intro); ?></p>
        </div>
        <div class="admin-workbench-search">
            <label for="admin-workbench-query"><?php echo $escape($searchLabel); ?></label>
            <input id="admin-workbench-query" type="search" autocomplete="off"
                aria-describedby="admin-workbench-search-hint" />
            <p id="admin-workbench-search-hint"><?php echo $escape($searchHint); ?></p>
        </div>
    </header>

    <div class="admin-workbench-layout">
        <div class="admin-workbench-areas" aria-live="polite">
            <?php foreach ($primaryModules as $category => $items): ?>
                <section class="admin-workbench-area" data-admin-area>
                    <h2><?php echo $escape($language->languageText(
                        'mod_toolbar_' . $category, 'toolbar',
                        ucwords(str_replace('_', ' ', $category)))); ?></h2>
                    <div class="admin-workbench-task-grid">
                        <?php foreach ($items as $line) {
                            echo $renderTask($line, $category);
                        } ?>
                    </div>
                </section>
            <?php endforeach; ?>
            <p class="admin-workbench-empty" data-admin-empty hidden>
                <?php echo $escape($noResults); ?>
            </p>
        </div>

        <aside class="admin-workbench-sidebar">
            <?php if (!empty($commonTasks)): ?>
                <section class="admin-workbench-panel" data-admin-area>
                    <h2><?php echo $escape($language->languageText(
                        'mod_toolbar_common', 'toolbar', 'Common tasks')); ?></h2>
                    <div class="admin-workbench-task-list">
                        <?php foreach ($commonTasks as $line) {
                            echo $renderTask($line, 'common', true);
                        } ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($adminInContext && !empty($currentTasks)): ?>
                <section class="admin-workbench-panel admin-workbench-panel--context"
                    data-admin-area>
                    <p class="admin-workbench-panel__eyebrow"><?php echo $escape(
                        $language->code2Txt('mod_toolbar_current', 'toolbar',
                            array('context' => 'Course'), 'Current [-context-]')
                    ); ?></p>
                    <h2><?php echo $escape($adminContextTitle); ?></h2>
                    <div class="admin-workbench-task-list">
                        <?php foreach ($currentTasks as $line) {
                            echo $renderTask($line, 'current', true);
                        } ?>
                        <a class="admin-workbench-task admin-workbench-task--compact"
                            href="<?php echo $escape($this->uri(
                                array('action' => 'leavecontext'), 'context')); ?>"
                            data-admin-task data-admin-search="leave course context">
                            <span class="admin-workbench-task__icon"><?php echo
                                $icons->render('log-out', array('decorative' => true,
                                    'class' => 'adminmenu-icon')); ?></span>
                            <span class="admin-workbench-task__label"><?php echo $escape(
                                $language->code2Txt('mod_toolbar_leavecontext', 'toolbar',
                                    array('context' => 'course'), 'Leave [-context-]')
                            ); ?></span>
                        </a>
                    </div>
                </section>
            <?php endif; ?>
        </aside>
    </div>

    <?php if (!empty($secondaryModules)): ?>
        <div class="admin-workbench-secondary">
            <?php foreach ($secondaryModules as $category => $items): ?>
                <section class="admin-workbench-area" data-admin-area>
                    <h2><?php echo $escape($language->languageText(
                        'mod_toolbar_' . $category, 'toolbar',
                        ucwords(str_replace('_', ' ', $category)))); ?></h2>
                    <div class="admin-workbench-task-grid admin-workbench-task-grid--secondary">
                        <?php foreach ($items as $line) {
                            echo $renderTask($line, $category);
                        } ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<script>
(function () {
    'use strict';
    var root = document.querySelector('[data-admin-workbench]');
    if (!root) return;
    var input = root.querySelector('#admin-workbench-query');
    var tasks = Array.prototype.slice.call(
        root.querySelectorAll('[data-admin-task]'));
    var areas = Array.prototype.slice.call(
        root.querySelectorAll('[data-admin-area]'));
    var empty = root.querySelector('[data-admin-empty]');
    input.addEventListener('input', function () {
        var query = input.value.toLowerCase().trim();
        var visible = 0;
        tasks.forEach(function (task) {
            var matches = query === '' ||
                task.getAttribute('data-admin-search').indexOf(query) !== -1;
            task.hidden = !matches;
            if (matches) visible += 1;
        });
        areas.forEach(function (area) {
            area.hidden = area.querySelector(
                '[data-admin-task]:not([hidden])') === null;
        });
        empty.hidden = visible !== 0;
    });
}());
</script>
