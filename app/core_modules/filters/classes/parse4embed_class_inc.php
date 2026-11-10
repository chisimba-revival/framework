<?php

/**
 * Class for parsing anchored multimedia links and turning them
 * into embeded media links. This class is based on the similar filters
 * from Moodle. The original author was not identified in the Moodle
 * file.
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
 * @version   $Id: parse4mmedia_class_inc.php 11998 2008-12-29 22:35:37Z charlvn $
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
 * Class for parsing anchored multimedia links and turning them
 * into embeded media links. This class is based on the similar filters
 * from Moodle. The original author was not identified in the Moodle
 * file.
 * 
 * @author    Derek Keats
 * @version   $Id: parse4mmedia_class_inc.php 11998 2008-12-29 22:35:37Z charlvn $
 * @copyright 2003 GPL
 */
class parse4embed extends ChisimbaObject {
    /**
     * Method to take a string and return it with anchor linkd to 
     * quicktime movies changed to embeded media
     * 
     * TEST: passed
     * 
     * @param  string $str The string to be parsed
     * @return the    parsed string
     */
    function parseMov($str)
    {
        return preg_replace_callback('~\[EMBED\]\s*<a\b[^>]*href=["\']([^"\']+\.(?:mov)(?:[?#][^"\']*)?)["\'][^>]*>.*?</a>\s*\[/EMBED\]~is',
            fn($match) => $this->getObject('nativeplayer', 'files')->video($match[1]), $str);
    } # end of function
    
    /**
     * Method to take a string and return it with anchor linkd to 
     * windows media movies changed to embeded media
     * 
     * TEST: passed
     * 
     * @param  string $str The string to be parsed
     * @return the    parsed string
     *                
     */
    function parseWmv($str)
    {
        return preg_replace_callback('~\[EMBED\]\s*<a\b[^>]*href=["\']([^"\']+\.(?:wmv)(?:[?#][^"\']*)?)["\'][^>]*>.*?</a>\s*\[/EMBED\]~is',
            fn($match) => $this->getObject('nativeplayer', 'files')->video($match[1]), $str);
    } # end of function
    
    /**
     * Method to take a string and return it with anchor linkd to 
     * flash movies changed to embeded media
     * 
     * TEST: passed
     * 
     * @param  string $str The string to be parsed
     * @return the    parsed string
     */
    function parseWmm($str)
    {
        return preg_replace_callback('~\[EMBED\]\s*<a\b[^>]*href=["\']([^"\']+\.(?:swf)(?:[?#][^"\']*)?)["\'][^>]*>.*?</a>\s*\[/EMBED\]~is',
            fn($match) => $this->getObject('nativeplayer', 'files')->download($match[1]), $str);
    } # end of function
    
    /**
     * Method to take a string and return it with anchor linkd to 
     * mpeg movies changed to embeded media
     * 
     * TEST: passed
     * 
     * @param  string $str The string to be parsed
     * @return the    parsed string
     */
    function parseMpeg($str)
    {
        return preg_replace_callback('~\[EMBED\]\s*<a\b[^>]*href=["\']([^"\']+\.(?:mpe?g)(?:[?#][^"\']*)?)["\'][^>]*>.*?</a>\s*\[/EMBED\]~is',
            fn($match) => $this->getObject('nativeplayer', 'files')->video($match[1]), $str);
    } # end of function
    
    /**
     * Method to take a string and return it with anchor linkd to 
     * avi movies changed to embeded media.
     * 
     * TEST: passed
     * 
     * @param  string $str The string to be parsed
     * @return the    parsed string
     */
    function parseAvi($str)
    {
        return preg_replace_callback('~\[EMBED\]\s*<a\b[^>]*href=["\']([^"\']+\.(?:avi)(?:[?#][^"\']*)?)["\'][^>]*>.*?</a>\s*\[/EMBED\]~is',
            fn($match) => $this->getObject('nativeplayer', 'files')->video($match[1]), $str);
    } # end of function
    
    /**
     * Method to take a string and return it with anchor linkd to 
     * mp3 files changed to embeded media. It uses a provided
     * flash MP3 player to play MP3 files
     * 
     * TEST: passed
     * 
     * @param  string $str The string to be parsed
     * @return the    parsed string
     */
    function parseMp3($str)
    {
        return preg_replace_callback('~\[EMBED\]\s*<a\b[^>]*href=["\']([^"\']+\.(?:mp3)(?:[?#][^"\']*)?)["\'][^>]*>.*?</a>\s*\[/EMBED\]~is',
            fn($match) => $this->getObject('nativeplayer', 'files')->audio($match[1]), $str);
    } # end of function
    
    /**
     * Method to parse a string against all the available methods
     * 
     * @param  string $str The string to be parsed
     * @return the    parsed string
     */
    function parseAll($str)
    {
        $str = $this->parseAvi($str);
        $str = $this->parseMov($str);
        $str = $this->parseMp3($str);
        $str = $this->parseMpeg($str);
        $str = $this->parseWmm($str);
        return $this->parseWmv($str);
    } # end of function
    

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @param  unknown $str Parameter description (if any) ...
     * @return unknown Return description (if any) ...
     * @access public 
     */
    public function parse($str)
    {
        return $this->parseAll($str);
    }
} # end of class

?>