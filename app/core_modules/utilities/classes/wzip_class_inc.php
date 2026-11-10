<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * ZIP compatibility service backed by PHP's maintained ZipArchive extension.
 *
 * This preserves the historical wzip public API while removing the bundled
 * PclZip dependency. Extraction is validated before any content is written.
 */
class wzip extends ChisimbaObject
{
    public $error = null;

    public function init()
    {
    }

    private function requireZipArchive()
    {
        if (!class_exists('ZipArchive')) {
            $this->error = 'The PHP ZipArchive extension is unavailable.';
            return false;
        }
        return true;
    }

    private function normaliseEntryName($name)
    {
        $name = str_replace('\\', '/', (string) $name);
        if ($name === '' || strpos($name, "\0") !== false ||
                $name[0] === '/' || preg_match('/^[A-Za-z]:\//', $name)) {
            return false;
        }
        foreach (explode('/', $name) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }
        return $name;
    }

    private function strippedEntryName($name, $removePath)
    {
        $name = $this->normaliseEntryName($name);
        if ($name === false) {
            return false;
        }

        $removePath = trim(str_replace('\\', '/', (string) $removePath), '/');
        if ($removePath === '') {
            return $name;
        }
        if ($name === $removePath || $name === $removePath . '/') {
            return '';
        }
        $prefix = $removePath . '/';
        if (strpos($name, $prefix) === 0) {
            return substr($name, strlen($prefix));
        }
        return $name;
    }

    private function isSymbolicLink($zip, $index)
    {
        $opsys = 0;
        $attributes = 0;
        if (!$zip->getExternalAttributesIndex(
                $index,
                $opsys,
                $attributes,
                ZipArchive::FL_UNCHANGED
            )) {
            return false;
        }
        if ($opsys !== ZipArchive::OPSYS_UNIX) {
            return false;
        }
        return (($attributes >> 16) & 0170000) === 0120000;
    }

    private function extractValidated($filename, $path, $removePath = '')
    {
        $this->error = null;
        if (!$this->requireZipArchive()) {
            return false;
        }
        if (!is_file($filename)) {
            $this->error = 'ZIP archive does not exist.';
            return false;
        }

        $destination = realpath($path);
        if ($destination === false || !is_dir($destination) ||
                !is_writable($destination)) {
            $this->error = 'ZIP destination is unavailable or not writable.';
            return false;
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($filename);
        if ($openResult !== true) {
            $this->error = 'Unable to open ZIP archive (code '
                . (string) $openResult . ').';
            return false;
        }

        $entryLimit = 5000;
        $expandedSizeLimit = 512 * 1024 * 1024;
        $compressionRatioLimit = 250;
        $expandedSize = 0;
        $entries = array();

        if ($zip->numFiles > $entryLimit) {
            $zip->close();
            $this->error = 'ZIP archive contains too many entries.';
            return false;
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index, ZipArchive::FL_UNCHANGED);
            if ($stat === false || !isset($stat['name'])) {
                $zip->close();
                $this->error = 'ZIP archive contains an unreadable entry.';
                return false;
            }

            $sourceName = $this->normaliseEntryName($stat['name']);
            $targetName = $this->strippedEntryName(
                $stat['name'],
                $removePath
            );
            if ($sourceName === false || $targetName === false) {
                $zip->close();
                $this->error = 'ZIP archive contains an unsafe path.';
                return false;
            }
            if ($this->isSymbolicLink($zip, $index)) {
                $zip->close();
                $this->error = 'ZIP archive contains a symbolic link.';
                return false;
            }
            if ($targetName === '') {
                continue;
            }

            $size = isset($stat['size']) ? max(0, (int) $stat['size']) : 0;
            $compressedSize = isset($stat['comp_size'])
                ? max(0, (int) $stat['comp_size'])
                : 0;
            $expandedSize += $size;
            if ($expandedSize > $expandedSizeLimit) {
                $zip->close();
                $this->error = 'ZIP archive exceeds the expanded-size limit.';
                return false;
            }
            if ($compressedSize > 0 &&
                    ($size / $compressedSize) > $compressionRatioLimit) {
                $zip->close();
                $this->error =
                    'ZIP archive contains a suspicious compression ratio.';
                return false;
            }

            $isDirectory = substr($sourceName, -1) === '/';
            $targetPath = $destination . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, rtrim($targetName, '/'));

            if ((!$isDirectory && file_exists($targetPath)) ||
                    ($isDirectory && file_exists($targetPath) &&
                     !is_dir($targetPath))) {
                $zip->close();
                $this->error =
                    'ZIP extraction would overwrite existing content.';
                return false;
            }

            $entries[] = array(
                'source' => $sourceName,
                'target' => $targetPath,
                'directory' => $isDirectory,
            );
        }

