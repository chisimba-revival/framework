<?php
/**
 * Render enabled course plugins in the course control panel.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2008 Tohir Solomons
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

/**
 * Course Plugins control-panel block.
 *
 * @category Chisimba
 * @package  context
 * @author   Derek Keats <derek@dkeats.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class block_contextmodules extends ChisimbaObject
{
    /** @var object Course repository. */
    public $objContext;

    /** @var object Course-plugin repository. */
    public $objContextModules;

    /** @var object Module catalogue service. */
    public $objModules;

    /** @var object Language service. */
    public $objLanguage;

    /** @var string Active course code. */
    public $contextCode;

    /**
     * Load services required by the block.
     *
     * @return void
     */
    public function init()
    {
        $this->objContext = $this->getObject('dbcontext');
        $this->contextCode = $this->objContext->getContextCode();
        $this->objContextModules = $this->getObject('dbcontextmodules');
        $this->objModules = $this->getObject('modules', 'modulecatalogue');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->title = $this->objLanguage->code2Txt(
            'mod_context_plugins',
            'context',
            array('plugins' => 'plugins'),
            '[-plugins-]'
        );
    }

    /**
     * Render enabled course plugins with their registered semantic icons.
     *
     * @return string HTML fragment.
     */
    public function show()
    {
        if ($this->contextCode === 'root' || $this->contextCode === '') {
            return '';
        }
        if (!is_array($this->objContext->getContextDetails($this->contextCode))) {
            return '';
        }

        $available = $this->objModules->getContextPlugins();
        $availableCount = is_array($available) ? count($available) : 0;
        $modules = $this->objContextModules->getContextModules(
            $this->contextCode
        );
        $modules = is_array($modules) ? $modules : array();

        if (count($modules) === 0) {
            $content = '<div class="noRecordsMessage">'
                . $this->escape($this->objLanguage->code2Txt(
                    'mod_context_contexthasnopluginsabs',
                    'context',
                    array('plugins' => 'plugins'),
                    'This [-context-] does not have any [-plugins-] enabled'
                )) . '</div>';
        } else {
            $resolver = $this->getObject(
                'moduleiconresolver',
                'modulecatalogue'
            );
            $items = array();
            foreach ($modules as $moduleId) {
                $moduleInfo = $this->objModules->getModuleInfo($moduleId);
                if (!is_array($moduleInfo) || empty($moduleInfo['isreg'])) {
                    continue;
                }
                $moduleTitle = $this->objModules->getModuleTitle($moduleId);
                $url = $this->uri(null, $moduleId);
                $items[] = '<li><a class="course-control-plugin" href="'
                    . $this->escape($url) . '"><span '
                    . 'class="course-control-plugin__icon">'
                    . $resolver->render($moduleId) . '</span><span '
                    . 'class="course-control-plugin__label">'
                    . $this->escape($moduleTitle) . '</span></a></li>';
            }
            $content = '<ul class="course-control-plugins">'
                . implode('', $items) . '</ul>';
        }

        $unused = max(0, $availableCount - count($modules));
        $unusedLabel = $this->objLanguage->code2Txt(
            'mod_context_unusedpluginsabs',
            'context',
            array('plugins' => 'plugins'),
            'Unused [-plugins-]'
        );
        $manageLabel = $this->objLanguage->code2Txt(
            'mod_context_managepluginsabs',
            'context',
            array('plugins' => 'plugins'),
            'Manage [-plugins-]'
        );

        return $content . '<p class="course-control-meta">'
            . $this->escape($unusedLabel) . ': ' . $unused . '</p>'
            . '<p class="course-control-action"><a href="'
            . $this->escape($this->uri(array('action' => 'manageplugins')))
            . '">' . $this->escape($manageLabel) . '</a></p>';
    }

    /**
     * Escape a value for HTML output.
     *
     * @param mixed $value Value to escape.
     *
     * @return string Escaped value.
     */
    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
