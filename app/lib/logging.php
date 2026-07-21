<?php
/**
 * Chisimba framework logging bootstrap.
 *
 * The public compatibility boundary consists of three global functions:
 *
 * - log_debug() writes general framework diagnostics;
 * - sql_log() writes selected SQL statements;
 * - logger_log() retains the historical logger-file hook.
 *
 * The logger module's database-backed activity counting is separate from this
 * file and remains unchanged.
 *
 * @author Derek Keats
 */

$enable_debug_logging = true;

if (!function_exists('chisimba_log_value_to_string')) {
    /**
     * Convert a legacy logging value to printable text.
     *
     * @param mixed $value Value supplied by historical framework or module code.
     *
     * @return string Printable representation of the value.
     */
    function chisimba_log_value_to_string($value)
    {
        if (is_string($value)) {
            return $value;
        }

        return print_r($value, true);
    }
}

if ($enable_debug_logging === true) {
    require_once __DIR__ . '/chisimba_file_logger.php';

    $GLOBALS['DEBUG_LOG_OBJ'] = new ChisimbaFileLogger(
        'error_log/system_errors.log',
        'framework',
        0644
    );

    $GLOBALS['SQL_LOG_OBJ'] = new ChisimbaFileLogger(
        'error_log/sqllog.log',
        'sql',
        0644,
        '[SQLDATA]',
        '[/SQLDATA]'
    );

    $GLOBALS['LOGGER_LOG'] = new ChisimbaFileLogger(
        'error_log/logger.log',
        'logger',
        0644,
        '[LOGDATA]',
        '[/LOGDATA]'
    );

    if (!function_exists('log_debug')) {
        /**
         * Write a general framework diagnostic value.
         *
         * @param mixed $value Value to record.
         *
         * @return bool TRUE when the entry is written.
         */
        function log_debug($value)
        {
            return $GLOBALS['DEBUG_LOG_OBJ']->log(
                chisimba_log_value_to_string($value)
            );
        }
    }

    if (!function_exists('sql_log')) {
        /**
         * Write a SQL diagnostic value.
         *
         * Historical callers sometimes supply their own SQLDATA markers. Remove
         * one outer marker pair so the native logger emits exactly one pair.
         *
         * @param mixed $value SQL value to record.
         *
         * @return bool TRUE when the entry is written.
         */
        function sql_log($value)
        {
            $message = stripcslashes(stripslashes(
                chisimba_log_value_to_string($value)
            ));

            $message = preg_replace(
                '/^\s*\[SQLDATA\](.*)\[\/SQLDATA\]\s*$/s',
                '$1',
                $message
            );

            return $GLOBALS['SQL_LOG_OBJ']->log($message);
        }
    }

    if (!function_exists('logger_log')) {
        /**
         * Write a value to the historical logger.log destination.
         *
         * @param mixed $value Value to record.
         *
         * @return bool TRUE when the entry is written.
         */
        function logger_log($value)
        {
            return $GLOBALS['LOGGER_LOG']->log(
                chisimba_log_value_to_string($value)
            );
        }
    }
} else {
    if (!function_exists('log_debug')) {
        function log_debug($value)
        {
            return true;
        }
    }

    if (!function_exists('sql_log')) {
        function sql_log($value)
        {
            return true;
        }
    }

    if (!function_exists('logger_log')) {
        function logger_log($value)
        {
            return true;
        }
    }
}
