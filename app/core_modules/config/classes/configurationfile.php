<?php
/**
 * Checked, atomic configuration replacement on a trusted local filesystem.
 * GPL-2.0-or-later. Author: Derek Keats.
 * Cooperating writers use a persistent sidecar lock; stale snapshots fail closed.
 */
class ChisimbaConfigurationFile
{
    /** Create a private configuration directory; failures remain checked. */
    public function ensureDirectory($path, $recursive = false)
    {
        if (!is_dir($path) && !$this->checked(function () use ($path,$recursive) { return mkdir($path,0700,$recursive); }) && !is_dir($path)) {
            throw new RuntimeException('Configuration directory could not be created.');
        }
    }

    /** Read only regular, non-linked files. null denotes an absent destination. */
    public function read($path)
    {
        clearstatcache(true, $path);
        if (is_link($path)) {
            throw new RuntimeException('Configuration links are not supported.');
        }
        if (!file_exists($path)) {
            return null;
        }
        $stat = stat($path);
        if (!is_file($path) || $stat['nlink'] !== 1) {
            throw new RuntimeException('Configuration must be a regular file.');
        }
        $bytes = $this->checked(function () use ($path) { return file_get_contents($path); });
        if ($bytes === false) {
            throw new RuntimeException('Configuration could not be read.');
        }
        return $bytes;
    }

    /** Replace only the revision previously read, preserving existing metadata. */
    public function replace($path, $bytes, $expected)
    {
        $directory = realpath(dirname($path));
        if ($directory === false || !is_dir($directory)) {
            throw new RuntimeException('Configuration directory is unavailable.');
        }
        $path = $directory . DIRECTORY_SEPARATOR . basename($path);
        $lockPath = $path . '.lock';
        $lock = null;
        $stream = null;
        $temporary = null;
        try {
            $lock = $this->acquireLock($lockPath);
            $current = $this->read($path);
            if ($current !== $expected) {
                throw new RuntimeException('Configuration changed; reload before saving.');
            }
            $metadata = $current === null ? null : stat($path);
            $temporary = $directory . '/.chisimba-config-' . bin2hex(random_bytes(12));
            $mask = umask(0077);
            try {
                $stream = $this->checked(function () use ($temporary) { return fopen($temporary, 'x+b'); });
            } finally {
                umask($mask);
            }
            if (!$stream) {
                throw new RuntimeException('Configuration temporary file is unavailable.');
            }
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = $this->writeChunk($stream, substr($bytes, $offset));
                if ($written === false || $written <= 0) {
                    throw new RuntimeException('Configuration write failed.');
                }
                $offset += $written;
            }
            if (!$this->flush($stream)) {
                throw new RuntimeException('Configuration flush failed.');
            }
            if ($metadata !== null) {
                $created = fstat($stream);
                if (($created['uid'] !== $metadata['uid'] && !$this->checked(function () use ($temporary, $metadata) { return chown($temporary, $metadata['uid']); })) ||
                    ($created['gid'] !== $metadata['gid'] && !$this->checked(function () use ($temporary, $metadata) { return chgrp($temporary, $metadata['gid']); }))) {
                    throw new RuntimeException('Configuration ownership could not be preserved.');
                }
                if (!$this->checked(function () use ($temporary, $metadata) { return chmod($temporary, $metadata['mode'] & 0777); })) {
                    throw new RuntimeException('Configuration permissions could not be preserved.');
                }
            }
            if (!fclose($stream)) {
                $stream = null;
                throw new RuntimeException('Configuration close failed.');
            }
            $stream = null;
            // Detect changes from non-cooperating writers before the rename too.
            if ($this->read($path) !== $expected || !$this->move($temporary, $path)) {
                throw new RuntimeException('Configuration replacement failed.');
            }
            $temporary = null;
            clearstatcache(true, $path);
        } finally {
            if (is_resource($stream)) { fclose($stream); }
            if ($temporary !== null && is_file($temporary)) { unlink($temporary); }
            if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
            // Never unlink the lock: waiters must continue to lock the same inode.
        }
    }

    /** Coordinate a complete multi-step caller operation in a trusted directory. */
    public function synchronise($path, $operation)
    {
        $lock = $this->acquireLock($path . '.lock');
        try { return $operation(); }
        finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    private function acquireLock($lockPath)
    {
        $lock = null;
        try {
            if (is_link($lockPath) || (file_exists($lockPath) && !is_file($lockPath))) {
                throw new RuntimeException('Configuration lock is invalid.');
            }
            // Persistent locks are opened without truncation after validation. The
            // parent directory must not be writable by untrusted users.
            $mask = umask(0077);
            try {
                $lock = $this->checked(function () use ($lockPath) { return fopen($lockPath, 'c+b'); });
            } finally {
                umask($mask);
            }
            if (!$lock || !is_file($lockPath) || fstat($lock)['nlink'] !== 1 || !$this->checked(function () use ($lock) { return flock($lock, LOCK_EX); })) {
                throw new RuntimeException('Configuration lock is unavailable.');
            }
            return $lock;
        } catch (Throwable $e) {
            if (is_resource($lock)) { fclose($lock); }
            throw $e;
        }
    }

    /** Narrow filesystem failure boundary; never leak paths or configuration data. */
    protected function checked($operation)
    {
        set_error_handler(static function () { return true; });
        try { return $operation(); } finally { restore_error_handler(); }
    }

    /** Operation seams permit deterministic disk-full/flush/rename failure tests. */
    protected function writeChunk($stream, $bytes)
    {
        return $this->checked(function () use ($stream, $bytes) { return fwrite($stream, $bytes); });
    }
    protected function flush($stream)
    {
        return $this->checked(function () use ($stream) { return fflush($stream) && fsync($stream); });
    }
    protected function move($source, $destination)
    {
        return $this->checked(function () use ($source, $destination) { return rename($source, $destination); });
    }
}
