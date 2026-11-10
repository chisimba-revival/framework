<?php

/**
 * Class to Generate Thumbnails for the File Manager
 *
 * This class packages functionality to generate thumbnails for images that are uploaded.
 * It is separated from the image resize class as it here it checks for folder existence.
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
 * @package   filemanager
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see
 */


/**
 * Class to Generate Thumbnails for the File Manager
 *
 * This class packages functionality to generate thumbnails for images that are uploaded.
 * It is separated from the image resize class as it here it checks for folder existence.
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
class thumbnails extends ChisimbaObject
{

    // Services and state populated during initialisation.
    public $objMkdir;
    public $objImageResize;
    public $objFileParts;
    public $objFiles;
    public $objCleanUrl;
    public $thumbLocations;


    /**
    * Constructor
    */
    public function init()
    {
        $this->objConfig = $this->getObject('altconfig', 'config');
        $this->objMkdir = $this->getObject('mkdir', 'files');
        $this->objImageResize = $this->getObject('imageresize', 'files');
        $this->objFileParts = $this->getObject('fileparts', 'files');
        $this->objFiles = $this->getObject('dbfile','filemanager');
        $this->objCleanUrl = $this->getObject('cleanurl');
        //the directories array
        $this->thumbLocations = array(
            0=>'/filemanager_thumbnails/large/',
            1=>'/filemanager_thumbnails/medium/',
            2=>'/filemanager_thumbnails/'
        );
    }

    /** Generate a bounded preview in protected storage, only after authorising its original. */
    public function pickerPreview($file)
    {
        if (!is_array($file) || !preg_match('/^[A-Za-z0-9_-]+$/D', (string)($file['id'] ?? ''))
            || !$this->getObject('filereadpolicy', 'filemanager')->mayRead($file)) return false;
        $delivery = $this->getObject('filedelivery', 'filemanager');
        $secure = $this->getObject('dbsysconfig', 'sysconfig')->getValue('SECUREFODLER', 'filemanager');
        if (!is_string($secure) || !is_dir($secure)) return false;
        $source = $delivery::resolve($secure, $file['path'] ?? '');
        if (!$source) $source = $delivery::resolve($this->objConfig->getcontentBasePath(), $file['path'] ?? '');
        if (!$source) return false;
        $relative = 'filemanager_thumbnails/picker/'.$file['id'].'.jpg';
        $directory = rtrim($secure, '/').'/filemanager_thumbnails/picker';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) return false;
        $target = $directory.'/'.$file['id'].'.jpg';
        $signature = hash('sha256', $source.'|'.filemtime($source).'|'.filesize($source).'|320x200-v1');
        $stamp = $target.'.stamp';
        $lock = fopen($target.'.lock', 'c');
        if (!$lock) return false;
        $temporary = null;
        try {
            if (!flock($lock, LOCK_EX)) return false;
            clearstatcache(true, $target);
            if (is_file($target) && is_file($stamp) && trim(file_get_contents($stamp)) === $signature) return $relative;
            $info = @getimagesize($source);
            if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_AVIF], true)
                || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 24000000) return false;
            // Bound decoder memory as well as output size; oversized images retain their filename card.
            $limit = trim(ini_get('memory_limit'));
            $bytes = (int)$limit * (match(strtolower(substr($limit, -1))) {'g'=>1073741824,'m'=>1048576,'k'=>1024,default=>1});
            if ($bytes > 0 && memory_get_usage(true) + $info[0] * $info[1] * 8 + 16777216 > $bytes) return false;
            $this->objImageResize->setImg($source);
            if (!$this->objImageResize->canCreateFromSouce) return false;
            $scale = min(1, 320 / $info[0], 200 / $info[1]);
            $this->objImageResize->resize(max(1, (int)round($info[0]*$scale)), max(1, (int)round($info[1]*$scale)), false);
            $temporary = tempnam($directory, '.preview-');
            if (!$temporary || !$this->objImageResize->store($temporary)) return false;
            chmod($temporary, 0600);
            if (!rename($temporary, $target)) return false;
            file_put_contents($stamp, $signature, LOCK_EX);
            chmod($stamp, 0600);
            return $relative;
        } finally {
            if ($temporary && is_file($temporary)) unlink($temporary);
            flock($lock, LOCK_UN); fclose($lock);
        }
    }

    /**
    * Method to check that the user folder for uploads, and subfolders exist
    * @param  string  $userId UserId of the User
    * @return boolean True if folder exists, else False
    */
    public function checkThumbnailFolder()
    {
        // Set Up Path
        $path = $this->objConfig->getcontentBasePath().$this->thumbLocations[0];
        $path = $this->objCleanUrl->cleanUpUrl($path);
        //medium thumbnail images
        $m_path = $this->objConfig->getcontentBasePath().$this->thumbLocations[1];
        $m_path = $this->objCleanUrl->cleanUpUrl($m_path);
        //large thumbnail images
        $l_path = $this->objConfig->getcontentBasePath().$this->thumbLocations[2];
        $l_path = $this->objCleanUrl->cleanUpUrl($l_path);
        // Check if Folder exists, else create it
        $result = $this->objMkdir->mkdirs($path);
        //create folder for medium images
        $this->objMkdir->mkdirs($m_path);
        //create folder for large images
        $this->objMkdir->mkdirs($l_path);

        return $result;
    }
    
    /**
    * Method to create Thumbnail
    * @param string $filepath Path to File
    * @param string $fileId   Record Id of the path
    */
    public function createThumbailFromFile($filepath, $fileId)
    {
        
        // Check if folder exists
        $this->checkThumbnailFolder();
        
        $filepath = $this->objCleanUrl->cleanUpUrl($filepath);
        //image size for video thumbnails
        $imgSize = '';
        // Send Image to Resize Class
        $this->objImageResize->setImg($filepath);
        
        for($index=0;$index<3;$index++){
        // Do a check if a thumbnail exists. No need to overwrite
        $thumbJpg = $this->objConfig->getcontentBasePath().$this->thumbLocations[$index].$fileId.'.jpg';
        $thumbJpg = $this->objCleanUrl->cleanUpUrl($thumbJpg);
        $thumbPng = $this->objConfig->getcontentBasePath().$this->thumbLocations[$index].$fileId.'.png';
        $thumbPng = $this->objCleanUrl->cleanUpUrl($thumbPng);        
        
        // Dont do anything if thumb exists.
        $ext = '';
        if (file_exists($thumbJpg))
        {
            $ext = 'jpg';
            return TRUE;
        }
        elseif (file_exists($thumbPng))
        {
            $ext = 'png';
            return TRUE;
        }
        
        if(preg_match('~image~', $this->objFiles->getFileMimetype($fileId))){
        if($index == 0){
            // Resize to 250x250 Maintaining Aspect Ratio
            $this->objImageResize->resize(250, 250, TRUE);
        }
        if($index == 1){
            // Resize to 200x200 Maintaining Aspect Ratio
            $this->objImageResize->resize(200, 200, TRUE);
        }
        if($index == 2){
            // Resize to 100x100 Maintaining Aspect Ratio
            $this->objImageResize->resize(100, 100, TRUE);
        }
        }elseif (preg_match('~video~', $this->objFiles->getFileMimetype($fileId))) {
            if($index == 0){
                $imgSize = '250x250';
            }elseif ($index == 1) {
                    $imgSize = '200x200';
                }elseif ($index == 2) {
                    $imgSize = '100x100';
                }
                //where the file will be saved
                $destination = $this->objConfig->getcontentBasePath().$this->thumbLocations[$index].$fileId.'.png';
                //where the file is located
                $location = $this->objFiles->getFilePath($fileId);
                //the command to be executed in order to produce the image thumbnail
                $command = "ffmpeg -i '".$location."' -vframes 1 -s '".$imgSize."' -ss 00:00:01.1 -f image2 '".$destination."'";
                if(!file_exists($destination)){
                    exec($command);
                }  else {
                    continue;
                }
            }
        
        
        
        //$this->objImageResize->show(); // Uncomment for testing purposes

        
        // Determine filename for file
        // If thumbnail can be created, give it a unique file name
        // Else resort to [ext].jpg - prevents clutter, other files with same type can reference this one file
        if ($this->objImageResize->canCreateFromSouce)
        {
            if ($ext == 'jpg')
            {
                $img = $this->objConfig->getcontentBasePath().$this->thumbLocations[$index].$fileId.'.jpg';
            }
            else
            {
                $img = $this->objConfig->getcontentBasePath().$this->thumbLocations[$index].$fileId.'.png';
            }
        }
        else
        {
            if ($ext == 'jpg')
            {
                $img = $this->objConfig->getcontentBasePath().$this->thumbLocations[$index].$this->objImageResize->filetype.'.jpg';
            }
            else
            {
                $img = $this->objConfig->getcontentBasePath().$this->thumbLocations[$index].$this->objImageResize->filetype.'.png';
            }
        }
        $this->objImageResize->store($img);
        }
        
        // Save File
        return $this->objImageResize->store($img);
    }

    /**
    * Method to get the thumbnail for a file
    *
    * Second parameter is optional. If provided and thumbnail does not exist, it will return an image
    * saying "unable to create thumbnail for {filetype}
    *
    * Otherwise it will just return FALSE
    * 
    * @param  string $fileId   Record Id of the File
    * @param  string $filename Filename of the file
     * @param string $size size of the thumbnail image to be returned
    * @return string Path to the thumbnail or False
    */
    public function getThumbnail($fileId, $filename=NULL, $filePath=NULL,$size=NULL)
    {
        $thumbLocation = '';
        if(empty($size) || NULL){
            $size = 'small';
        }
        switch (strtolower($size)){
        case 'large':
            $thumbLocation = $this->thumbLocations[0];
            break;
        case 'medium':
            $thumbLocation = $this->thumbLocations[1];
            break;
            default :
                $thumbLocation = $this->thumbLocations[2];
        }
        // Create thumbnail. If it exists, it will not be recreated.
        if ($filePath != NULL) {
            $this->createThumbailFromFile($this->objConfig->getcontentBasePath().'/'.$filePath, $fileId);
        }
        //set thumbnail location depending on the requested size
        
        
        // Check if thumbnail exist
        if (file_exists($this->objConfig->getcontentPath().$thumbLocation.$fileId.'.jpg'))
        {
            $url = $this->objConfig->getcontentPath().$thumbLocation.$fileId.'.jpg';
            $url = $this->objCleanUrl->cleanUpUrl($url);
            return $url;
        } 
        elseif (file_exists($this->objConfig->getcontentPath().$thumbLocation.$fileId.'.png'))
        {
            $url = $this->objConfig->getcontentPath().$thumbLocation.$fileId.'.png';
            $url = $this->objCleanUrl->cleanUpUrl($url);
            return $url;
        } 
        
        if ($filename == NULL)
        {
            return FALSE;
        }
        else
        {
            $extension = $this->objFileParts->getExtension($filename);
            
            if (file_exists($this->objConfig->getcontentPath().$thumbLocation.$extension.'.jpg'))
            {
                $url = $this->objConfig->getcontentPath().$thumbLocation.$extension.'.jpg';
                $url = $this->objCleanUrl->cleanUpUrl($url);
                return $url;
            }
            elseif (file_exists($this->objConfig->getcontentPath().$thumbLocation.$extension.'.png'))
            {
                $url = $this->objConfig->getcontentPath().$thumbLocation.$extension.'.png';
                $url = $this->objCleanUrl->cleanUpUrl($url);
                return $url;
            }
            else
            {
                return FALSE;
            }
        }
    }
}
?>
