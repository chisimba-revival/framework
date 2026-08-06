<?php

/**
 * Context Image
 *
 * CThis class allows users to retrieve and set an image as their context image
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
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2008 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
/* -------------------- dbTable class ----------------*/
// security check - must be included in all scripts
if (! /**
 * Description for $GLOBALS
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS ['kewl_entry_point_run']) {
    die ( "You cannot view this page directly" );
}
// end security check


/**
 * Context Image
 *
 * CThis class allows users to retrieve and set an image as their context image
 *
 * @category  Chisimba
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2008 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class contextimage extends ChisimbaObject {

    /**
     * Constructor
     */
    public function init() {
        $this->objFiles = $this->getObject ( 'dbfile', 'filemanager' );
        $this->objThumbnails = $this->getObject ( 'thumbnails', 'filemanager' );
        $this->objConfig = $this->getObject ( 'altconfig', 'config' );
        $this->objMkdir = $this->getObject ( 'mkdir', 'files' );
        $this->objCleanUrl = $this->getObject ( 'cleanurl', 'filemanager' );
        $this->objFileParts = $this->getObject('fileparts', 'files');
    }

    /**
     * Method to retrieve a context image
     *
     * @param string $contextCode Context Code
     * @return string Path to Image - Still needs html img tags added
     */
    public function getContextImage($contextCode)
    {
        // All filesystem operations use getcontentBasePath().
        $basePath = rtrim($this->objConfig->getcontentBasePath(), '/');
        $contentPath = rtrim($this->objConfig->getcontentPath(), '/');

        foreach (array('jpg', 'png') as $extension) {
            $filename = $contextCode . '.' . $extension;
            if (is_file($basePath . '/contextimage/' . $filename)) {
                return $this->objCleanUrl->cleanUpUrl(
                    $contentPath . '/contextimage/' . $filename
                );
            }
        }

        return FALSE;
    }
    /**
     * Method to set a context image
     *
     * @param string $contextCode Context Code
     * @param string $fileId Record Id of the file from file manager
     */
    public function setContextImage($contextCode, $fileId) {
        if (!$this->checkContextImageFolder()) {
            return FALSE;
        }

        $filename = $this->objFiles->getFileName($fileId);
        $sourceFile = $this->objFiles->getFilePath($fileId);

        if ($filename == FALSE || $sourceFile == FALSE || !is_file($sourceFile)) {
            return FALSE;
        }

        $basePath = rtrim($this->objConfig->getcontentBasePath(), '/');
        $thumbnail = FALSE;
        $thumbnailFolders = array(
            '/filemanager_thumbnails/',
            '/filemanager_thumbnails/medium/',
            '/filemanager_thumbnails/large/'
        );

        foreach ($thumbnailFolders as $folder) {
            foreach (array('jpg', 'png') as $extension) {
                $candidate = $basePath . $folder . $fileId . '.' . $extension;
                if (is_file($candidate)) {
                    $thumbnail = $candidate;
                    break 2;
                }
            }
        }

        if ($thumbnail == FALSE) {
            $this->objThumbnails->createThumbailFromFile($sourceFile, $fileId);

            foreach ($thumbnailFolders as $folder) {
                foreach (array('jpg', 'png') as $extension) {
                    $candidate = $basePath . $folder . $fileId . '.' . $extension;
                    if (is_file($candidate)) {
                        $thumbnail = $candidate;
                        break 2;
                    }
                }
            }
        }

        if ($thumbnail == FALSE) {
            return FALSE;
        }

        $extension = strtolower(pathinfo($thumbnail, PATHINFO_EXTENSION));
        if (!in_array($extension, array('jpg', 'png'), TRUE)) {
            return FALSE;
        }

        $destinationFolder = $basePath . '/contextimage';
        $destination = $destinationFolder . '/' . $contextCode . '.' . $extension;

        foreach (array('jpg', 'png') as $oldExtension) {
            $oldImage = $destinationFolder . '/' . $contextCode . '.' . $oldExtension;
            if ($oldImage !== $destination && is_file($oldImage) && !unlink($oldImage)) {
                return FALSE;
            }
        }

        return copy($thumbnail, $destination);
    }
    /**
     * Method to remove an existing context image
     *
     * @param string $contextCode Context Code
     * @return boolean Whether the image has been successfully removed or not
     */
    public function removeContextImage($contextCode)
    {
        $basePath = rtrim($this->objConfig->getcontentBasePath(), '/');
        $success = TRUE;

        foreach (array('jpg', 'png') as $extension) {
            $image = $basePath . '/contextimage/' . $contextCode . '.' . $extension;
            if (is_file($image) && !unlink($image)) {
                $success = FALSE;
            }
        }

        return $success;
    }
    /**
     * Method to check that the user folder for uploads, and subfolders exist
     *
     * @param  string  $userId UserId of the User
     * @return boolean True if folder exists, else False
     */
    private function checkContextImageFolder() {
        // Set Up Path
        $path = $this->objConfig->getcontentBasePath () . '/contextimage';
        $path = $this->objCleanUrl->cleanUpUrl ( $path );

        // Check if Folder exists, else create it
        $result = $this->objMkdir->mkdirs ( $path );

        return $result;
    }
}
?>