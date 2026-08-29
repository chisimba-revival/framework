<?php
/**
 * Verify the modern course-control-panel presentation contract.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */

$root = dirname(__DIR__);
$app = dirname(dirname($root));
$settings = file_get_contents($root . '/classes/block_contextsettings_class_inc.php');
$plugins = file_get_contents($root . '/classes/block_contextmodules_class_inc.php');
$forms = file_get_contents($root . '/classes/contextforms_class_inc.php');
$sidebar = file_get_contents($root . '/classes/contextsidebar_class_inc.php');
$template = file_get_contents($root . '/templates/content/controlpanel_tpl.php');
$controller = file_get_contents($root . '/controller.php');
$journey = file_get_contents($root . '/classes/block_learningjourney_class_inc.php');
$register = file_get_contents($root . '/register.conf');
$reborn = file_get_contents($app . '/skins/chisimba-reborn/stylesheet.css');

$marker = 'CHISIMBA COURSE CONTROL PANEL COMPONENTS';
$checks = array(
    'Context module has release metadata' => preg_match(
        '/^MODULE_VERSION:\s*\d+(?:\.\d+)?$/m',
        $register
    ) === 1,
    'settings use a semantic spaced layout' => str_contains(
        $settings,
        'course-control-settings__details'
    ),
    'control panel exposes lecturer task shortcuts' => str_contains(
        $template,
        'course-control-tasks'
    ) && str_contains($template, "'action' => 'authors'")
        && str_contains($template, "'contextcontent'"),
    'single-page settings and the full wizard remain distinct journeys' => str_contains(
        $controller,
        "return 'editcontextsettings_tpl.php'"
    ) && str_contains($template, "'action' => 'updatesettings'")
        && str_contains($template, "'mod_context_fullcontextedit'")
        && str_contains($template, "'action' => 'edit', 'contextcode' => \$contextCode"),
    'course managers receive a role-aware management journey' => str_contains(
        $journey,
        'isCourseAdmin($contextCode)'
    ) && str_contains($journey, "'mod_context_managerjourneyaction'")
        && str_contains($journey, "array('action' => 'controlpanel')"),
    'task shortcuts use the shared navigation-action primitive' => str_contains(
        $template,
        'chisimba-navigation-action course-control-task'
    ) && str_contains($reborn, 'CHISIMBA NAVIGATION ACTION PRIMITIVE')
        && str_contains($reborn, 'text-decoration: none !important;'),
    'control panel uses a deliberate details grid' => str_contains(
        $template,
        'course-control-details'
    ) && !str_contains($template, '$counter % 2'),
    'task URLs use the shared raw URI encoding contract' => str_contains(
        $template,
        '$escape($task[\'url\'])'
    ) && !str_contains($template, 'html_entity_decode'),
    'settings image has a presentation hook' => str_contains(
        $settings,
        'course-control-settings__image'
    ),
    'plugins use registered module icons' => str_contains(
        $plugins,
        "getObject(\n                'moduleiconresolver'"
    ) && str_contains($plugins, 'resolver->render($moduleId)'),
    'legacy bitmap module icon path is absent' => !str_contains(
        $plugins,
        'setModuleIcon'
    ),
    'plugin heading is capitalized' => str_contains(
        $plugins,
        'ucfirst($this->objLanguage->code2Txt('
    ),
    'settings form supplies the saved image preview' => str_contains(
        $forms,
        'setDefaultPreviewUrl($currentImage)'
    ),
    'normal settings expose every supported course format' => str_contains(
        $forms,
        "new dropdown('delivery_format')"
    ) && str_contains($forms, "'standard'")
        && str_contains($forms, "'microlearning'")
        && str_contains($forms, "'masterclass'")
        && str_contains($forms, 'context-delivery-format-help'),
    'course search uses the shared Lucide icon service' => str_contains(
        $sidebar,
        "render('search'"
    ) && !str_contains($sidebar, 'setIconClass("search")'),
    'student sidebar omits management and assessment indexes' => str_contains(
        $sidebar,
        '$learnerHiddenModules'
    ) && str_contains($sidebar, "'contentblocks'")
        && str_contains($sidebar, "'gradebook'")
        && str_contains($sidebar, "'mcqtests'")
        && str_contains($sidebar, '$mayManageLearning'),
    'plugin links separate icons from labels' => str_contains(
        $plugins,
        'course-control-plugin__label'
    ),
    'shared skin contains the contract' => str_contains($reborn, $marker),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
