<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace Utils;

class Logger
{

    static $id;
    static $conf;

    /**
     * Init logger and set specific Log Id
     */
    public static function init(array $conf) {
        self::$id = round(microtime(true) * 1000) . '_' . rand(1000, 9999);
        self::$conf = $conf;
    }

    /**
     * Log the given text with the configured text log template
     * @param string $text Text to log
     */
    public static function logText(string $text)
    {
        $log_line = self::$conf['text_log_template'];
        // Build the placeholders array with the text and date
        $replacements = [
            "data" => $text,
            "date" => date('d/m/Y H:i:s'),
            "req_id" => self::$id
        ];
        // Check log_level before logging
        if (self::$conf['log_level'] >= 2) {
            self::log($log_line, $replacements);
        }
    }

    /**
     * Log the given exception with the configured exception log template
     * @param Exception $error PHP exception to log
     */
    public static function logException(\Exception $error)
    {
        $log_line = self::$conf['exception_log_template'];
        // Build the placeholders array with the date and the exception data
        $replacements = [
            "code" => $error->getCode(),
            "message" => $error->getMessage(),
            "file" => $error->getFile(),
            "line" => $error->getLine(),
            "trace" => $error->getTraceAsString(),
            "date" => date('d/m/Y H:i:s'),
            "req_id" => self::$id
        ];
        // Check log_level before logging
        if (self::$conf['log_level'] >= 1) {
            self::log($log_line, $replacements);
        }
    }

    /**
     * Build and log the given log template with the provided replacements
     * @param string $template     Log template
     * @param array  $replacements Array with the replacements for the template
     */
    public static function log(string $template, array $replacements = [])
    {
        // Replace the log template with the replacements
        foreach ($replacements as $name => $value) {
            $template = str_replace("{{".$name."}}", $value, $template);
        }

        // Print the log line
        if (self::$conf['echo_logs'] === true) {
            echo $template;
        }
        // Send the log line to the error_log function
        if (self::$conf['error_log'] === true) {
            error_log($template);
        }
    }
}
