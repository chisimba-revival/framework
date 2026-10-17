<?php

/**
 *
 * BLOG filter for inserting blog posts from other sites or users.
 *
 * This filter uses jQuery to dynamically load blog posts from another
 * server using a filter of the type
 *   [BLOG: server=server/path/to/chisimba, postid=somelongstring]
 * and inserts it at the point of the filter in the text of the current
 * content.
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
 * @package   filters
 * @author    Derek Keats <dkeats@uwc.ac.za>
 * @copyright 2007 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License 
 * @version   $Id: parse4ss_class_inc.php 12758 2009-03-06 20:14:13Z dkeats $
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
 * BLOG filter for inserting blog posts from other sites or users.
 *
 * This filter uses jQuery to dynamically load blog posts from another
 * server using a filter of the type
 *   [BLOG: server=server/path/to/chisimba, postid=somelongstring]
 * and inserts it at the point of the filter in the text of the current
 * content.
 *
 * @author Derek Keats
 *
 */

/** Retired remote Blog loader. Preserve stored markup without scripts or requests.
 * The old Blog publisher is paused; SimpleBlog is a separate supported module.
 */
class parse4blog extends ChisimbaObject
{
    public function init() {}
    public function parse($str) { return $str; }
}
