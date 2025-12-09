<?php
/**
 * Environment Configuration Loader
 * File: config/env.php
 * 
 * Load biến môi trường từ file .env
 */

class Env {
    private static $loaded = false;
    private static $vars = [];
    
    /**
     * Load file .env
     */
    public static function load($filePath = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($filePath === null) {
            $filePath = __DIR__ . '/../.env';
        }
        
        if (!file_exists($filePath)) {
            throw new Exception(".env file not found at: " . $filePath);
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Store in array and set as environment variable
                self::$vars[$key] = $value;
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$vars[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Check if environment is local
     */
    public static function isLocal() {
        return self::get('APP_ENV') === 'local';
    }
    
    /**
     * Check if environment is production
     */
    public static function isProduction() {
        return self::get('APP_ENV') === 'production';
    }
}

// Auto-load .env file
if (!defined('ENV_LOADED')) {
    try {
        Env::load();
        define('ENV_LOADED', true);
    } catch (Exception $e) {
        // Nếu không có .env file, sử dụng giá trị mặc định
        error_log("Warning: " . $e->getMessage());
        define('ENV_LOADED', false);
    }
}
