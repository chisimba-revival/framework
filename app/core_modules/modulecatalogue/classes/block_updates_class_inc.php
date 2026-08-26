<?php

/**
 * The patch class is used to read and write module version information,
 *  as well as to apply patches to modules
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
 * @package   modulecatalogue
 * @author    Monwabisi Sifumba <wsifumba@gmail.com>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */
// security check - must be included in all scripts
if (!
        /**
         * Description for $GLOBALS
         * @global unknown $GLOBALS['kewl_entry_point_run']
         * @name   $kewl_entry_point_run
         */
        $GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

class block_updates extends ChisimbaObject {

    /**
     *
     * @var string The block title
     * @access public
     */
    var $title;

    /**
     *
     * @var object The patch object
     * @access public
     */
    var $objPatch;

    private $csrf;

    /**
     * 
     * @access public
     * @return NULL
     */
    function init() {
        $this->objLanguage = $this->getObject('language', 'language');
        $this->title = $this->text('mod_modulecatalogue_updates_block_title', 'Updates');
        $this->objPatch = $this->getObject('patch', 'modulecatalogue');
        $this->objUser = $this->getObject('user', 'security');
        $nativeAuth = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $this->csrf = $nativeAuth['csrf'];
    }

    /**
     * Method to build the block
     * 
     * @access public
     * @return string The block content as string
     */
    public function getUpdates() {
        $escape = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        $modules = $this->objPatch->checkModules();
        if (!$modules) {
            return '<div class="module-updates" data-module-updates>'
                . '<p class="module-updates__empty">'
                . $escape($this->objLanguage->languageText('mod_modulecatalogue_noupdates', 'modulecatalogue'))
                . '</p></div>';
        }

        $objIcon = $this->getObject('geticon', 'htmlelements');
        $updateUrl = html_entity_decode(
            $this->uri(array('action' => 'update'), 'modulecatalogue'),
            ENT_QUOTES,
            'UTF-8'
        );
        $allUrl = html_entity_decode(
            $this->uri(array('action' => 'patchall'), 'modulecatalogue'),
            ENT_QUOTES,
            'UTF-8'
        );
        $html = '<div class="module-updates" data-module-updates'
            . ' data-empty-message="' . $escape($this->objLanguage->languageText('mod_modulecatalogue_noupdates', 'modulecatalogue')) . '"'
            . ' data-updating-message="' . $escape($this->text('mod_modulecatalogue_updating', 'Applying update…')) . '"'
            . ' data-applying-message="' . $escape($this->text('mod_modulecatalogue_applying_all', 'Applying all updates…')) . '"'
            . ' data-request-failed-message="' . $escape($this->text('mod_modulecatalogue_update_request_failed', 'The update result could not be confirmed. Please try again.')) . '">'
            . '<div class="module-updates__list">';
        foreach ($modules as $module) {
            $moduleId = (string) $module['module_id'];
            $objIcon->setModuleIcon($module['module_id']);
            $html .= '<article class="module-updates__item" data-update-item>'
                . '<div class="module-updates__heading"><img class="module-updates__icon" src="'
                . $escape($objIcon->getSrc()) . '" alt=""><strong>' . $escape(ucwords(str_replace('-', ' ', $moduleId))) . '</strong></div>'
                . '<p class="module-updates__description">' . $escape($module['desc']) . '</p>'
                . '<button type="button" class="button chisimba-button-secondary module-updates__action" data-update-module'
                . ' data-url="' . $escape($updateUrl) . '" data-module="' . $escape($moduleId)
                . '" data-version="' . $escape($module['new_version']) . '" data-csrf="'
                . $escape($this->csrf->issue('modulecatalogue_apply_update')) . '">'
                . $escape($this->objLanguage->languageText('phrase_update', 'system')) . ' '
                . $escape($module['old_version']) . ' '
                . $escape($this->objLanguage->languageText('word_to', 'system')) . ' '
                . $escape($module['new_version']) . '</button>'
                . '<p class="module-updates__status" data-update-status role="status" aria-live="polite"></p>'
                . '</article>';
        }
        $html .= '</div>';
        if (count($modules) > 1) {
            $html .= '<div class="module-updates__all"><button type="button" class="button chisimba-button-secondary" data-update-all'
                . ' data-url="' . $escape($allUrl) . '" data-csrf="'
                . $escape($this->csrf->issue('modulecatalogue_apply_all_updates')) . '">'
                . $escape($this->objLanguage->languageText('mod_modulecatalogue_patchall', 'modulecatalogue'))
                . '</button><p class="module-updates__status" data-update-all-status role="status" aria-live="polite"></p></div>';
        }
        return $html . '</div>';
    }

    /**
     * Method to display the form or message
     * 
     * @access public
     * @return string The block and javascript file OR message to non-administrative users
     */
    public function show() {
        if ($this->objUser->isAdmin()) {
            return $this->getUpdates() . $this->getJavascriptFile('updates.js', 'modulecatalogue');
        } else {
            return $this->objLanguage->languageText('mod_modulecatalogue_nonadminmsg', 'modulecatalogue');
        }
    }

    private function text($key, $fallback)
    {
        $value = $this->objLanguage->languageText($key, 'modulecatalogue');
        $value = str_starts_with((string) $value, 'Language item not found:')
            ? $fallback
            : $value;

        // Language records may contain legacy HTML entities. These strings are
        // assigned through textContent, so normalise them to plain UTF-8 before
        // they are safely escaped into data attributes.
        return html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

}

?>
