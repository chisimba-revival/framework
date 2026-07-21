<?php
/**
 * Native file logger for the Chisimba framework.
 *
 * This class replaces the narrow PEAR Log usage that historically sat behind
 * lib/logging.php. It intentionally provides only the behaviour required by
 * the Chisimba framework's three global logging functions.
 *
 * It is not the logger module's activity-counting system and does not replace
 * logging facilities bundled inside third-party libraries.
 *
 * @author Derek Keats
 */
class ChisimbaFileLogger
{
    /**
     * Path to the destination log file.
     *
     * @var string
     */
    private $filePath;

    /**
     * Text identifier written with each message.
     *
     * @var string
     */
    private $identifier;

    /**
     * File mode applied after a log file is first created.
     *
     * @var int
     */
    private $fileMode;

    /**
     * Optional text placed immediately before the message.
     *
     * @var string
     */
    private $messagePrefix;

    /**
     * Optional text placed immediately after the message.
     *
     * @var string
     */
    private $messageSuffix;

    /**
     * Construct a native Chisimba file logger.
     *
     * @param string $filePath      Destination file path.
     * @param string $identifier    Logger identifier.
     * @param int    $fileMode      File mode used for newly created files.
     * @param string $messagePrefix Optional message prefix.
     * @param string $messageSuffix Optional message suffix.
     */
    public function __construct(
        $filePath,
        $identifier,
        $fileMode = 0644,
        $messagePrefix = '',
        $messageSuffix = ''
    ) {
        $this->filePath = $filePath;
        $this->identifier = $identifier;
        $this->fileMode = $fileMode;
        $this->messagePrefix = $messagePrefix;
        $this->messageSuffix = $messageSuffix;
    }

    /**
     * Append a message to the logger's destination.
     *
     * The optional priority argument is accepted for compatibility with the
     * former PEAR Log object API. The framework currently writes only debug
     * messages, so no priority filtering is required at this boundary.
     *
     * @param mixed $message  Scalar, array, object, or other printable value.
     * @param mixed $priority Legacy priority value; retained for compatibility.
     *
     * @return bool TRUE when the complete entry was written.
     */
    public function log($message, $priority = null)
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true)) {
            return false;
        }

        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $entry = sprintf(
            "%s %s %s%s%s\n",
            date('Y-m-d H:i:s'),
            $this->identifier,
            $this->messagePrefix,
            rtrim($message),
            $this->messageSuffix
        );

        $fileExisted = file_exists($this->filePath);
        $bytesWritten = @file_put_contents(
            $this->filePath,
            $entry,
            FILE_APPEND | LOCK_EX
        );

        if ($bytesWritten === false) {
            return false;
        }

        if (!$fileExisted) {
            @chmod($this->filePath, $this->fileMode);
        }

        return $bytesWritten === strlen($entry);
    }
}
