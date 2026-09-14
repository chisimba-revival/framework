<?php
/** Read-only storage preflight. Usage: php inventory.php /path/to/ch */
if (PHP_SAPI !== 'cli' || empty($argv[1])) {
    fwrite(STDERR, "Usage: php inventory.php /path/to/ch\n");
    exit(2);
}
$root = realpath($argv[1]);
if (!$root || !is_file($root . '/classes/core/engine_class_inc.php')) {
    throw new RuntimeException('Invalid Chisimba application root');
}
chdir($root);
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';
$GLOBALS['kewl_entry_point_run'] = true;
require 'classes/core/engine_class_inc.php';
$engine = new engine();
$config = $engine->getObject('altconfig', 'config');
$public = rtrim($config->getcontentBasePath(), '/');
$secure = rtrim((string)$engine->getObject('dbsysconfig', 'sysconfig')->getValue('SECUREFODLER', 'filemanager'), '/');
$rows = $engine->getObject('dbfile', 'filemanager')->getArray('SELECT id, path, filefolder, access FROM tbl_files');
if (!is_array($rows)) throw new RuntimeException('Cannot read file inventory');
$summary = ['records'=>count($rows), 'scopes'=>[], 'unsafe_paths'=>0];
foreach ($rows as $file) {
    $scope = str_starts_with($file['filefolder'], 'context/') ? 'course'
        : (str_starts_with($file['filefolder'], 'users/') ? 'personal' : 'unclassified');
    if (!isset($summary['scopes'][$scope])) $summary['scopes'][$scope] = [
        'records'=>0, 'public_originals'=>0, 'public_compatibility_links'=>0, 'secure_originals'=>0,
        'duplicate_originals'=>0, 'missing_originals'=>0, 'public_derivatives'=>0,
        'unset_access'=>0];
    $counts = &$summary['scopes'][$scope];
    $counts['records']++;
    if (empty($file['access'])) $counts['unset_access']++;
    $path = (string)$file['path'];
    if ($path === '' || str_starts_with($path, '/') || preg_match('~(^|[\\\\/])\.\.([\\\\/]|$)~', $path)) {
        $summary['unsafe_paths']++;
        unset($counts);
        continue;
    }
    $a = is_file($public . '/' . $path);
    if ($a && !str_starts_with(realpath($public . '/' . $path), realpath($public) . DIRECTORY_SEPARATOR)) {
        $counts['public_compatibility_links']++;
        $a = false;
    }
    $b = $secure !== '' && is_file($secure . '/' . $path);
    $counts['public_originals'] += (int)$a;
    $counts['secure_originals'] += (int)$b;
    $counts['duplicate_originals'] += (int)($a && $b);
    $counts['missing_originals'] += (int)(!$a && !$b);
    if (preg_match('/^[A-Za-z0-9_-]+$/', $file['id'])) {
        foreach (['filemanager_thumbnails/', 'filemanager_thumbnails/medium/', 'filemanager_thumbnails/large/', 'filemanager_forcemax/'] as $directory) {
            foreach (['jpg','png'] as $extension) {
                $counts['public_derivatives'] += (int)is_file($public . '/' . $directory . $file['id'] . '.' . $extension);
            }
        }
    }
    unset($counts);
}
// Conservative reference discovery. Counts include drafts and private pages;
// a match is a review requirement, never an instruction to publish the file.
$summary['publishing_references'] = [];
$tables = [
    'tbl_simpleblog_posts'=>['post_content','composition_json','legacy_content_html','featured_image'],
    'tbl_contentblocks'=>['body_html','image_url','action_url'],
    'tbl_contextcontent_pages'=>['pagecontent']
];
$db = $engine->getObject('dbfile', 'filemanager');
foreach ($tables as $table=>$fields) {
    $exists = $db->getArray("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$table."'");
    if (!$exists) { $summary['publishing_references'][$table] = ['available'=>false]; continue; }
    $matches = 0; $scanned = 0;
    do {
        $page = $db->getArray('SELECT '.implode(',', $fields).' FROM '.$table.' ORDER BY puid LIMIT 100 OFFSET '.$scanned);
        if (!is_array($page)) throw new RuntimeException('Reference scan failed: '.$table);
        foreach ($page as $record) {
            $text = html_entity_decode(rawurldecode(implode(' ', array_values($record))), ENT_QUOTES, 'UTF-8');
            $text = str_replace('\\/', '/', $text);
            foreach ($rows as $file) {
                if (!str_starts_with($file['filefolder'], 'context/')) continue;
                if (str_contains($text, $file['path']) || str_contains($text, $file['id'])) { $matches++; break; }
            }
        }
        $scanned += count($page);
    } while (count($page) === 100);
    $summary['publishing_references'][$table] = ['available'=>true,'records_scanned'=>$scanned,'records_referencing_course_files'=>$matches];
}
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), "\n";
