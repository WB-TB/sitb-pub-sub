<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Boot {
    private static $initialized = false;
    private static $config;
    private static $db;
    private static $sqlite;
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

        $class = basename(str_replace('\\', '/', $class));
        $lclass = strtolower($class);
        $cliParams = self::getCliParams();
        if (isset($cliParams['mode']) && $cliParams['mode'] === 'api') {
            $lclass = "$lclass-api";
            $logname = 'Api-' . $class;
        } else {
            if ($lclass == 'producer')
                $lclass = "$lclass-pubsub";
            $logname = 'PubSub-' . $class;
        }

        // initialize logger
        self::$logger = new Logger($logname);
        self::$logger->pushHandler(new StreamHandler(self::$config['logging'][$lclass], self::$config['logging']['level']));    
        
        self::checkVersion();

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
        
        $argv = $GLOBALS['argv'] ? $GLOBALS['argv'] : [];
        
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
            // Check database requirements after connection is established
            self::checkRequirements();
        }
        return self::$db;
    }

    /**
     * Get SQLite database connection for Pub/Sub
     *
     * @return \Database\SQLite
     */
    public static function getSQLite() {
        self::checkInitialized();
        if (self::$sqlite === null) {
            self::$sqlite = new \Database\SQLite(self::$config);
        }
        return self::$sqlite;
    }

    private static function checkVersion() {
        $requiredPhpVersion = '7.4.0';
        if (version_compare(PHP_VERSION, $requiredPhpVersion, '<')) {
            throw new Exception("PHP version $requiredPhpVersion or higher is required. Current version: " . PHP_VERSION);
        }

        // Read local version
        $localVersionFile = APPDIR . '/version';
        if (!file_exists($localVersionFile)) {
            return; // Skip version check if local version file doesn't exist
        }
        
        $localVersion = trim(file_get_contents($localVersionFile));
        
        // Fetch remote version from GitHub
        $remoteVersionUrl = 'https://raw.githubusercontent.com/WB-TB/sitb-pub-sub/main/version';
        $remoteVersion = null;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $remoteVersionContent = @file_get_contents($remoteVersionUrl, false, $context);
        
        if ($remoteVersionContent !== false) {
            $remoteVersion = trim($remoteVersionContent);
        }
        
        // Compare versions
        if ($remoteVersion !== null && version_compare($localVersion, $remoteVersion, '<')) {
            // Local version is lower, run update script
            $updateScript = __DIR__ . '/scripts/install.sh';
            
            if (file_exists($updateScript)) {
                // Execute the update script
                $output = [];
                $returnCode = 0;
                exec("sh " . escapeshellarg($updateScript) . " update 2>&1", $output, $returnCode);
                
                // Log the update process
                if (isset(self::$logger)) {
                    self::$logger->info("Updating from version $localVersion to $remoteVersion");
                    self::$logger->info("Update output: " . implode("\n", $output));
                    
                    if ($returnCode === 0) {
                        self::$logger->info("Update completed successfully");
                    } else {
                        self::$logger->error("Update failed with return code: $returnCode");
                    }
                }
            }
        }
    }

    public static function checkRequirements() {
        try {
            $db = self::getDatabase();
            $config = self::getConfig();
            $tableName = $config['ckg']['table_skrining'];
            
            // Check if the 'ckg_id' column exists in the 'ta_skrining' table
            $query = "SELECT COUNT(*) as column_exists 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = ? 
                     AND COLUMN_NAME = 'ckg_id'";
            
            $result = $db->fetchOne($query, [$tableName]);
            
            if ($result && isset($result['column_exists']) && $result['column_exists'] == 0) {
                // coba tambahkan sendiri kolom ckg_id jika belum ada
                try {
                    $alterQuery = "ALTER TABLE $tableName ADD COLUMN ckg_id varchar(16) DEFAULT NULL";
                    $db->query($alterQuery, []);
                    
                    if (isset(self::$logger)) {
                        self::$logger->info("Successfully added 'ckg_id' column to table '$tableName' using:\tALTER TABLE $tableName ADD COLUMN ckg_id varchar(16) DEFAULT NULL;");
                    }
                } catch (Exception $e) {
                    // Silent error - log but don't fail initialization
                    if (isset(self::$logger)) {
                        self::$logger->error("Column 'ckg_id' does not exist in table '$tableName'. Please add it manually using:\tALTER TABLE $tableName ADD COLUMN ckg_id varchar(16) DEFAULT NULL;");
                    }
                    echo "Error: Column 'ckg_id' does not exist in table '$tableName'.\n\n=======================================\nPlease add it using:\n\tALTER TABLE $tableName ADD COLUMN ckg_id varchar(16) DEFAULT NULL;\n=======================================\n\n";
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the initialization
            if (isset(self::$logger)) {
                self::$logger->error("Failed to check for 'ckg_id' column: " . $e->getMessage());
            }
            echo "Failed to check for 'ckg_id' column: " . $e->getMessage();
        }
    }

    // Helper function to validate date format (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
    public static function validateDateFormat($date) {
        if ($date === 'now') {
            return true;
        }
        $pattern = '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/';
        if (!preg_match($pattern, $date)) {
            return false;
        }
        $parsed = date_parse($date);
        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }
}