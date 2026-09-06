<?php
/** Prevent literal role and context names returning to active core language defaults. */
$root = dirname(__DIR__) . '/app/core_modules';
$modules = array('context', 'contextadmin', 'contextgroups', 'toolbar', 'security', 'groupadmin', 'useradmin', 'postlogin');
$allowed = array(
    'context:mod_context_author', 'context:mod_context_authors',
    'context:mod_context_readonly',
);
$failures = array();
foreach ($modules as $module) {
    $lines = file($root . '/' . $module . '/register.conf', FILE_IGNORE_NEW_LINES);
    foreach ($lines ?: array() as $number => $line) {
        if (!preg_match('/^(?:TEXT|USES):\s*([^|]+)\|[^|]*\|(.*)$/', $line, $match)) {
            continue;
        }
        if (in_array($module . ':' . trim($match[1]), $allowed, true)) {
            continue;
        }
        $withoutTokens = preg_replace('/\[-(?:author|authors|readonly|readonlys|context|contexts|organisation|organisations)-\]/i', '', $match[2]);
        if (preg_match('/\b(?:course|courses|student|students|lecturer|lecturers|learner|learners)\b/i', $withoutTokens)) {
            $failures[] = $module . '/register.conf:' . ($number + 1) . ': ' . $match[2];
        }
    }
}
if ($failures) {
    fwrite(STDERR, "Hard-coded role or context terminology found:\n" . implode("\n", $failures) . "\n");
    exit(1);
}
fwrite(STDOUT, "PASS: active core language defaults preserve system terminology.\n");
