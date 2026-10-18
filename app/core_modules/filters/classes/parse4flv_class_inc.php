<?php

/**
 * Class to parse a string (e.g. page content) that contains a link
 * to a flv (Flash video file) and render the video in the page, YouTube! style
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
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott & Derek Keats 
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License 
 * @version   $Id$
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

class parse4flv extends ChisimbaObject
{
    
    /**
     * init
     * 
     * Standard Chisimba init function
     * 
     * @return void  
     * @access public
     */
    function init()
    {
        // Media rendering is resolved lazily; no mutable parser state.
    }
    
    /**
    *
    * Method to parse the string
    * @param  String $str The string to parse
    * @return The    parsed string
    *                
    */
    public function parse($txt)
    {
        $txt = preg_replace_callback('~\[FLV\](.*?)\[/FLV\]~s',
            fn($m) => $this->getVideoObject(trim($m[1])), $txt);
        return preg_replace_callback('~\[FLV:(.*?)\]~s', function ($m) {
            $params = [];
            foreach (explode(',', $m[1]) as $pair) {
                $parts = explode('=', trim($pair), 2);
                if (count($parts) === 2) $params[trim($parts[0])] = trim($parts[1], " \t\n\r\0\x0B\"'");
            }
            if (empty($params['url'])) return $m[0];
            return $this->getVideoObject($params['url'], $params['width'] ?? 320, $params['height'] ?? 240);
        }, $txt);
    }
    
    /**
     * 
     * Method to build the flv video object code
     * @param  string  $videoId The id of the flv video
     * @return String  The object code
     * @access private
     *                 
     */
    private function getVideoObject($videoId, $width=320, $height=240)
    {
        return $this->getObject('nativeplayer', 'files')->video($videoId, $width, $height);
    }
    
    /**
     * 
     * Method to set up the parameter / value pairs for th efilter
     * @access public
     * @return VOID
     * 
     */
    public function setUpPage()
    {
        if (isset($this->objExpar->url)) {
            $this->id = $this->objExpar->url;
        } else {
            $this->id=NULL;
        }
        
        if (isset($this->objExpar->width)) {
            $this->id = $this->objExpar->width;
        } else {
            $this->id=NULL;
        }
        
        
    }
    
}
?>