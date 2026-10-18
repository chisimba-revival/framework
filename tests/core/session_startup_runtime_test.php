<?php
/** Real HTTP cookie resumption and active-session checks; disposable runtimes only. */
$runtime = getenv('CHISIMBA_WARNING_TEST_RUNTIME');
if (!$runtime || !is_file($runtime . '/.mcq-bank-disposable')) exit(64);
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
if (PHP_SAPI === 'cli-server') {
    chdir($runtime);
    $mode = $_GET['mode'] ?? '';
    if ($mode === 'seed') {
        session_name('PHPSESSION');
        session_start();
        $_SESSION['warning_test_sentinel'] = 'retained';
        echo 'seeded';
        exit;
    }
    if ($mode === 'active') {
        session_name('PHPSESSION');
        session_start();
    }
    $GLOBALS['kewl_entry_point_run'] = true;
    $_SERVER['REQUEST_METHOD'] = 'CLI'; // Avoid site-root redirects in this private harness.
    $_SERVER['SCRIPT_NAME'] = '/ch/index.php';
    require 'classes/core/engine_class_inc.php';
    try {
        $engine = new engine();
        if (session_status() !== PHP_SESSION_ACTIVE
            || session_id() !== ($_COOKIE['PHPSESSION'] ?? '')
            || ($_SESSION['warning_test_sentinel'] ?? '') !== 'retained') {
            throw new RuntimeException('Cookie session was not resumed intact');
        }
        if ($mode !== 'active' && (ini_get('session.gc_probability') !== '2' || ini_get('session.gc_divisor') !== '100')) {
            throw new RuntimeException('Session settings were not applied before resumption');
        }
        $engine->sessionStart();
        if (session_id() !== $_COOKIE['PHPSESSION']) throw new RuntimeException('Repeated startup changed identity');
        echo 'PASS';
    } finally {
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    }
    exit;
}
if (PHP_SAPI !== 'cli') exit(64);
// Bind only inside the disposable container; no published port or external calls.
$socket = stream_socket_server('tcp://127.0.0.1:0');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$log = tempnam(sys_get_temp_dir(), 'session-warning-');
$process = proc_open([PHP_BINARY, '-d', 'auto_prepend_file=', '-S', $address, __FILE__],
    [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes);
if (!is_resource($process)) throw new RuntimeException('Could not start private session test server');
fclose($pipes[0]);
try {
    for ($i = 0; $i < 50; $i++) {
        $curl = curl_init('http://' . $address . '/?mode=seed');
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 2]);
        $seed = curl_exec($curl);
        if ($seed !== false) break;
        usleep(20000);
    }
    foreach (['resume', 'active'] as $mode) {
        if ($mode === 'active') $seed = curl_exec($curl);
        if (!preg_match('/Set-Cookie: (PHPSESSION=[^;\r\n]+)/i', (string) $seed, $match)) {
            throw new RuntimeException('Private session seed did not set its cookie');
        }
        $request = curl_init('http://' . $address . '/?mode=' . $mode);
        curl_setopt_array($request, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIE => $match[1], CURLOPT_TIMEOUT => 10]);
        $body = curl_exec($request);
        if ($body !== 'PASS') throw new RuntimeException('Session ' . $mode . ' failed: ' . $body);
        echo 'PASS: strict HTTP session ' . $mode . " preserves cookie identity and state\n";
    }
} finally {
    proc_terminate($process);
    proc_close($process);
    unlink($log);
}
