<?php
/**
 * Database Connection for Admin Tools
 * Simple connection without session/headers
 */

class Database {
    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_NAME = 'godifa1';
    private const DB_CHARSET = 'utf8mb4';
    
    private static $instance = null;
    private $connection;
    
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
            
            mysqli_set_charset($this->connection, self::DB_CHARSET);
            
        } catch (Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function connect() {
        return $this->connection;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    private function __clone() {}
}
