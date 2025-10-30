<?php
/**
 * Database Configuration & Connection
 * File: model/mketnoi.php
 * SINGLE SOURCE - File duy nhất quản lý database
 */

class Database {
    // Database credentials
    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_NAME = 'godifa';
    private const DB_CHARSET = 'utf8mb4';
    
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor - Singleton pattern
     */
    private function __construct() {
        try {
            $this->connection = mysqli_connect(
                self::DB_HOST,
                self::DB_USER,
                self::DB_PASS,
                self::DB_NAME
            );
            
            if (!$this->connection) {
                throw new Exception("Connection failed: " . mysqli_connect_error());
            }
            
            // Set charset
            mysqli_set_charset($this->connection, self::DB_CHARSET);
            
        } catch (Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Close connection
     */
    public function closeConnection() {
        if ($this->connection) {
            mysqli_close($this->connection);
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// ============================================
// BACKWARD COMPATIBILITY
// Wrapper cho code cũ
// ============================================

class clsKetNoi {
    public function moKetNoi() {
        return Database::getInstance()->getConnection();
    }
    
    public function dongKetNoi($conn) {
        // Không làm gì vì sử dụng singleton
        // Connection sẽ tự đóng khi script kết thúc
    }
}

// ============================================
// APP CONFIGURATION
// Cấu hình ứng dụng
// ============================================

// URL Configuration
define('BASE_URL', 'http://localhost/GODIFA/');
define('ADMIN_URL', BASE_URL . 'admin/');

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../images/');
define('UPLOAD_URL', BASE_URL . 'images/');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 86400); // 24 hours
    session_start();
}

// ============================================
// HELPER FUNCTIONS
// Các hàm tiện ích
// ============================================

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Check if customer is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['customer_id']);
}

/**
 * Check if admin/staff is logged in
 */
function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role_id']);
}

/**
 * Format price in Vietnamese style
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

/**
 * Format date in Vietnamese style
 */
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

/**
 * Get database connection (shorthand)
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
