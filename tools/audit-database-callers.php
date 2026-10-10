<?php
/** Static migration inventory; reads tracked PHP/INC/SQL source without executing it.
 * @author Derek Keats <derek@dkeats.com>
 * Usage: php tools/audit-database-callers.php /path/to/modules /path/to/output
 */
if (PHP_SAPI !== 'cli' || count($argv) !== 3) {
    fwrite(STDERR, "Usage: php tools/audit-database-callers.php MODULES OUTPUT\n");
    exit(64);
}
$roots = ['framework' => dirname(__DIR__), 'modules' => realpath($argv[1])];
if (!$roots['modules'] || !is_dir($roots['modules'].'/.git') && !is_file($roots['modules'].'/.git')) {
    throw new RuntimeException('A modules Git checkout is required.');
}
$output = $argv[2];
if (!is_dir($output) && !mkdir($output, 0755, true)) throw new RuntimeException('Cannot create output directory.');
$patterns = [
    'mdb2_symbol' => '/\bMDB2(?:_[A-Za-z0-9_]+)?\b/i',
    'native_mdb2_bridge' => '/\bMdb2[A-Za-z0-9_]+\b/',
    'global_connection' => '/\$_globalObjDb(?:Manager)?\b/',
    'alternate_connection' => '/\b(?:DB\s*::\s*connect|mysql_connect|mysqli_connect|pg_connect)\s*\(/',
    'connection_access' => '/\b(?:getDbObj|getDbManagementObj)\s*\(/i',
    'raw_connection_member' => '/->\s*(?:_db|db|objDb|objDB|_objDb|connection)\s*->\s*[A-Za-z_][A-Za-z0-9_]*/',
    'mdb2_result_or_schema' => '/->\s*(?:fetchRow|queryAll|queryRow|queryOne|numRows|numCols|loadModule|quoteIdentifier|setFetchMode|setOption|setCharset|autoExecute|autoPrepare|nextID|mg[A-Z][A-Za-z]+)\s*\(/',
    'driver_state' => '/->\s*(?:phptype|in_transaction|manager|datatype|function|extended)\b/',
    'transaction_or_lock' => '/\b(?:beginTransaction|startTransaction|commit|rollback|inTransaction|GET_LOCK|RELEASE_LOCK|SAVEPOINT|FOR\s+UPDATE)\b/i',
    'pear_error' => '/\b(?:PEAR\s*::\s*isError|PEAR_Error|MDB2_Error)\b/',
    'dbtable_subclass' => '/\bextends\s+dbTable\b/i',
    'schema_subclass' => '/\bextends\s+dbTableManager\b/i',
    'pdo' => '/\bPDO(?:Exception|Statement)?\b/',
];
$rows = []; $summary = ['method'=>'PHP tokenisation removes comments and inline HTML; regex signals are candidates, not call-graph reachability proof.', 'sourceCommits'=>[], 'scannedFiles'=>[], 'signals'=>[], 'owners'=>[]];
foreach ($roots as $repo=>$root) {
    $summary['sourceCommits'][$repo] = trim(shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD'));
    $files = explode("\0", shell_exec('git -C '.escapeshellarg($root).' ls-files -z'));
    $scanned = 0;
    foreach ($files as $path) {
        if ($repo === 'framework' && $path === 'tools/audit-database-callers.php') continue;
        if (!preg_match('/\.(?:php|inc|sql)$/i', $path) || !is_file($root.'/'.$path)) continue;
        $source = file_get_contents($root.'/'.$path);
        if (!str_contains($source, '<?')) continue;
        ++$scanned;
        $clean = '';
        foreach (token_get_all($source) as $token) {
            if (!is_array($token)) { $clean .= $token; continue; }
            $clean .= in_array($token[0], [T_COMMENT,T_DOC_COMMENT,T_INLINE_HTML], true)
                ? preg_replace('/[^\r\n]/', ' ', $token[1]) : $token[1];
        }
        $owner = $repo==='modules' ? explode('/',$path)[0] : (preg_match('~^app/core_modules/([^/]+)/~',$path,$m) ? $m[1] : (str_starts_with($path,'app/installer/')?'installer':'framework-core'));
        $cohort = 'application';
        if (preg_match('~(?:^|/)(?:tests?|examples?|docs|tools|scripts)(?:/|$)~i',$path)) $cohort='test-or-operator';
        if (preg_match('~(?:^|/)(?:lib|vendor|resources|thirdparty|Auth)(?:/|$)~i',$path)) $cohort='embedded-or-resource-review';
        if (preg_match('~(?:_DEPRECATED|__PAUSED|_OLD)(?:/|$)~i',$path)) $cohort='retired-review';
        foreach ($patterns as $signal=>$regex) {
            preg_match_all($regex,$clean,$matches,PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as [$symbol,$offset]) {
                $line=substr_count(substr($clean,0,$offset),"\n")+1;
                $rows[]=[$repo,$path,$line,$owner,$cohort,$signal,preg_replace('/\s+/',' ',$symbol)];
                $summary['signals'][$repo][$cohort][$signal][$path]=true;
                $summary['owners'][$repo][$cohort][$owner][$signal][$path]=true;
            }
        }
    }
    $summary['scannedFiles'][$repo]=$scanned;
}
usort($rows,fn($a,$b)=>[$a[0],$a[1],$a[2],$a[5]]<=>[$b[0],$b[1],$b[2],$b[5]]);
$f=fopen($output.'/callers.csv','w');fputcsv($f,['repository','path','line','owner','cohort','signal','symbol'],',','"','');
foreach($rows as $row) {
    if ($row[4] !== 'embedded-or-resource-review') fputcsv($f,$row,',','"','');
}
fclose($f);
foreach($summary['signals'] as &$cohorts)foreach($cohorts as &$signals)foreach($signals as &$paths)$paths=count($paths);unset($cohorts,$signals,$paths);
foreach($summary['owners'] as &$cohorts)foreach($cohorts as &$owners)foreach($owners as &$signals)foreach($signals as &$paths)$paths=count($paths);unset($cohorts,$owners,$signals,$paths);
file_put_contents($output.'/inventory-summary.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo 'Scanned '.array_sum($summary['scannedFiles']).' tracked PHP-containing files; recorded '.count($rows)." candidate signals (embedded/resource counts in summary; first-party/operator rows in CSV).\n";
