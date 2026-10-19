<?php
/** Retired PEAR-channel packager; releases are assembled from versioned source. */
if (PHP_SAPI !== 'cli') {
    http_response_code(410);
    exit;
}
fwrite(STDERR, "The PEAR-channel packager is retired. Use the versioned release assembly workflow.\n");
exit(64);
