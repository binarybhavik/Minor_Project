<?php
class Database {
    private static $instance = null;
    private $connection = null;
    private $error = null;
    private static $errorShown = false;
    
    private function __construct() {
        // Check if mysqli is loaded
        if (!extension_loaded('mysqli')) {
            $this->error = "The mysqli extension is not loaded. Please enable it in your php.ini file.";
            if (!self::$errorShown) {
                // Check if this is an AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    // AJAX request, return JSON error
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Database error: mysqli extension not loaded. Please visit enable-mysqli.php for instructions.']);
                    exit;
                } else {
                    // Regular request, redirect to help page
                    header('Location: ../enable-mysqli.php');
                    exit;
                }
                self::$errorShown = true;
            }
            return;
        }
        
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'FTT_iips';
        
        try {
            $this->connection = new mysqli($host, $username, $password);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Check if database exists, create it if not
            $result = $this->connection->query("SHOW DATABASES LIKE '$database'");
            if ($result->num_rows == 0) {
                // Database doesn't exist, create it
                if (!$this->connection->query("CREATE DATABASE IF NOT EXISTS `$database`")) {
                    throw new Exception("Failed to create database: " . $this->connection->error);
                }
            }
            
            // Select the database
            if (!$this->connection->select_db($database)) {
                throw new Exception("Failed to select database: " . $this->connection->error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            $this->error = "Database connection error: " . $e->getMessage();
            error_log($this->error);
            
            if (!self::$errorShown) {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
                echo "<h2>Database Connection Error</h2>";
                echo "<p>{$this->error}</p>";
                echo "</div>";
                self::$errorShown = true;
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function hasError() {
        return $this->error !== null;
    }
    
    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?> 