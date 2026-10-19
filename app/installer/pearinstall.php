<?php
/** Retired: application dependencies are versioned and deployed with the release. */
if (PHP_SAPI === 'cli') {
    fwrite(STDERR, "The PEAR web installer is retired. Deploy the versioned application bundle.\n");
    exit(64);
}
http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "The PEAR web installer is retired. Deploy the versioned application bundle.\n";
