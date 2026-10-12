<?php

/**
 * Class to parse a string (e.g. page content) that contains a Chisimba filter
 * of the form [SKETCHCAST: id=55016, width=WIDTH, height=HEIGHT]
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
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       
 */
/**
*
* Class to parse a string (e.g. page content) that contains a Chisimba filter
* of the form [SKETCHCAST id=55016, width=WIDTH, height=HEIGHT]
*
* @author Derek Keats
*         
*/

class parse4sketchcast extends ChisimbaObject
{
    /** Retired Flash-only integration. Preserve stored shortcodes for replacement. */
    public function init() {}
    public function parse($text) { return $text; }
}
