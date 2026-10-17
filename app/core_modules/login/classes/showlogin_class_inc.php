<?php
/**
 *
 *
 * Render a login box
 *
 * Class to render login box, which can be rendered in a block, or via
 * an Ajax request to provide it in a modal window.
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
 *
 * @category  Chisimba
 * @package   login
 * @author    Multiple contributors
 * @copyright 2011 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @link      http://www.chisimba.com
 *
 */

// security check - must be included in all scripts
if (!
/**
 * The $GLOBALS is an array used to control access to certain constants.
 * Here it is used to check if the file is opening in engine, if not it
 * stops the file from running.
 *
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 *
 */
$GLOBALS['kewl_entry_point_run'])
{
        die("You cannot view this page directly");
}
// end security check

/**
*
 * Render a login box
 *
 * Class to render login box, which can be rendered in a block, or via
 * an Ajax request to provide it in a modal window.
*
* @author Multiple contributors
* @package login
*
*/
class showlogin extends ChisimbaObject
{
    /** Legacy callers share the maintained login form and its security boundary. */
    public function show($module = null, $ajaxLogin = false, $loginType = 'renderLoginBox', $loadScripts = true)
    {
        return $loginType === 'renderLoginAsDropdown'
            ? $this->renderLoginAsDropdown($module)
            : $this->renderLoginBox($module);
    }

    public function renderLoginBox($module = null, $ajaxLogin = false)
    {
        return $this->getObject('logininterface', 'security')->renderLoginBox($module);
    }

    public function renderLoginAsDropdown($module = null, $ajaxLogin = false)
    {
        $user = $this->getObject('user', 'security');
        $language = $this->getObject('language', 'language');
        if ($user->isLoggedIn()) {
            return htmlspecialchars($language->languageText('mod_login_loggedinas', 'login'), ENT_QUOTES, 'UTF-8')
                . ': <em>' . htmlspecialchars($user->fullName(), ENT_QUOTES, 'UTF-8') . '</em>';
        }
        return '<details class="login-disclosure"><summary>'
            . htmlspecialchars($language->languageText('word_login'), ENT_QUOTES, 'UTF-8')
            . '</summary>' . $this->renderLoginBox($module) . '</details>';
    }

    /** Abuse challenges are owned by native authentication, not the old nonce gate. */
    public function renderProveHuman() { return $this->renderLoginBox(); }

    /** Compatibility entry point: the native form needs no login-specific script. */
    public function loadAllScripts() {}
}
