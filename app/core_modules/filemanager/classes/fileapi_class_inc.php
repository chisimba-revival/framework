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
    private $objLanguage;
    private $objContext;

    public function init()
    {
        $this->objFiles = $this->getObject('dbfile', 'filemanager');
        $this->objFolders = $this->getObject('dbfolder', 'filemanager');
        $this->objUser = $this->getObject('user', 'security');
        $this->objFileManagerObject = $this->getObject('filemanagerobject', 'filemanager');
        $this->objUpload = $this->getObject('upload', 'filemanager');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objContext = $this->getObject('dbcontext', 'context');
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

    /* CHISIMBA_GENERIC_FILE_PICKER_START */
    /* CHISIMBA_PICKER_LOCATIONS: authorised personal/course roots */
    public function listUserFiles($policyName, $folderId = null, $location = '')
    {
        $policy = $this->filePolicy($policyName);
        if ($policy === null) { return $this->error('unknown_policy', $this->objLanguage->languageText('mod_filemanager_picker_unknown_policy', 'filemanager')); }
        $folderResult = $this->resolvePickerFolder($location, $folderId);
        if (empty($folderResult['ok'])) { return $folderResult; }
        $folder = $folderResult['folder'];
        $folders = array();
        foreach ((array) $this->objFolders->getSubFolders($folder['id']) as $item) {
            if (!is_array($item) || empty($item['id']) || empty($item['folderpath'])) { continue; }
            $path = trim((string) $item['folderpath'], '/');
            if (!$this->isInsideRoot($path, $folderResult['rootPath'])) { continue; }
            $folders[] = array('id'=>(string)$item['id'], 'name'=>basename($path), 'path'=>$path);
        }
        $files = array();
        foreach ((array) $this->objFiles->getFolderFiles($folder['folderpath']) as $file) {
            if (!$this->fileMatchesPolicy($file, $policy)) { continue; }
            $files[] = $this->genericFileItem($file);
        }
        usort($folders, array($this, 'sortByName')); usort($files, array($this, 'sortByName'));
        $parentId = null;
        if ($folder['folderpath'] !== $folderResult['rootPath']) {
            $parentId = $this->objFolders->getFolderId(dirname($folder['folderpath'])) ?: null;
        }
        return array('ok'=>true, 'policy'=>$policyName,
            'policyDetails'=>array('accept'=>$policy['accept'], 'titleKey'=>$policy['titleKey'],
                'uploadKey'=>$policy['uploadKey'], 'icon'=>$policy['icon']),
            'location'=>$folderResult['location'], 'locations'=>$folderResult['locations'],
            'folder'=>array('id'=>(string)$folder['id'], 'name'=>basename($folder['folderpath']),
                'path'=>$folder['folderpath'], 'isRoot'=>$folder['folderpath']===$folderResult['rootPath'],
                'parentId'=>$parentId===null?null:(string)$parentId),
            'folders'=>$folders, 'files'=>$files,
            'capabilities'=>array('browse'=>true,'upload'=>$folderResult['canUpload'],'manage'=>false));
    }

    public function uploadUserFile($policyName, $folderId, $inputName = 'file', $location = '')
    {
        $policy = $this->filePolicy($policyName);
        if ($policy === null) { return $this->error('unknown_policy', $this->objLanguage->languageText('mod_filemanager_picker_unknown_policy', 'filemanager')); }
        $folderResult = $this->resolvePickerFolder($location, $folderId);
        if (empty($folderResult['ok'])) { return $folderResult; }
        if (empty($folderResult['canUpload'])) {
            return $this->error('upload_forbidden', $this->objLanguage->languageText('mod_filemanager_picker_upload_forbidden', 'filemanager'));
        }
        if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
            return $this->error('no_file', $this->objLanguage->languageText('mod_filemanager_picker_choose_file', 'filemanager'));
        }
        $file=$_FILES[$inputName]; $error=isset($file['error'])?(int)$file['error']:UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) { return $this->error('upload_failed', $this->uploadErrorMessage($error)); }
        $name=isset($file['name'])?(string)$file['name']:''; $tmp=isset($file['tmp_name'])?(string)$file['tmp_name']:'';
        $ext=strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $policy['extensions'], true)) {
            return $this->error('invalid_extension', $this->objLanguage->languageText('mod_filemanager_picker_invalid_type', 'filemanager'));
        }
        if ($tmp==='' || !is_uploaded_file($tmp)) { return $this->error('invalid_upload', $this->objLanguage->languageText('mod_filemanager_native_invalid_upload', 'filemanager')); }
        $mime=''; if (function_exists('finfo_open')) { $fi=finfo_open(FILEINFO_MIME_TYPE); if ($fi) { $mime=(string)finfo_file($fi,$tmp); finfo_close($fi); } }
        if (!in_array(strtolower($mime), $policy['mimetypes'], true)) {
            return $this->error('invalid_mimetype', $this->objLanguage->languageText('mod_filemanager_picker_invalid_type', 'filemanager'));
        }
        $this->objUpload->setUploadFolder($folderResult['folder']['folderpath']);
        $this->objUpload->enableOverwriteIncrement=true;
        // Picker policies already validate extension and detected MIME type.
        // Obsolete media probing must not take over this HTML response.
        $this->objUpload->skipLegacyMediaAnalysis=true;
        // Use File Manager's established general upload path. The legacy
        // single-file helper assumes image-oriented response handling for
        // some media and may terminate the picker request for audio files.
        $results=$this->objUpload->uploadFiles();
        $result = is_array($results) ? current($results) : null;
        if (!is_array($result)) {
            $result = null;
        }
        if (!is_array($result)||empty($result['success'])||empty($result['fileid'])) {
            $reason=is_array($result)&&isset($result['reason'])?(string)$result['reason']:'upload_failed';
            return $this->error($reason,$this->uploadFailureMessage($reason));
        }
        $item=array('id'=>(string)$result['fileid'],'name'=>isset($result['name'])?(string)$result['name']:$name,
            'url'=>html_entity_decode($this->objFileManagerObject->uri(array('action'=>'file','id'=>$result['fileid'],'filename'=>$name,'type'=>'.'.$ext),'filemanager','','',false,true),ENT_QUOTES,'UTF-8'),
            'mimetype'=>isset($result['mimetype'])?(string)$result['mimetype']:$mime,'extension'=>$ext,
            'size'=>isset($result['size'])?(int)$result['size']:(isset($file['size'])?(int)$file['size']:0));
        return array('ok'=>true,'file'=>$item,'folderId'=>(string)$folderResult['folder']['id']);
    }

    private function pickerLocations()
    {
        $locations = array();
        $userPath = 'users/' . (string) $this->objUser->userId();
        $userId = $this->objFolders->getFolderId($userPath);
        if ($userId) {
            $locations['user'] = array('key'=>'user','id'=>(string)$userId,'path'=>$userPath,
                'label'=>$this->objLanguage->languageText('mod_filemanager_picker_my_files','filemanager'),'canUpload'=>true);
        }
        $contextCode = trim((string) $this->objContext->getContextCode());
        if ($contextCode !== '') {
            $contextPath = 'context/' . $contextCode;
            $contextId = $this->objFolders->getFolderId($contextPath);
            if ($contextId) {
                $locations['context'] = array('key'=>'context','id'=>(string)$contextId,'path'=>$contextPath,
                    'label'=>$this->objLanguage->languageText('mod_filemanager_picker_course_files','filemanager'),
                    'canUpload'=>(bool)$this->objFolders->checkPermissionUploadFolder('context',$contextCode));
            }
        }
        return $locations;
    }

    private function resolvePickerFolder($location, $folderId)
    {
        $locations = $this->pickerLocations();
        if (!$locations) { return $this->error('picker_root_not_found', $this->objLanguage->languageText('mod_filemanager_picker_root_missing','filemanager')); }
        $location = trim((string)$location);
        if ($location === '') { $location = isset($locations['context']) ? 'context' : 'user'; }
        if (!isset($locations[$location])) { return $this->error('location_forbidden', $this->objLanguage->languageText('mod_filemanager_picker_location_forbidden','filemanager')); }
        $root = $locations[$location];
        if ($folderId === null || trim((string)$folderId) === '') { $folderId = $root['id']; }
        $folder = $this->objFolders->getFolder($folderId);
        if (!$folder || !isset($folder['folderpath'])) { return $this->error('folder_not_found', $this->objLanguage->languageText('mod_filemanager_native_folder_missing','filemanager')); }
        $folderPath = trim((string)$folder['folderpath'],'/');
        if (!$this->isInsideRoot($folderPath,$root['path'])) { return $this->error('folder_forbidden', $this->objLanguage->languageText('mod_filemanager_native_folder_outside_root','filemanager')); }
        $folder['folderpath']=$folderPath;
        $publicLocations=array(); foreach ($locations as $item) { $publicLocations[]=array('key'=>$item['key'],'label'=>$item['label'],'id'=>$item['id']); }
        return array('ok'=>true,'location'=>$location,'rootPath'=>$root['path'],'rootId'=>$root['id'],
            'canUpload'=>(bool)$root['canUpload'],'locations'=>$publicLocations,'folder'=>$folder);
    }

    private function isInsideRoot($path,$rootPath)
    {
        return $path === $rootPath || strpos($path,$rootPath.'/') === 0;
    }

    private function filePolicy($name)
    {
        $policies = array(
            'image' => array(
                'extensions' => array('jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'),
                'mimetypes' => array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'),
                'accept' => 'image/jpeg,image/png,image/gif,image/webp,image/avif,.jpg,.jpeg,.png,.gif,.webp,.avif',
                'titleKey' => 'select_image', 'uploadKey' => 'upload_image', 'icon' => 'image'
            ),
            'audio' => array(
                'extensions' => array('mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'),
                'mimetypes' => array('audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a', 'audio/aac', 'audio/flac', 'audio/x-flac'),
                'accept' => 'audio/mpeg,audio/wav,audio/x-wav,audio/ogg,audio/mp4,audio/x-m4a,audio/aac,audio/flac,audio/x-flac,.mp3,.wav,.ogg,.m4a,.aac,.flac',
                'titleKey' => 'select_audio', 'uploadKey' => 'upload_audio', 'icon' => 'audio'
            ),
            'pdf' => array(
                'extensions' => array('pdf'), 'mimetypes' => array('application/pdf'),
                'accept' => 'application/pdf,.pdf',
                'titleKey' => 'select_pdf', 'uploadKey' => 'upload_pdf', 'icon' => 'pdf'
            ),
            'zip' => array(
                'extensions' => array('zip'),
                'mimetypes' => array('application/zip', 'application/x-zip-compressed'),
                'accept' => 'application/zip,application/x-zip-compressed,.zip',
                'titleKey' => 'select_zip', 'uploadKey' => 'upload_zip', 'icon' => 'zip'
            )
        );
        return isset($policies[$name]) ? $policies[$name] : null;
    }
    private function fileMatchesPolicy($file,$policy)
    {
        if (!is_array($file)||empty($file['id'])||empty($file['filename'])) { return false; }
        $ext=strtolower(!empty($file['datatype'])?$file['datatype']:pathinfo($file['filename'],PATHINFO_EXTENSION));
        $mime=strtolower(isset($file['mimetype'])?$file['mimetype']:'');
        return in_array(ltrim($ext,'.'),$policy['extensions'],true) && in_array($mime,$policy['mimetypes'],true);
    }
    private function genericFileItem($file)
    {
        $ext=strtolower(ltrim(isset($file['datatype'])?$file['datatype']:pathinfo($file['filename'],PATHINFO_EXTENSION),'.'));
        return array('id'=>(string)$file['id'],'name'=>(string)$file['filename'],
            'url'=>html_entity_decode($this->objFileManagerObject->uri(array('action'=>'file','id'=>$file['id'],'filename'=>$file['filename'],'type'=>'.'.$ext),'filemanager','','',false,true),ENT_QUOTES,'UTF-8'),
            'mimetype'=>isset($file['mimetype'])?(string)$file['mimetype']:'','extension'=>$ext,
            'size'=>isset($file['filesize'])?(int)$file['filesize']:0);
    }
    /* CHISIMBA_GENERIC_FILE_PICKER_END */

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
