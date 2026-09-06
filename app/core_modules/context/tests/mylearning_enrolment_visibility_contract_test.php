<?php
/**
 * Contract ensuring My Learning includes every canonically enrolled course.
 *
 * @author Derek Keats
 */

$source = file_get_contents(
    dirname(__DIR__) . '/classes/studentlearningoverview_class_inc.php'
);
if ($source === false) {
    fwrite(STDERR, "FAIL: unable to read student learning overview\n");
    exit(1);
}

if (strpos(
    $source,
    "if (!is_array(\$state) || empty(\$state['available']))"
) !== false) {
    fwrite(STDERR, "FAIL: enrolled courses without pages are still discarded\n");
    exit(1);
}

$required = array(
    'getContextWhereStudent($userId)',
    '$hasContent',
    "'mylearningavailable'",
    "'mylearningnocontent'",
    "'mylearningopen'",
    "'action' => 'joincontext'",
);
foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: incomplete no-content enrolled-course journey\n");
        exit(1);
    }
}

if (!str_contains($source, "!\$started && !empty(\$state['sectionid'])")
    || !str_contains($source, "'contextaction' => 'viewsection'")) {
    fwrite(STDERR, "FAIL: new section classes do not start at their informational section\n");
    exit(1);
}

echo "PASS: My Learning retains enrolled courses without content pages.\n";
