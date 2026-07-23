<?php

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class fileapi extends ChisimbaObject
{
    private $objFiles;
    private $objFolders;
    private $objUser;
    private $objFileManagerObject;
    private $objUpload;

    public function init()
    {
        $this->objFiles = $this->getObject('dbfile', 'filemanager');
        $this->objFolders = $this->getObject('dbfolder', 'filemanager');
        $this->objUser = $this->getObject('user', 'security');
        $this->objFileManagerObject = $this->getObject('filemanagerobject', 'filemanager');
        $this->objUpload = $this->getObject('upload', 'filemanager');
    }

    public function listUserImages($folderId = null)
    {
        $folderResult = $this->resolveUserFolder($folderId);
        if (empty($folderResult['ok'])) {
            return $folderResult;
        }

        $rootPath = $folderResult['rootPath'];
        $folderId = $folderResult['folder']['id'];
        $folderPath = $folderResult['folder']['folderpath'];

        $subfolders = $this->objFolders->getSubFolders($folderId);
        if (!is_array($subfolders)) {
            $subfolders = array();
        }

        $files = $this->objFiles->getFolderFiles($folderPath);
        if (!is_array($files)) {
            $files = array();
        }

        $folderItems = array();
        foreach ($subfolders as $subfolder) {
            if (!is_array($subfolder) || empty($subfolder['id']) || empty($subfolder['folderpath'])) {
                continue;
            }

            $subfolderPath = trim((string) $subfolder['folderpath'], '/');
            if (!$this->isInsideUserRoot($subfolderPath, $rootPath)) {
                continue;
            }

            $folderItems[] = array(
                'id' => (string) $subfolder['id'],
                'name' => basename($subfolderPath),
                'path' => $subfolderPath,
            );
        }

        $imageItems = array();
        foreach ($files as $file) {
            if (!is_array($file) || empty($file['id']) || empty($file['filename'])) {
                continue;
            }

            $category = isset($file['category']) ? strtolower((string) $file['category']) : '';
            $mimetype = isset($file['mimetype']) ? strtolower((string) $file['mimetype']) : '';

            if ($category !== 'images' && strpos($mimetype, 'image/') !== 0) {
                continue;
            }

            $imageItems[] = $this->imageItem($file);
        }

        usort($folderItems, array($this, 'sortByName'));
        usort($imageItems, array($this, 'sortByName'));

        $parentId = null;
        if ($folderPath !== $rootPath) {
            $parentPath = dirname($folderPath);
            $parentId = $this->objFolders->getFolderId($parentPath);
            if (!$parentId) {
                $parentId = null;
            }
        }

        return array(
            'ok' => true,
            'folder' => array(
                'id' => (string) $folderId,
                'name' => basename($folderPath),
                'path' => $folderPath,
                'isRoot' => ($folderPath === $rootPath),
                'parentId' => $parentId === null ? null : (string) $parentId,
            ),
            'folders' => $folderItems,
            'files' => $imageItems,
            'capabilities' => array(
                'browse' => true,
                'upload' => false,
                'manage' => false,
            ),
        );
    }

    /**
     * Upload one raster image into the current user's selected folder.
     */
    public function uploadUserImage($folderId, $inputName = 'image')
    {
        $folderResult = $this->resolveUserFolder($folderId);
        if (empty($folderResult['ok'])) {
            return $folderResult;
        }

        if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
            return $this->error('no_file', $this->objLanguage->languageText('mod_filemanager_native_choose_image', 'filemanager'));
        }

        $file = $_FILES[$inputName];
        $uploadError = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            return $this->error('upload_failed', $this->uploadErrorMessage($uploadError));
        }

        $name = isset($file['name']) ? (string) $file['name'] : '';
        $tmpName = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp');

        if ($name === '' || !in_array($extension, $allowedExtensions, true)) {
            return $this->error('invalid_extension', $this->objLanguage->languageText('mod_filemanager_native_image_types', 'filemanager'));
        }

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return $this->error('invalid_upload', $this->objLanguage->languageText('mod_filemanager_native_invalid_upload', 'filemanager'));
        }

        $detectedMime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedMime = (string) finfo_file($finfo, $tmpName);
                finfo_close($finfo);
            }
        }
        if ($detectedMime === '' && function_exists('mime_content_type')) {
            $detectedMime = (string) mime_content_type($tmpName);
        }
        if (strpos(strtolower($detectedMime), 'image/') !== 0) {
            return $this->error('invalid_mimetype', $this->objLanguage->languageText('mod_filemanager_native_invalid_image', 'filemanager'));
        }

        $this->objUpload->setUploadFolder($folderResult['folder']['folderpath']);
        $this->objUpload->enableOverwriteIncrement = true;
        $result = $this->objUpload->uploadFile($inputName, $allowedExtensions);

        if (!is_array($result) || empty($result['success']) || empty($result['fileid'])) {
            $reason = is_array($result) && isset($result['reason']) ? (string) $result['reason'] : 'upload_failed';
            return $this->error($reason, $this->uploadFailureMessage($reason));
        }

        // uploadFile() already returns the canonical details required by
        // the picker. Do not immediately re-enter the legacy dbfile metadata
        // path here: the physical file and database row have already been
        // saved, and a failure while reloading ancillary metadata must not
        // turn a successful upload into an empty HTTP response.
        $fileId = (string) $result['fileid'];
        $filename = isset($result['name'])
            ? (string) $result['name']
            : (isset($file['name']) ? (string) $file['name'] : '');

        $params = array(
            'action' => 'file',
            'id' => $fileId,
            'filename' => $filename,
        );

        if ($extension !== '') {
            $params['type'] = '.' . ltrim($extension, '.');
        }

        $fileItem = array(
            'id' => $fileId,
            'name' => $filename,
            'url' => html_entity_decode(
                $this->objFileManagerObject->uri(
                    $params,
                    'filemanager',
                    '',
                    false,
                    false,
                    true
                ),
                ENT_QUOTES,
                'UTF-8'
            ),
            'mimetype' => isset($result['mimetype'])
                ? (string) $result['mimetype']
                : $detectedMime,
            'size' => isset($result['size'])
                ? (int) $result['size']
                : (isset($file['size']) ? (int) $file['size'] : 0),
        );

        return array(
            'ok' => true,
            'file' => $fileItem,
            'folderId' => (string) $folderResult['folder']['id'],
        );
    }

    public function sortByName($left, $right)
    {
        return strcasecmp(
            isset($left['name']) ? (string) $left['name'] : '',
            isset($right['name']) ? (string) $right['name'] : ''
        );
    }

    private function resolveUserFolder($folderId)
    {
        $userId = (string) $this->objUser->userId();
        $rootPath = 'users/' . $userId;
        $rootId = $this->objFolders->getFolderId($rootPath);

        if (!$rootId) {
            return $this->error('user_root_not_found', $this->objLanguage->languageText('mod_filemanager_native_user_root_missing', 'filemanager'));
        }
        if ($folderId === null || trim((string) $folderId) === '') {
            $folderId = $rootId;
        }

        $folder = $this->objFolders->getFolder($folderId);
        if (!$folder || !isset($folder['folderpath'])) {
            return $this->error('folder_not_found', $this->objLanguage->languageText('mod_filemanager_native_folder_missing', 'filemanager'));
        }

        $folderPath = trim((string) $folder['folderpath'], '/');
        if (!$this->isInsideUserRoot($folderPath, $rootPath)) {
            return $this->error('folder_forbidden', $this->objLanguage->languageText('mod_filemanager_native_folder_outside_root', 'filemanager'));
        }

        $folder['folderpath'] = $folderPath;
        return array(
            'ok' => true,
            'rootPath' => $rootPath,
            'rootId' => (string) $rootId,
            'folder' => $folder,
        );
    }

    private function imageItem($file)
    {
        $params = array(
            'action' => 'file',
            'id' => $file['id'],
            'filename' => $file['filename'],
        );
        if (!empty($file['datatype'])) {
            $params['type'] = '.' . ltrim((string) $file['datatype'], '.');
        }

        $item = array(
            'id' => (string) $file['id'],
            'name' => (string) $file['filename'],
            'url' => html_entity_decode(
                $this->objFileManagerObject->uri($params, 'filemanager', '', false, false, true),
                ENT_QUOTES,
                'UTF-8'
            ),
            'mimetype' => isset($file['mimetype']) ? (string) $file['mimetype'] : '',
            'size' => isset($file['filesize']) ? (int) $file['filesize'] : 0,
        );
        if (isset($file['width']) && $file['width'] !== '') {
            $item['width'] = (int) $file['width'];
        }
        if (isset($file['height']) && $file['height'] !== '') {
            $item['height'] = (int) $file['height'];
        }
        return $item;
    }

    private function uploadErrorMessage($code)
    {
        switch ((int) $code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return $this->objLanguage->languageText('mod_filemanager_native_image_too_large', 'filemanager');
            case UPLOAD_ERR_PARTIAL:
                return $this->objLanguage->languageText('mod_filemanager_native_upload_incomplete', 'filemanager');
            case UPLOAD_ERR_NO_FILE:
                return $this->objLanguage->languageText('mod_filemanager_native_choose_image', 'filemanager');
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                return $this->objLanguage->languageText('mod_filemanager_native_server_store_failed', 'filemanager');
            default:
                return $this->objLanguage->languageText('mod_filemanager_native_image_upload_failed', 'filemanager');
        }
    }

    private function uploadFailureMessage($reason)
    {
        switch ($reason) {
            case 'doesnotmeetextension': return $this->objLanguage->languageText('mod_filemanager_native_image_types', 'filemanager');
            case 'bannedfile': return $this->objLanguage->languageText('mod_filemanager_native_file_type_not_permitted', 'filemanager');
            case 'partialuploaded': return $this->objLanguage->languageText('mod_filemanager_native_upload_incomplete', 'filemanager');
            case 'nouploadedfileprovided': return $this->objLanguage->languageText('mod_filemanager_native_choose_image', 'filemanager');
            case 'filecouldnotbesaved': return $this->objLanguage->languageText('mod_filemanager_native_server_save_failed', 'filemanager');
            case 'needsoverwrite': return $this->objLanguage->languageText('mod_filemanager_native_image_exists', 'filemanager');
            default: return $this->objLanguage->languageText('mod_filemanager_native_image_could_not_upload', 'filemanager');
        }
    }

    private function isInsideUserRoot($path, $rootPath)
    {
        return $path === $rootPath || strpos($path, $rootPath . '/') === 0;
    }

    private function error($code, $message)
    {
        return array(
            'ok' => false,
            'error' => array(
                'code' => $code,
                'message' => $message,
            ),
        );
    }
}
