<?php

/**
* Class to parse a string (e.g. page content) that contains a Wiki
* item from the local wiki, and return the content inside the Chisimba
* page
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
* @package   filters
* @author    Derek Keats <dkeats@uwc.ac.za>
* @copyright 2007 Derek Keats
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   $Id: parse4wiki_class_inc.php 3123 2007-11-27 13:01:19Z dkeats $
* @link      http://avoir.uwc.ac.za
*/
// security check - must be included in all scripts
if (!
/**
 * Description for $GLOBALS
 * @global string $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run'])
{
    die("You cannot view this page directly");
}
// end security check


/**
*
* Class to parse a string (e.g. page content) that contains a link
* to a trac Wiki page and render the desired content.
*
* @author Paul Scott <pscott@uwc.ac.za>
*
*/

/** Retired remote XML-RPC filter. Keep stored markup intact for editors/imports.
 * No network request, cache write or service bootstrap is performed.
 */
class parse4tracwiki extends ChisimbaObject
{
    public function init() {}
    public function parse($txt) { return $txt; }
}
