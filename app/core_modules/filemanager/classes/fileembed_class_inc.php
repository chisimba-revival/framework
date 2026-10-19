<?php

/**
 * Class to Generate the Code to <embed> a Media Object in a Webpage
 *
 * At present, it caters for various types of media such as:
 * - audio
 * - video
 * - flash
 * - images
 * - 3d obj
 * - vrml
 *
 * PHP version 3
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
 * @package   filemanager
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see
 */

/**
 * Class to Generate the Code to <embed> a Media Object in a Webpage
 *
 * At present, it caters for various types of media such as:
 * - audio
 * - video
 * - flash
 * - images
 * - 3d obj
 * - vrml
 *
 * @category  Chisimba
 * @package   filemanager
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see
 */
class fileembed extends ChisimbaObject {

    // Services and state populated during initialisation.
    public $objCleanUrl;
    public $objConfig;

    /**
    *
    * @var object $objLanguage The language object
    *
    */
    private $objLanguage;

    /**
     * Constructor
     */
    function init() {
        $this->loadClass('link', 'htmlelements');
        $this->objCleanUrl = $this->getObject('cleanurl');
        $this->objConfig = $this->getObject('altconfig', 'config');
        $this->objLanguage = $this->getObject('language', 'language');
    }

    /**
     * Method to generate the embed code for a media object
     * @param  string $file   Full Path to the File
     * @param  string $type   Type of Embed
     * @param  int    $width  Width of the Media File
     * @param  int    $height Height of the Media File
     * @return string The media file embed code
     */
    function embed($file, $type, $width="100%", $height="100%") {
        // For some reason, cleaning the URL messes things up. Turned off for so long
        // Please clean URLs yourself for the timebeing before calling this function
        //$this->objCleanUrl->cleanUpUrl($file);

        switch ($type) {
            case 'link':
            default:
                return $this->linkToFile($file);
            case 'image':
                return $this->showImage($file, $width, $height);
            case 'obj3d':
                return $this->showObj3d($file, $width, $height);
            case 'vrml':
                return $this->showVRML($file, $width, $height);
            case 'freemind':
                return $this->showFreemind($file, $width, $height);
            case 'audio':
                return $this->showSoundPlayer($file);
            case 'flash':
                return $this->showFlash($file, $width, $height);
            case 'quicktime':
                return $this->showQuicktime($file, $width, $height);
            case 'wmv':
                return $this->showWMV($file, $width, $height);
            case 'avi':
                return $this->showAVI($file, $width, $height);
            case 'mpg':
            case 'mpeg':
                return $this->showMPG($file, $width, $height);
            case 'mp4':
                return $this->showMP4($file, $width, $height);
            case 'm4v':
                return $this->showMP4($file, $width, $height);
            case 'flv':
                return $this->showFLV($file, $width, $height);
            case 'ogg':
            case 'ogv':
                return $this->showOggVideo($file, $width, $height);
            case 'svg':
                return $this->showSVG($file, $width, $height);
            case 'webm':
                return $this->showWebm($file, $width, $height);
            case 'pdf':
                return $this->showPDF($file, $width, $height);
        }
    }

