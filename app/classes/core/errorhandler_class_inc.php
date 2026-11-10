<?php

/**
 * Native error message adapter
 * 
 * This file contains the native errorhandler message adapter
 * 
 * PHP version 8
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
 * @package   core
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */

/**
 * Preserve the historical message adapter without loading or extending PEAR.
 * Already-created legacy errors remain readable for installations using MDB2.
 */
class errorhandler
{
    public $error;
    public $handleErr;

    public function __construct($errstr, $errno, $object)
    {
        $this->error = ($object instanceof Throwable || is_a($object, 'PEAR_Error'))
            ? $object->getMessage() : $errstr;
    }

    public function handleError()
    {
        return $this->error;
    }
}
