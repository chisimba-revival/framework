<?php
/**
 * Provision the File Manager user root for every existing account.
 *
 * Fileapi::pickerLocations() only offers a "user" picker location when a
 * folder row exists at users/<userid>. That row is created by
 * userfoldercheck::checkUserFolder(), which is otherwise only reached lazily
 * from the upload path. An installation whose accounts have never uploaded
 * therefore has no picker root at all, and the REST files resource reports
 * picker_root_not_found even for a legitimate administrator.
 *
 * This script calls that canonical method for each account. It deliberately
 * does not insert folder rows by hand: the physical directory and the
 * tbl_files_folders record are both part of the contract, and checkUserFolder()
 * is what keeps the two in step.
 *
 *
 * Idempotent and repeatable: an account that already has a root is skipped,
 * and no existing row or directory is modified. Nothing runs during a page
 * request.
 *
 * Usage:
 *   php core_modules/filemanager/scripts/provision-user-folders.php [appdir]
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$app = rtrim($argv[1] ?? dirname(__DIR__, 3), '/') . '/';

if (!is_file($app . 'classes/core/engine_class_inc.php')) {
    throw new RuntimeException('Pass the Chisimba application directory');
}

chdir($app);

$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';

$GLOBALS['kewl_entry_point_run'] = true;

require_once 'classes/core/engine_class_inc.php';

$engine = new engine();

// The canonical owner of tbl_users. Accounts are listed through it rather
// than by querying the table here.
$users = $engine->getObject('userservice', 'security');
$rows = $users->listUsers('', true);

if (!is_array($rows)) {
    throw new RuntimeException('Accounts could not be listed');
}

$folderCheck = $engine->getObject('userfoldercheck', 'filemanager');

$provisioned = 0;
$skipped = 0;
$failed = 0;

foreach ($rows as $row) {
    if (!is_array($row) || empty($row['userid'])) {
        continue;
    }

    $userId = (string) $row['userid'];

    if ($folderCheck->checkUserFolder($userId)) {
        echo 'provisioned: ' . $userId . PHP_EOL;
        $provisioned++;
    } else {
        // checkUserFolder() returns FALSE both when it created nothing and
        // when it failed, so this is reported rather than silently counted as
        // already-present.
        echo 'FAILED: ' . $userId . PHP_EOL;
        $failed++;
    }
}

echo 'provisioned ' . $provisioned . '; already present or unchanged '
    . $skipped . '; failed ' . $failed . '.' . PHP_EOL;

if ($failed > 0) {
    exit(1);
}