    /**
     *
     * Embed a WEBM video using HTML5 code
     *
     * @param string $file The file URL to embed
     * @param integer $width The width of the video
     * @param integer $height The height of the video
     * @return string The rendered video HTML5 tags
     * @access public
     */
    public function showWebm($file, $width, $height)
    {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height);
    }

    /**
     * Method to provide a link to the file
     * @param string $file Path to the File
     */
    function linkToFile($file) {
        $link = new link($file);
        $link->link = basename($file);
        return $link->show();
    }

    /**
     * Method to show an Image
     * @param string $file Path to the Image
     */
    function showImage($file, $width=null, $height=null) {
        if ($width != null && $height != null) {
            return '<img src="' . $file . '" width="' . $width . '" height="' . $height . '" />';
        } else {
            return '<img src="' . $file . '"/>';
        }
    }

    /**
     * Method to show a 3d Object
     * This uses David Wafula's 3d Object Viewer
     * @param string $file Path to the Object
     */
    function showObj3d($file) {
        return $this->getObject('nativeplayer', 'files')->download($file, false);
    }

    /**
     * Method to embed a VRML file
     * @param string $file Path to the File
     */
    function showVRML($file) {
        return $this->getObject('nativeplayer', 'files')->download($file, false);
    }

    /**
     * Method to embed a freemind map
     * Note. This uses the flash version rather than the java applet.
     * @param string $file Path to the File
     */
    function showFreemind($file) {
        return $this->getObject('nativeplayer', 'files')->download($file, false);
    }

    /**
     * Render audio with the shared native player
     * @param string $file Path to the File
     */
    function showSoundPlayer($file) {
        return $this->getObject('nativeplayer', 'files')->audio($file);
    }

    /**
     * Keep the original Flash file available without a browser plugin
     * @param string $file   Path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     */
    function showFlash($file, $width='100%', $height='400') {
        return $this->getObject('nativeplayer', 'files')->download($file);
    }

    /**
     * Method to embed a Quicktime Object
     * @param string $file   Path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     */
    function showQuicktime($file, $width='100%', $height='400') {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height);
    }

    /**
     * Method to show a WMV video
     * @param string $file   Path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     */
    function showWMV($file, $width='100%', $height='400') {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height, true);
    }

    /**
     * Method to show a AVI video
     * @param string $file   Path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     */
    function showAVI($file, $width='100%', $height='400') {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height, true);
    }

    /**
     * Method to show a FLV video
     * @param string $file   Path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     */
    public function old______showFLV($file, $width='100%', $height='400') {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height, true);
    }

    /**
     * Show a OGG video
     * @param string $file   Path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     * @access public
     * @return string The rendered video
     */
    public function showOggVideo($file, $width='100%', $height='400') {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height);
    }

    /**
     * Method to show a Scalable Vector Graphics (SVG) Image
     * NOTE: Width and height are kept for legacy reasons,  but
     * they are not used as they do not work as expected. Rather
     * render the SVG in a fluid div.
     *
     * @param string $file   Path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     * @access public
     * @return string The rendered SVG
     *
     */
    function showSVG($file, $width=NULL, $height=NULL) {
        return '<object data="' . $file
          . '" type="image/svg+xml"></object>';
    }

    /**
     * Method to show a MPEG Moview
     * @param string $file   Path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     */
    function showMPG($file, $width='100%', $height='400') {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height, true);
    }


    /**
     *
     * Keep a legacy FLV file available for conversion. Width and height are here for
     * legacy reasons, they are not needed since we have implemented this
     * as a fluid div, and the video will resize to available space or
     * can be styled using the CSS Fluid video settings. See common2.css
     * in the core _common2 skin.
     *
     * @param string $file   URL path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     * @access public
     * @return string The rendered video
     *
     */
    public function showFLV($file, $width=FALSE, $height=FALSE) {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height, true);
    }

    /**
     *
     * Historical entry point for the shared native video player.
     *
     * @param string $file The full URL for the file to play
     * @return string The rendered player with the video
     * @access public
     *
     */
    public function showWithFlowPlayer($file)
    {
        return $this->getObject('nativeplayer', 'files')->video($file);
    }

    /**
     *
     * Show a MP4 Movie. Note that width and height are here for
     * legacy reasons, they are not needed since we have implemented this
     * as a fluid div, and the video will resize to available space or
     * can be styled using the CSS Fluid video settings. See common2.css
     * in the core _common2 skin.
     *
     * @param string $file   URL path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     * @access public
     * @return string The rendered video
     *
     */
    public function showMP4($file, $width='100%', $height='auto')
    {
        return $this->getObject('nativeplayer', 'files')->video($file, $width, $height);
    }

    /**
     *
     * Embed a PDF file
     *
     * @param string $file   URL path to the File
     * @param string $width  Width of Object
     * @param string $height Height of Object
     * @access public
     * @return string The rendered PDF
     *
     */
    public function showPDF($file, $width="100%", $height="500")
    {
        return "<div class='pdf_wrapper'>"
          . "<embed src='$file' width='$width' height='$height'>"
          . "</embed></div>";
    }
}
?>