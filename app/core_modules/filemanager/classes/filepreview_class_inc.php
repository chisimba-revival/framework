<?php

/**
 * Class to present a preview of files
 *
 * PHP versions 4 and 5
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
 * Class to present a preview of files
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
class filepreview extends ChisimbaObject {

    // Services and state populated during initialisation.
    public $objFileParts;
    public $objConfig;
    public $file;
    public $objFiles;
    public $objCleanurl;
    public $objThumbnails;
    public $objFileEmbed;
    public $objFolderAccess;
    public $sysConf;


    //secure folder 
    private $secureFolder;
    private $isSecure = FALSE;

    /**
     * Constructor
     */
    function init() {
        $this->objFileParts = $this->getObject('fileparts', 'files');
        $this->objFiles = $this->getObject('dbfile');
        $this->objCleanurl = $this->getObject('cleanurl');
        $this->objThumbnails = $this->getObject('thumbnails');
        $this->objConfig = $this->getObject('altconfig', 'config');
        $this->objFileEmbed = $this->getObject('fileembed');

        $this->objFolderAccess = $this->getObject("folderaccess");
        $this->sysConf = $this->getObject('dbsysconfig', 'sysconfig');
        $this->secureFolder = $this->sysConf->getValue('SECUREFODLER', 'filemanager');

        $this->loadClass('link', 'htmlelements');
    }

    /**
     * Method to determine which sub folder a file should be placed in
     *
     * Note: This function is pretty hardcoded in determining the result
     * More dynamic options are welcome.
     *
     * @param  string $name     Name of the File
     * @param  string $mimetype Mimetype of the File
     * @return string Sub Folder file must be placed in
     */
    function previewFile($fileId) {
        $preview = '';

        $this->file = $this->objFiles->getFileInfo($fileId);

        if ($this->file == FALSE || !$this->getObject('filereadpolicy', 'filemanager')->mayRead($this->file)) {
            return '';
        }

        $this->isSecure = false;
        if ($this->objFolderAccess->isFileAccessPrivate($this->file)) {
            $this->isSecure = true;
        }


        $fullPath = $this->objConfig->getcontentBasePath() . $this->file['path'];
        $path = $this->objConfig->getcontentPath() . $this->file['path'];
        if ($this->isSecure) {
            $fullPath = $this->secureFolder . '/' . $this->file['path'];
            $path = $this->file['path'];
        }
        $this->file['fullpath'] = $fullPath;
        $this->file['path'] = $path;
        $url = $this->objConfig->getsiteRoot() . $this->file['path'];
        if ($this->isSecure) {
            $url = $this->uri(array("action" => "downloadsecurefile", "path" => $this->file['path'], "filename" => $this->file['filename']));
        }
        $this->file['fullurl'] = $url;

        // Fix Up - Try to get file using controller, instead of hard linking to file
        $this->file['path'] = $this->objCleanurl->cleanUpUrl($this->file['path']);
        $this->file['fullurl'] = $this->objCleanurl->cleanUpUrl($this->file['fullurl']);
        $this->file['fullpath'] = $this->objCleanurl->cleanUpUrl($this->file['fullpath']);

        // Restore Double Slash for http://
        $protocol = "http";
        if (isset($this->is_htpps)) {
            if ($this->is_htpps) {
                $protocol = "https";
            }
        }
        $this->file['fullurl'] = str_replace($protocol . ':/', $protocol . '://', $this->file['fullurl']);

        $this->file['linkname'] = $this->uri(array('action' => 'file', 'id' => $this->file['id'], 'filename' => $this->file['filename']), 'filemanager');

        //var_dump($this->file);
        switch ($this->file['category']) {
            case 'images': $preview = $this->showImage();
                break;
            case 'obj3d': $preview = $this->show3dObject();
                break;
            case 'freemind': $preview = $this->showFreemind();
                break;
            case 'audio': $preview = $this->showAudio();
                break;
            case 'video': $preview = $this->showVideo();
                break;
            case 'flash': $preview = $this->showFlash();
                break;
            case 'scripts': $preview = $this->showScript();
                break;
            case 'documents': $preview = $this->showDocument();
                break;
            case 'archives': $preview = $this->showArchive();
                break;
            case 'fonts': $preview = $this->showFont();
                break;
            case 'timeline': $preview = $this->showTimeline();
                break;
        }
        return $preview;
    }

    /**
     * determines if we are using http or https
     * @return <type>
     */
    function is_https() {
        return strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https' ? true : false;
    }

    /**
     * Method to preview an image
     */
    function showImage() {


        $contentPath = $this->objConfig->getcontentPath();
        $contentBasePath = $this->objConfig->getcontentBasePath();
        if ($this->isSecure) {
            $contentPath = $this->secureFolder . '/';
            $contentBasePath = $this->secureFolder . '/';
        }
        // If Photoshop File, check for converted
        if ($this->file['datatype'] == 'psd') {
            if (file_exists($contentPath . 'filemanager_thumbnails/standard_' . $this->file['id'] . '.jpg')) {
                return $this->objFileEmbed->embed($contentPath . 'filemanager_thumbnails/standard_' . $this->file['id'] . '.jpg', 'image');
            } else {
                $objImageResize = $this->getObject('imageresize', 'files');
                $objImageResize->setImg($this->file['path']);
                $objImageResize->resize(imagesx($objImageResize->image), imagesy($objImageResize->image), TRUE);

                $img = $contentBasePath . '/filemanager_thumbnails/standard_' . $this->file['id'] . '.jpg';
                $objImageResize->store($img);

                return $this->objFileEmbed->embed($contentPath . 'filemanager_thumbnails/standard_' . $this->file['id'] . '.jpg', 'image');
            }
        } else if ($this->file['datatype'] == 'svg') {
            return $this->objFileEmbed->embed($this->file['linkname'], 'svg', '100%', 400);
        } else {

            $fileUrl = $this->objConfig->getSiteRoot() . '/' . $this->file['path'];


            if ($this->isSecure) {
                $fileUrl = $this->uri(array("action" => "downloadsecurefile", "path" => $this->file['path'], "filename" => $this->file['filename']));
            }
            return $this->objFileEmbed->embed($fileUrl, 'image', null, null);
        }
        //width="270" height="270"'<img src="'.$this->file['linkname'].'"  />';//
    }

    /**
     * Method to preview a 3d Object
     */
    function show3dObject() {
        switch ($this->file['datatype']) {
            // Original files use the same authorised route as other previews.
            case 'obj': return $this->objFileEmbed->embed($this->file['linkname'], 'obj3d');
            case 'wrl': return $this->objFileEmbed->embed($this->file['linkname'], 'vrml');
            default: return $this->objFileEmbed->embed($this->file['linkname'], 'unknown');
        }
    }

    /**
     * Method to preview a Freemind Map
     */
    function showFreemind() {
        return $this->objFileEmbed->embed($this->file['linkname'], 'freemind');
    }

    /**
     * Method to preview an Audio File
     */
    function showAudio() {
        return $this->objFileEmbed->embed($this->file['linkname'], 'audio');
    }

    /**
     * Method to preview a Video
     */
    function showVideo() {
        if (array_key_exists('width', $this->file) && $this->file['width'] != '') {
            $width = $this->file['width'] < 200 ? '200' : $this->file['width'];
        } else {
            $width = '100%';
        }

        if (array_key_exists('height', $this->file) && $this->file['height'] != '') {
            $height = $this->file['height'] < 200 ? '200' : $this->file['height'];
        } else {
            $height = '400';
        }

        switch ($this->file['datatype']) {
            case 'mov':
                return $this->objFileEmbed->embed($this->file['linkname'], 'quicktime', $width, $height);
            case '3gp':
                return $this->objFileEmbed->embed($this->file['linkname'], 'quicktime', $width, $height);
            case 'wmv':
                return $this->objFileEmbed->embed($this->file['linkname'], 'wmv', $width, $height);
            case 'avi':
                return $this->objFileEmbed->embed($this->file['linkname'], 'avi', $width, $height);
            case 'flv':
                return $this->objFileEmbed->embed($this->file['linkname'], 'flv', $width, $height + 26);
            case 'ogg':
            case 'ogv':
                return $this->objFileEmbed->embed($this->file['linkname'], 'ogg', $width, $height + 12);
            case 'mpg':
            case 'mpeg':
                return $this->objFileEmbed->embed($this->file['linkname'], 'mpg', $width, $height + 12);
            case 'mp4':
                return $this->objFileEmbed->embed($this->file['linkname'], 'mp4', $width, $height + 12);
            case 'm4v' :
                return $this->objFileEmbed->embed($this->file['fullurl'], 'm4v', $width, $height + 12);
            case 'webm':
                return $this->objFileEmbed->embed($this->file['fullurl'], 'webm', $width, $height + 12);
            default:
                return $this->objFileEmbed->embed($this->file['linkname'], 'unknown');
        }
    }

    /**
     * Method to preview a Flash file
     */
    function showFlash() {
        if (array_key_exists('width', $this->file) && $this->file['width'] != '') {
            $width = $this->file['width'];
        } else {
            $width = '100%';
        }

        if (array_key_exists('height', $this->file) && $this->file['height'] != '') {
            $height = $this->file['height'];
        } else {
            $height = '100%';
        }

        return $this->objFileEmbed->embed($this->file['linkname'], 'flash', $width, $height);
    }

    /**
     * Method to preview a Script
     */
    function showScript() {
        // Get Extension
        $filetype = $this->objFileParts->getExtension($this->file['filename']);

        // Convert Extension to Language
        switch ($filetype) {
            case 'phps': $filetype = 'php';
                break;
            case 'pl': $filetype = 'perl';
                break;
            case 'js': $filetype = 'javascript';
                break;
            case 'py': $filetype = 'python';
                break;
        }

        $contentPath = $this->objConfig->getcontentPath();
        if ($this->isSecure) {
            $contentPath = $this->secureFolder . '/';
        }

        // Check if file has been rendered
        if (file_exists($contentPath . 'filemanager_thumbnails/' . $this->file['id'] . '.htm')) {
            return '<div class="code-file-preview" style="padding: 20px; overflow: auto;">' . file_get_contents($contentPath . 'filemanager_thumbnails/' . $this->file['id'] . '.htm') . '</div>';
        } else {
            // Open File, Read Contents, Close
            $handle = fopen($this->file['path'], "r");
            $contents = fread($handle, filesize($this->file['path']));
            fclose($handle);

            $objGeshi = $this->getObject('geshiwrapper', 'utilities');
            $objGeshi->source = $contents;
            $objGeshi->language = $filetype;

            $objGeshi->startGeshi();
            $objGeshi->enableLineNumbers(2);

            $content = stripslashes($objGeshi->show());

            $objCleaner = $this->newObject('htmlcleaner', 'utilities');
            $content = $objCleaner->cleanHtml($content);

            // Write to File to Prevent Server Straim
            $filename = $contentPath . 'filemanager_thumbnails/' . $this->file['id'] . '.htm';
            $handle = fopen($filename, 'w');
            fwrite($handle, $content);
            fclose($handle);

            return $content;
        }
    }

    /**
     * Method to show a document
     */
    function showDocument() {
        $contentPath = $this->objConfig->getcontentPath();
        $contentBasePath = $this->objConfig->getcontentBasePath();
        if ($this->isSecure) {
            $contentPath = $this->secureFolder . '/';
            $contentBasePath = $this->secureFolder . '/';
        }


        switch ($this->file['datatype']) {
            case 'rss':
                if (file_exists($contentPath . 'filemanager_thumbnails/' . $this->file['id'] . '.htm')) {
                    return file_get_contents($contentPath . 'filemanager_thumbnails/' . $this->file['id'] . '.htm');
                    break;
                } else {
                    // Open File, Read Contents, Close
                    $handle = fopen($this->file['path'], "r");
                    $contents = fread($handle, filesize($this->file['path']));
                    fclose($handle);

                    $this->objFeed = $this->getObject('feeds', 'feed');
                    $feed = $this->objFeed->importString($contents);

                    $link = new link($feed->link());
                    $link->link = $feed->title();
                    $link->title = $feed->description();

                    // Some replacement to make it XHTML compliant
                    $url = str_replace('&amp;', '&', $link->show());
                    $url = str_replace('&', '&amp;', $url);

                    $content = '<h1>' . $url . '</h1>';

                    foreach ($feed->items as $item) {
                        $link = new link($item->link());
                        $link->link = $item->title();

                        // Some replacement to make it XHTML compliant
                        $url = str_replace('&amp;', '&', $link->show());
                        $url = str_replace('&', '&amp;', $url);

                        $content .= '<p><strong>' . $url . '</strong><br />';
                        $content .= $item->description() . '</p>';
                    }

                    $objCleaner = $this->newObject('htmlcleaner', 'utilities');
                    $content = $objCleaner->cleanHtml($content);

                    // Write to File to Prevent Server Straim
                    $filename = $contentPath . 'filemanager_thumbnails/' . $this->file['id'] . '.htm';
                    $handle = fopen($filename, 'w');
                    fwrite($handle, $content);
                    fclose($handle);

                    return $content;
                    break;
                }
            case 'txt':
            case 'html':
            case 'htm':
                return '<iframe src="' . $this->file['linkname'] . '" width="99%" height="300"></iframe>';
            case 'pdf':
                // The canonical endpoint rechecks current access before serving bytes.
                $url = htmlspecialchars($this->file['linkname'], ENT_QUOTES, 'UTF-8');
                $name = htmlspecialchars($this->file['filename'], ENT_QUOTES, 'UTF-8');
                return '<div class="chisimba-document"><iframe class="chisimba-document-preview" src="' . $url
                    . '" title="' . $name . '"></iframe>' . $this->documentDownload() . '</div>';
            case 'odt':
            case 'sxw':
            case 'rtf':
            case 'doc':
            case 'docx':
            case 'wpd':
            case 'ods':
            case 'sxc':
            case 'xls':
            case 'xlsx':
            case 'odp':
            case 'sxi':
            case 'ppt':
            case 'pptx':
                // Office documents remain available in their original format.
                return $this->documentDownload();
            default:
                return NULL;
        }
    }

    /** Render an original-file link without consulting obsolete Flash derivatives. */
    private function documentDownload() {
        $label = $this->getObject('language', 'language')->languageText('word_download');
        return '<p><a href="' . htmlspecialchars($this->file['linkname'], ENT_QUOTES, 'UTF-8')
            . '">' . htmlspecialchars($label . ': ' . $this->file['filename'], ENT_QUOTES, 'UTF-8')
            . '</a></p>';
    }

    /**
     * Method to Preview a Zip File
     */
    function showArchive() {
        if ($this->file['datatype'] == 'zip') {
            $objArchive = $this->getObject('archivehandler');
            return $objArchive->previewZip($this->file['path'], 'zip');
        } else {
            return 'No Preview Available';
        }
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return string Return description (if any) ...
     * @access public
     */
    function showFont() {
        return 'saffas';
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return object Return description (if any) ...
     * @access public
     */
    function showTimeline() {
        $objTimeline = & $this->getObject('timelineparser', 'timeline');
        $objTimeline->setTimelineUri($this->file['path']);
        return $objTimeline->show();
    }

}

?>