<?php

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Store and retrieve the featured image belonging to a course context.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2008 Tohir Solomons
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
class contextimage extends ChisimbaObject
{
    /** @var object File Manager database gateway. */
    private $objFiles;

    /** @var object Recursive-directory helper. */
    private $objMkdir;

    /** @var object URL normalisation helper. */
    private $objCleanUrl;

    /**
     * Load the services required for context-image persistence.
     *
     * @return void
     */
    public function init()
    {
        $this->objFiles = $this->getObject('dbfile', 'filemanager');
        $this->objConfig = $this->getObject('altconfig', 'config');
        $this->objMkdir = $this->getObject('mkdir', 'files');
        $this->objCleanUrl = $this->getObject('cleanurl', 'filemanager');
    }

    /**
     * Return the public URL of a course image.
     *
     * @param string $contextCode Course context code.
     *
     * @return string|false Public image URL, or false when none exists.
     */
    public function getContextImage($contextCode)
    {
        $basePath = rtrim($this->objConfig->getcontentBasePath(), '/');
        $contentPath = rtrim($this->objConfig->getcontentPath(), '/');

        foreach ($this->supportedExtensions() as $extension) {
            $filename = $contextCode . '.' . $extension;
            if (is_file($basePath . '/contextimage/' . $filename)) {
                return $this->objCleanUrl->cleanUpUrl(
                    $contentPath . '/contextimage/' . $filename
                );
            }
        }

        return false;
    }

    /**
     * Copy a validated File Manager image into canonical course-image storage.
     *
     * The native picker accepts modern browser image formats. Detection uses
     * the actual image MIME type rather than the supplied filename extension.
     *
     * @param string $contextCode Course context code.
     * @param string $fileId      File Manager record ID.
     *
     * @return bool Whether the course image was saved.
     */
    public function setContextImage($contextCode, $fileId)
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', (string) $contextCode)) {
            return false;
        }
        if (!$this->checkContextImageFolder()) {
            return false;
        }

        $sourceFile = $this->objFiles->getFilePath($fileId);
        if ($sourceFile === false || !is_file($sourceFile)) {
            return false;
        }

        $imageInfo = @getimagesize($sourceFile);
        if (!is_array($imageInfo) || empty($imageInfo['mime'])) {
            return false;
        }
        $extension = $this->extensionForMime($imageInfo['mime']);
        if ($extension === null) {
            return false;
        }

        $destinationFolder = rtrim(
            $this->objConfig->getcontentBasePath(),
            '/'
        ) . '/contextimage';
        $destination = $destinationFolder . '/' . $contextCode . '.' . $extension;
        $temporary = $destinationFolder . '/.' . $contextCode . '-'
            . str_replace('.', '', uniqid('', true)) . '.' . $extension;

        if (!copy($sourceFile, $temporary)) {
            return false;
        }
        if (!rename($temporary, $destination)) {
            @unlink($temporary);
            return false;
        }

        foreach ($this->supportedExtensions() as $oldExtension) {
            $oldImage = $destinationFolder . '/' . $contextCode . '.' . $oldExtension;
            if ($oldImage !== $destination && is_file($oldImage)) {
                if (!unlink($oldImage)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Remove every supported representation of a course image.
     *
     * @param string $contextCode Course context code.
     *
     * @return bool Whether all existing representations were removed.
     */
    public function removeContextImage($contextCode)
    {
        $basePath = rtrim($this->objConfig->getcontentBasePath(), '/');
        $success = true;

        foreach ($this->supportedExtensions() as $extension) {
            $image = $basePath . '/contextimage/' . $contextCode . '.' . $extension;
            if (is_file($image) && !unlink($image)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Ensure canonical course-image storage exists.
     *
     * @return bool Whether the storage directory is available.
     */
    private function checkContextImageFolder()
    {
        $path = rtrim($this->objConfig->getcontentBasePath(), '/')
            . '/contextimage';
        return (bool) $this->objMkdir->mkdirs($path);
    }

    /**
     * Return the image extensions accepted by the native picker.
     *
     * @return array Supported canonical extensions.
     */
    private function supportedExtensions()
    {
        return array('jpg', 'png', 'gif', 'webp', 'avif');
    }

    /**
     * Map a detected image MIME type to its canonical stored extension.
     *
     * @param string $mime Detected MIME type.
     *
     * @return string|null Canonical extension, or null for a non-image type.
     */
    private function extensionForMime($mime)
    {
        $types = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
        );
        $mime = strtolower(trim((string) $mime));
        return isset($types[$mime]) ? $types[$mime] : null;
    }
}
