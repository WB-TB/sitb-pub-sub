<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Boot {
    private static $initialized = false;
    private static $config;
    private static $db;
    private static $logger;
    private static $cliParams = [];

    public static function init($class) {
        if (self::$initialized) {
            return;
        }
     
        define('APPDIR', __DIR__ . '/..');
        define('LIBDIR', __DIR__);

        require APPDIR . '/vendor/autoload.php';

        
        spl_autoload_register(function ($class) {
            $file = LIBDIR . '/' . str_replace('\\', '/', $class) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
            }
        });

        // load configuration
        self::$config = require APPDIR . '/config.php';

        // initialize logger
        $class = basename(str_replace('\\', '/', $class));
        $lclass = strtolower($class);
        self::$logger = new Logger('PubSub-' . $class);
        self::$logger->pushHandler(new StreamHandler(self::$config['logging'][$lclass], self::$config['logging']['level']));
     
        self::getCliParams();

        self::$initialized = true;
    }

    private static function checkInitialized() {
        if (!self::$initialized) {
            throw new Exception("Boot not initialized. Call Boot::init() first.");
        }
    }

    /**
     * Extract CLI parameters with format --<key>=<value>
     *
     * @return array Associative array of parameters
     */
    public static function getCliParams() {
        if (self::$initialized) {
            return self::$cliParams;
        }
        
        $argv = $GLOBALS['argv'] ?? [];
        
        // Skip the script name (argv[0])
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            // Check if argument matches the format --<key>=<value>
            if (preg_match('/^--([^=]+)=(.+)$/', $arg, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
                self::$cliParams[$key] = $value;
            }
        }
        
        return self::$cliParams;
    }

    /**
     * Get application configuration
     *
     * @return array
     */
    public static function getConfig() {
        self::checkInitialized();
        return self::$config;
    }

    /**
     * Get application logger
     *
     * @return Logger
     */
    public static function getLogger() {
        self::checkInitialized();
        return self::$logger;
    }

    /**
     * Get database connection
     *
     * @return \Database\MySQL
     */
    public static function getDatabase() {
        self::checkInitialized();
        if (self::$db === null) {
            self::$db = new \Database\MySQL(self::$config);
        }
        return self::$db;
    }
}