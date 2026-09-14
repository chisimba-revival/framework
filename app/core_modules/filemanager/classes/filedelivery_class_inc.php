<?php
/** Authorised course-file delivery; never redirects to publicly served storage. */
class filedelivery extends ChisimbaObject
{
    public function sendCourseFile($file)
    {
        if (!is_array($file) || !str_starts_with((string)($file['filefolder'] ?? ''), 'context/')) {
            $file = false;
        }
        $this->sendRegistered($file, is_array($file) ? $file['path'] : '');
    }

    /** The relative path is parsed against known derivative conventions, never
     * accepted as a general filesystem request. */
    public function sendDerivative($relative)
    {
        $id = self::derivativeId($relative);
        $file = $id === false ? false : $this->getObject('dbfile', 'filemanager')->getFile($id);
        $this->sendRegistered($file, $relative);
    }

    public static function derivativeId($path)
    {
        if (!is_string($path)) return false;
        if (!preg_match('~^(?:filemanager_thumbnails/(?:large/|medium/)?(?:standard_)?|filemanager_forcemax/)([A-Za-z0-9_-]+)\.(?:jpg|png|gif|webp|htm|pdf|swf)$~D', $path, $m)) return false;
        return $m[1];
    }

    private function sendRegistered($file, $relative)
    {
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        if (!is_array($file) || !$this->getObject('filereadpolicy', 'filemanager')->mayRead($file)) {
            http_response_code(403);
            exit;
        }
        $config = $this->getObject('altconfig', 'config');
        $secure = $this->getObject('dbsysconfig', 'sysconfig')->getValue('SECUREFODLER', 'filemanager');
        $path = self::resolve($secure, $relative);
        // Transitional fallback for verified records not yet migrated. Web-server
        // routing must guard the legacy URL before public copies are removed.
        if ($path === false) $path = self::resolve($config->getcontentBasePath(), $relative);
        if ($path === false) { http_response_code(404); exit; }
        $handle = fopen($path, 'rb');
        if ($handle === false) { http_response_code(404); exit; }
        $stat = fstat($handle);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $plan = self::plan($stat['size'], $method, $_SERVER['HTTP_RANGE'] ?? null);
        http_response_code($plan['status']);
        header('Accept-Ranges: bytes');
        if ($plan['status'] === 405) header('Allow: GET, HEAD');
        if (isset($plan['contentRange'])) header('Content-Range: ' . $plan['contentRange']);
        header('Content-Length: ' . $plan['length']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        // Active uploaded documents must not execute in the application's origin.
        $inline = preg_match('~^(image/(png|jpeg|gif|webp|avif)|audio/|video/|application/pdf$)~', $mime);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment')
            . "; filename*=UTF-8''" . rawurlencode($file['filename']));
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        if ($plan['body']) {
            fseek($handle, $plan['offset']);
            $remaining = $plan['length'];
            while ($remaining > 0 && !feof($handle)) {
                $chunk = fread($handle, min(65536, $remaining));
                if ($chunk === false || $chunk === '') break;
                echo $chunk;
                $remaining -= strlen($chunk);
            }
        }
        fclose($handle);
        exit;
    }

    public static function resolve($root, $relative)
    {
        if (!is_string($root) || $root === '' || !is_string($relative) || $relative === ''
            || $relative[0] === '/' || str_contains($relative, "\0")
            || preg_match('~(^|[\\\\/])\.\.([\\\\/]|$)~', $relative)) return false;
        $base = realpath($root);
        $path = realpath($root . '/' . $relative);
        return $base !== false && $path !== false && is_file($path)
            && str_starts_with($path, $base . DIRECTORY_SEPARATOR) ? $path : false;
    }

    /** Single byte ranges support media seeking. Conditional cache hits are
     * deliberately not issued: each request must pass current authorisation. */
    public static function plan($size, $method, $range = null)
    {
        if (!in_array($method, ['GET', 'HEAD'], true))
            return ['status'=>405, 'length'=>0, 'offset'=>0, 'body'=>false];
        $plan = ['status'=>200, 'length'=>$size, 'offset'=>0, 'body'=>$method === 'GET'];
        if ($range === null || $method === 'HEAD') return $plan;
        // Ignore multi-range and malformed ranges, as allowed by HTTP; never
        // construct an unbounded multipart response from request input.
        if (!preg_match('/^bytes=(\d*)-(\d*)$/D', $range, $m) || ($m[1] === '' && $m[2] === '')) return $plan;
        $start = $m[1] === '' ? max(0, $size - (int)$m[2]) : (int)$m[1];
        $end = $m[1] === '' || $m[2] === '' ? $size - 1 : min($size - 1, (int)$m[2]);
        if ($start >= $size || $start > $end || $size === 0)
            return ['status'=>416, 'length'=>0, 'offset'=>0, 'body'=>false, 'contentRange'=>'bytes */'.$size];
        return ['status'=>206, 'length'=>$end-$start+1, 'offset'=>$start, 'body'=>true,
            'contentRange'=>'bytes '.$start.'-'.$end.'/'.$size];
    }
}