        $createdFiles = array();
        $createdDirectories = array();
        $oldUmask = umask(0022);
        $success = true;

        foreach ($entries as $entry) {
            if ($entry['directory']) {
                if (!is_dir($entry['target'])) {
                    if (!mkdir($entry['target'], 0755, true)) {
                        $success = false;
                        $this->error = 'Unable to create an extracted directory.';
                        break;
                    }
                    $createdDirectories[] = $entry['target'];
                }
                continue;
            }

            $parent = dirname($entry['target']);
            if (!is_dir($parent)) {
                if (!mkdir($parent, 0755, true)) {
                    $success = false;
                    $this->error = 'Unable to create an extracted directory.';
                    break;
                }
                $createdDirectories[] = $parent;
            }

            $input = $zip->getStream($entry['source']);
            $output = @fopen($entry['target'], 'xb');
            if ($input === false || $output === false) {
                if (is_resource($input)) {
                    fclose($input);
                }
                if (is_resource($output)) {
                    fclose($output);
                }
                $success = false;
                $this->error = 'Unable to create an extracted file.';
                break;
            }

            $copied = stream_copy_to_stream($input, $output);
            fclose($input);
            fclose($output);
            if ($copied === false) {
                $success = false;
                $this->error = 'Unable to write an extracted file.';
                break;
            }
            $createdFiles[] = $entry['target'];
        }

        umask($oldUmask);
        $zip->close();

        if (!$success) {
            foreach (array_reverse($createdFiles) as $createdFile) {
                if (is_file($createdFile)) {
                    @unlink($createdFile);
                }
            }
            foreach (array_reverse(array_unique($createdDirectories)) as $dir) {
                if (is_dir($dir)) {
                    @rmdir($dir);
                }
            }
            return false;
        }

        return true;
    }

    public function unZipArchive($filename, $path)
    {
        return $this->extractValidated($filename, $path, 'install/release');
    }

    public function unzip($filename, $path)
    {
        return $this->extractValidated($filename, $path, 'install/release');
    }

    private function archiveLocalName($filePath, $removePath)
    {
        $filePath = str_replace('\\', '/', $filePath);
        $removePath = rtrim(str_replace('\\', '/', (string) $removePath), '/');
        if ($removePath !== '' && strpos($filePath, $removePath . '/') === 0) {
            return ltrim(substr($filePath, strlen($removePath)), '/');
        }
        return basename($filePath);
    }

    public function addArchive($path, $filename, $removePath = null)
    {
        $this->error = null;
        if (!$this->requireZipArchive()) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open(
                $filename,
                ZipArchive::CREATE | ZipArchive::OVERWRITE
            ) !== true) {
            $this->error = 'Unable to create ZIP archive.';
            return false;
        }

        $paths = is_array($path) ? $path : array($path);
        foreach ($paths as $item) {
            if (is_file($item)) {
                $zip->addFile(
                    $item,
                    $this->archiveLocalName($item, $removePath)
                );
                continue;
            }
            if (!is_dir($item)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $item,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $entry) {
                $entryPath = $entry->getPathname();
                $localName = $this->archiveLocalName(
                    $entryPath,
                    $removePath
                );
                if ($entry->isDir()) {
                    $zip->addEmptyDir($localName);
                } elseif ($entry->isFile()) {
                    $zip->addFile($entryPath, $localName);
                }
            }
        }

        if (!$zip->close()) {
            $this->error = 'Unable to finish ZIP archive.';
            return false;
        }
        return $filename;
    }

    public function listArchiveFiles($path)
    {
        $this->error = null;
        if (!$this->requireZipArchive()) {
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            $this->error = 'Unable to open ZIP archive.';
            return false;
        }

        $result = array();
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index, ZipArchive::FL_UNCHANGED);
            if ($stat === false) {
                continue;
            }
            $name = isset($stat['name']) ? $stat['name'] : '';
            $result[] = array(
                'filename' => $name,
                'stored_filename' => $name,
                'size' => isset($stat['size']) ? $stat['size'] : 0,
                'compressed_size' => isset($stat['comp_size'])
                    ? $stat['comp_size']
                    : 0,
                'mtime' => isset($stat['mtime']) ? $stat['mtime'] : 0,
                'folder' => substr($name, -1) ===  '/',
                'index' => $index,
                'status' => 'ok',
            );
        }
        $zip->close();
        return $result;
    }

    public function packFilesZip(
        $zipFN,
        $files,
        $removepath = true,
        $movefiles2zip = true
    ) {
        $removePath = null;
        if ($removepath && !empty($files)) {
            $first = reset($files);
            $removePath = dirname($first);
        }
        return $this->addArchive($files, $zipFN, $removePath);
    }

    public function unPackFilesFromZip($zipFN, $dest)
    {
        return $this->extractValidated($zipFN, $dest);
    }
}
?>
