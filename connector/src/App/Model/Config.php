<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model;

/**
 * Class Config
 * Loading configuration files
 * @package App\Model
 */
class Config
{
    /**
     * Config files extension
     */
    const EXT = '.php';

    /**
     * Root folder path
     */
    const CONFIG_PATH = '/Configuration';

    /**
     * @var array loaded configuration
     */
    private static $instance;

    /**
     * @param $path
     * @return null|array|mixed
     * @throws \Exception
     */
    public static function get($path)
    {
        $path = explode('.', $path);

        if (empty($path[0]) || !self::loadFileIfExists($path[0]))
            return null;

        $config = self::$instance;
        foreach ($path as $segment) {
            if (!empty($config[$segment])) {
                $config = $config[$segment];
            } else {
                $config = null;
                break;
            }
        }

        return $config;
    }

    /**
     * Load configuration file
     * @param string $fileName
     * @return bool
     * @throws \Exception
     */
    private static function loadFileIfExists(string $fileName)
    {
        if (!empty(self::$instance[$fileName]))
            return true;
        try {
            if (!file_exists($file = APP_ROOT . self::CONFIG_PATH . DIRECTORY_SEPARATOR . $fileName . self::EXT))
                throw new \Exception('There is no config file: ' . $file);

            self::$instance[$fileName] = include $file;
            return true;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Private constructor, to prevent creating new object
     */
    private function __construct()
    {
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * Prevent from being unserialized (which would create a second instance of it)
     */
    private function __wakeup()
    {
    }
}