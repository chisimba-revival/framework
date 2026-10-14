<?php
/**
 * Class to insert a pictureshow for a given location in the content. 
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
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2009 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id:  $
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
 * Class to insert a picture show for a given location in the content. 
 *
 * @author Paul Scott <pscott@uwc.ac.za>
 */

class parse4pictureshow extends ChisimbaObject
{
    public function init() {}

    /** Preserve the shortcode, serving only registered, authorised images. */
    public function parse($txt)
    {
        return preg_replace_callback('/\[PICTURESHOW:(.*?)\]/is', function ($match) {
            $params = array('username' => 'admin', 'folder' => 'pictures');
            foreach (explode(',', $match[1]) as $part) {
                $pair = explode('=', $part, 2);
                if (count($pair) === 2) {
                    $params[strtolower(trim($pair[0]))] = trim($pair[1]);
                }
            }
            $folder = trim($params['folder'], '/');
            // Folder segments only; never traverse or enumerate raw storage.
            if ($folder === '' || preg_match('~(^|/)\.{1,2}(/|$)|[\\\\\x00-\x1f]~', $folder)) {
                return '';
            }
            $uid = $this->getObject('user', 'security')->getUserId($params['username']);
            if (!$uid) return '';
            $files = $this->getObject('dbfile', 'filemanager')->selectPrepared(
                'SELECT * FROM tbl_files WHERE filefolder = ? AND category = ? ORDER BY filename, id',
                array('users/' . $uid . '/' . $folder, 'images')
            );
            $policy = $this->getObject('filereadpolicy', 'filemanager');
            $html = '';
            foreach ($files as $file) {
                if (!in_array(strtolower((string) ($file['datatype'] ?? '')), array('png', 'jpg', 'jpeg', 'gif', 'webp', 'avif'), true)
                    || !$policy->mayRead($file)) continue;
                $url = htmlspecialchars($this->uri(array('action' => 'file', 'id' => $file['id']), 'filemanager'), ENT_QUOTES, 'UTF-8');
                $name = htmlspecialchars($file['filename'], ENT_QUOTES, 'UTF-8');
                $html .= '<figure><a href="' . $url . '"><img src="' . $url
                    . '" alt="' . $name . '" loading="lazy"></a><figcaption>' . $name . '</figcaption></figure>';
            }
            return $html === '' ? '' : '<div class="chisimba-image-gallery">' . $html . '</div>';
        }, $txt);
    }
}
