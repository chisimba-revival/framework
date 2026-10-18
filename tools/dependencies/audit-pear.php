<?php
/** Read-only conservative source inventory; references are evidence, not reachability proof.
 * Usage: php tools/dependencies/audit-pear.php /path/to/modules > audit.json
 * Ignores comments. Includes dormant modules and nested vendors. Does not execute source.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__, 2);
$pear = $root . '/app/lib/pear/';
if (!is_dir($pear)) {
    echo json_encode(['runtime_present'=>false, 'status'=>'PEAR runtime retired; dormant modules must be audited before activation.'], JSON_PRETTY_PRINT), "\n";
    exit;
}
$modules = $argv[1] ?? dirname($root) . '/modules';
if (!is_dir($modules)) { fwrite(STDERR, "Modules directory required\n"); exit(1); }
function sources($dir) {
    $out = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
        if ($f->isFile() && preg_match('/\.(php|inc|sql)$/i', $f->getFilename())) $out[] = $f->getPathname();
    }
    sort($out); return $out;
}
function owner($relative) { return preg_replace('/\.php$/i', '', explode('/', $relative)[0]); }
$classes = []; $paths = []; $packages = [];
foreach (sources($pear) as $file) {
    $relative = substr($file, strlen($pear)); $package = owner($relative);
    $paths[$relative] = $package; $packages[$package] ??= ['files'=>0, 'external'=>[], 'incoming'=>[]];
    $packages[$package]['files']++;
    $tokens = token_get_all(file_get_contents($file));
    foreach ($tokens as $i => $token) {
        if (!is_array($token) || !in_array($token[0], [T_CLASS,T_INTERFACE,T_TRAIT], true)) continue;
        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE,T_COMMENT,T_DOC_COMMENT], true)) $j++;
        if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
            $classes[strtolower($tokens[$j][1])][$package] = true;
        }
    }
}
foreach (array_merge(sources($root . '/app'), sources($modules)) as $file) {
    $internal = str_starts_with($file, $pear);
    $from = $internal ? owner(substr($file, strlen($pear))) : null;
    $label = str_starts_with($file, $root . '/') ? 'framework/' . substr($file, strlen($root)+1) : 'modules/' . substr($file, strlen($modules)+1);
    foreach (token_get_all(file_get_contents($file)) as $token) {
        if (!is_array($token)) continue;
        [$type,$value,$line] = $token; $matches = [];
        if ($type === T_STRING) $matches = array_keys($classes[strtolower($value)] ?? []);
        if ($type === T_CONSTANT_ENCAPSED_STRING) {
            $value = substr($value, 1, -1);
            $matches = array_keys($classes[strtolower($value)] ?? []);
            if (isset($paths[$value])) $matches[] = $paths[$value];
        }
        foreach (array_unique($matches) as $package) {
            if ($internal) {
                if ($from !== $package) $packages[$package]['incoming'][$from] = true;
            } else {
                $packages[$package]['external'][$label . ':' . $line] = $value;
            }
        }
    }
}
ksort($packages);
foreach ($packages as &$p) { ksort($p['external']); $p['incoming'] = array_keys($p['incoming']); sort($p['incoming']); }
echo json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
