<?php

/**
 * Custom Exception Handler
 *
 * CustomException extends the built in SPL Exception Class.
 *
 * @category  Chisimba
 * @package   core
 */
class customException extends Exception
{
    /**
     * URI
     *
     * Retained for compatibility with older code that may inspect it.
     *
     * @var string
     */
    public $uri;

    /**
     * Config object.
     *
     * @var mixed
     */
    public $_objConfig;

    /**
     * Constructor.
     *
     * @param mixed $m Exception message
     */
    public function __construct($m)
    {
        parent::__construct((string) $m);

        $msg = urlencode((string) $m);

        if (function_exists('log_debug')) {
            log_debug((string) $m);
        }

        self::cleanUp($msg);
    }

    /**
     * Redirect to the standard Chisimba system-error page.
     *
     * This method is static because the Chisimba codebase calls
     * customException::cleanUp() statically in many locations.
     *
     * @param mixed $msg Encoded error message
     * @return void
     */
    public static function diePage($msg)
    {
        if ($msg === 'MDB2+Error%3A+connect+failed') {
            self::dbNoConn($msg);
            return;
        }

        $host = isset($_SERVER['HTTP_HOST'])
            ? $_SERVER['HTTP_HOST']
            : 'localhost';

        $script = isset($_SERVER['PHP_SELF'])
            ? $_SERVER['PHP_SELF']
            : '/index.php';

        $uri = 'http://'
            . $host
            . $script
            . '?module=errors&action=syserr&msg='
            . $msg;

        header('Location: ' . $uri);
    }

    /**
     * Database error handler.
     *
     * @param array $msg User and developer database messages
     * @return void
     */
    public static function dbDeath($msg)
    {
        if (!is_array($msg)) {
            $msg = array((string) $msg, (string) $msg);
        }

        $userMessage = isset($msg[0]) ? $msg[0] : 'Database error';
        $developerMessage = isset($msg[1]) ? $msg[1] : $userMessage;

        if (strstr($userMessage, 'connect failed') === false) {
            $host = isset($_SERVER['HTTP_HOST'])
                ? $_SERVER['HTTP_HOST']
                : 'localhost';

            $script = isset($_SERVER['PHP_SELF'])
                ? $_SERVER['PHP_SELF']
                : '/index.php';

            $uri = 'http://'
                . $host
                . $script
                . '?module=errors'
                . '&action=dberror'
                . '&usrmsg=' . urlencode($userMessage)
                . '&devmsg=' . urlencode($developerMessage);

            header('Location: ' . $uri);
            return;
        }

        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/plain');
        echo $developerMessage;
        exit(1);
    }

    /**
     * Handle a database connection failure.
     *
     * @param mixed $msg Encoded message
     * @return void
     */
    public static function dbNoConn($msg)
    {
        echo urldecode((string) $msg);
        exit;
    }

    /**
     * Generic cleanup dispatcher.
     *
     * This must remain static because it is called statically throughout
     * the legacy Chisimba codebase.
     *
     * @param mixed $msg Error message
     * @param bool  $db  Whether this is a database error
     * @return void
     */
    public static function cleanUp($msg = null, $db = false)
    {
        if ($db === false) {
            self::diePage($msg);
            return;
        }

        self::dbDeath($msg);
    }
}
