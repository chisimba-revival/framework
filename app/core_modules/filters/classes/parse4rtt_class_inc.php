<?php

/**
 *
 * Class used to parse [RTT]room name[/RTT] filter into a link to an
 instant virtual classroom
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @link      http://avoir.uwc.ac.za
 */
// security check - must be included in all scripts
if (!
/**
 * Description for $GLOBALS
 * @global string $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check


/**
 *
 * Parse string for filter for displaying a virtual classroom
 *
 * Class to parse a string (e.g. page content) that contains a filter
 * code for including the all files in a user directory as links with descriptions
 * where descriptions exist.
 *
 */
class parse4rtt extends ChisimbaObject
{
    /** Retired Java client; preserve stored shortcodes without loading services. */
    public function init() {}
    public function parse($text) { return $text; }
}
