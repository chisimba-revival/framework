<?php

/**
 * alertbox_class_inc.php
 *
 * Facebox
 * This generates Facebook-style lightbox which can display images, divs, or entire remote pages.
 *
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
 * @package   htmlelements
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

/**
* Alertbox
*
* This generates Facebook-style lightbox which can display images, divs, or entire remote pages.
*
* See: http://famspam.com/facebox
*
*
* @author Tohir Solomons
* @category  Chisimba
* @package   htmlelements
* @author    Tohir Solomons <tsolomons@uwc.ac.za>
* @copyright 2007 AVOIR
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   $Id$
* @link      http://famspam.com/facebox/
* @link      http://avoir.uwc.ac.za
*/
class alertbox extends ChisimbaObject
{

    /**
    * Class Constructor
    */
    public function init()
    {
        $this->loadClass('link', 'htmlelements');
    }

    /**
    * Compatibility no-op for callers of the retired legacy loader
    *
    * @return VOID
    * @access public
    */
    public function putJs()
    {
        // Retained as a no-op for old callers; show() loads native previews as needed.
    }

    /**
     *
     * Render a native local-fragment preview or an ordinary web link
     *
     * @param string $text The text for the link
     * @param string $link The URL to open in facebox
     * @return string The formatted link
     *
     */
    public function show($text, $link)
    {
        $url = trim((string) $link);
        // Allow web/relative links and local fragments, never executable schemes.
        if (preg_match('/[\x00-\x20\x7f]/', $url)
            || (preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $scheme)
                && !in_array(strtolower($scheme[1]), ['http', 'https'], true))) {
            return $text;
        }
        $anchor = new link($url);
        $anchor->link = $text;
        if (str_starts_with($url, '#') && strlen($url) > 1) {
            $close = $this->getObject('language', 'language')->languageText('mod_htmlelements_closepreview', 'htmlelements');
            $anchor->extra = 'data-native-alert data-close-label="'
                . htmlspecialchars($close, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            $this->appendArrayVar('headerParams', $this->getJavaScriptFile('alertbox/native-alert.js', 'htmlelements'));
        }
        // Remote content follows normal navigation and its existing access checks.
        // Do not fetch and inject arbitrary pages into the current document.
        return $anchor->show();
    }
}
?>
