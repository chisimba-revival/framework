<?php
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run'])
{
    die("You cannot view this page directly");
}

/**
 * Class to parse a string (e.g. page content) that contains a link
 * to a dynamic form. i.e. the forms created in the Form Builder module (forms)
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
 * @author    Charl Mert <cmert@uwc.ac.za>
 * @copyright 2007 Paul Scott & Derek Keats 
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License 
 * @version   $Id: parse4formbuilder_class_inc.php 11052 2008-10-25 16:04:14Z charlvn $
 * @link      http://avoir.uwc.ac.za
 * @see       
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
 * to a flv (Flash video file) and render the video in the page, YouTube! style
 *
 * @author    Paul Scott
 * @package   filters
 * @access    public
 * @copyright AVOIR GNU/GPL
 *
 */

class parse4form extends ChisimbaObject
{
    /** Legacy Forms is paused. Never load its repository or render stored HTML. */
    public function init() {}

    /** Preserve authored text so a future reviewed migration can recover it. */
    public function parse($txt) { return $txt; }

    public function setUpPage() {}
}
