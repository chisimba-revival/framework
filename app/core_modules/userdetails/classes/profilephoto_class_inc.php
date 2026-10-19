<?php
/** Own-account profile photos, using File Manager ownership and shared raster resizing. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class profilephoto extends ChisimbaObject
{
    public function init() {}

    private function identity(): array
    {
        $user = $this->getObject('user', 'security');
        $uid = (string) $user->userId();
        if (!$user->isLoggedIn() || !preg_match('/^[a-zA-Z0-9_-]+$/D', $uid)) {
            throw new RuntimeException('profile_denied');
        }
        $config = $this->getObject('altconfig', 'config');
        return [$uid, rtrim($config->getsiteRootPath(), '/') . '/user_images'];
    }

    public function change(string $id): void
    {
        [$uid, $destination] = $this->identity();
        $files = $this->getObject('dbfile', 'filemanager');
        $file = $files->getFileInfo($id);
        if (!$file || (string) ($file['userid'] ?? '') !== $uid
            || ((string) ($file['filefolder'] ?? '') !== 'users/' . $uid && !str_starts_with((string) ($file['filefolder'] ?? ''), 'users/' . $uid . '/'))
            || !$this->getObject('filereadpolicy', 'filemanager')->mayRead($file)) {
            throw new RuntimeException('profile_image_access');
        }
        $root = realpath($this->getObject('altconfig', 'config')->getcontentBasePath() . 'users/' . $uid);
        $source = $files->getFullFilePath($id);
        $path = is_string($source) && $source !== '' ? realpath($source) : false;
        if (!$root || !$path || !str_starts_with($path, $root . '/') || !is_file($path)) {
            throw new RuntimeException('profile_image_access');
        }
        if (filesize($path) > 10485760) throw new RuntimeException('profile_image_invalid');
        set_error_handler(static function () { throw new RuntimeException('profile_image_invalid'); });
        try { $info = getimagesize($path); } finally { restore_error_handler(); }
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)
            || $info[0] * $info[1] > 16000000 || filesize($path) > 10485760) {
            throw new RuntimeException('profile_image_invalid');
        }
        $this->locked($destination, $uid, function () use ($path, $destination, $uid) {
            $staged = []; $backups = []; $installed = [];
            try {
                foreach (['' => 100, '_small' => 35] as $suffix => $size) {
                    $target = $destination . '/' . $uid . $suffix . '.png';
                    $temporary = tempnam($destination, '.profile-');
                    if ($temporary === false) throw new RuntimeException('profile_failed');
                    $staged[$target] = $temporary;
                    $resize = $this->newObject('imageresize', 'files');
                    if (!$resize->setImg($path) || !$resize->canCreateFromSouce) throw new RuntimeException('profile_image_invalid');
                    $resize->resize($size, $size, true);
                    // Explicit PNG suffix makes output format independent of the original.
                    if (!rename($temporary, $temporary . '.png')) throw new RuntimeException('profile_failed');
                    $staged[$target] = $temporary . '.png';
                    if (!$resize->store($staged[$target])) throw new RuntimeException('profile_failed');
                    if (is_file($target)) {
                        $backup = tempnam($destination, '.profile-backup-');
                        if ($backup === false) throw new RuntimeException('profile_failed');
                        $backups[$target] = $backup;
                        if (!copy($target, $backup)) throw new RuntimeException('profile_failed');
                    }
                }
                foreach ($staged as $target => $temporary) {
                    if (!chmod($temporary, 0644) || !rename($temporary, $target)) throw new RuntimeException('profile_failed');
                    $installed[] = $target;
                }
            } catch (Throwable $error) {
                foreach ($installed as $target) {
                    if (isset($backups[$target])) rename($backups[$target], $target);
                    elseif (is_file($target)) unlink($target);
                }
                throw $error;
            } finally {
                foreach (array_merge(array_values($staged), array_values($backups)) as $temporary) {
                    if (is_file($temporary)) unlink($temporary);
                }
            }
        });
    }

    public function reset(): void
    {
        [$uid, $destination] = $this->identity();
        $this->locked($destination, $uid, function () use ($uid, $destination) {
            $this->getObject('useradmin_model2', 'security')->removeUserImage($uid);
            foreach (['.png','_small.png','.jpg','_small.jpg'] as $suffix) {
                if (is_file($destination.'/'.$uid.$suffix)) throw new RuntimeException('profile_failed');
            }
        });
    }

    private function locked(string $destination, string $uid, callable $operation): void
    {
        if (!is_dir($destination) || !is_writable($destination)) throw new RuntimeException('profile_failed');
        $lock = fopen(sys_get_temp_dir() . '/chisimba-profile-' . hash('sha256', $destination . '/' . $uid) . '.lock', 'c');
        if (!$lock) throw new RuntimeException('profile_failed');
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException('profile_failed');
            $operation();
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
}
