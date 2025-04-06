<?php
/**
 * Logger utility class for consistent logging across the application
 */
class Logger {
    private $className;
    private $logFile;
    private $context;
    private $logDir;
    private $canWrite = true;
    
    /**
     * Constructor
     * 
     * @param string $className The class name of the logger (optional)
     */
    public function __construct($className = 'Application') {
        $this->className = $className;
        $this->logFile = __DIR__ . '/../logs/app.log';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            try {
                if (!@mkdir($logDir, 0777, true)) {
                    $this->canWrite = false;
                }
            } catch (Exception $e) {
                $this->canWrite = false;
            }
        }
        
        // Check if the log file is writable
        if ($this->canWrite && file_exists($this->logFile) && !is_writable($this->logFile)) {
            $this->canWrite = false;
        }
        
        // Write initial entry
        if ($this->canWrite) {
            $this->log("Logger initialized");
        }
    }
    
    /**
     * Log a message
     * 
     * @param string $message The message to log
     */
    public function log($message) {
        if (!$this->canWrite) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [{$this->className}] INFO: $message\n";
        
        try {
            @error_log($logMessage, 3, $this->logFile);
        } catch (Exception $e) {
            // Silent fail
            $this->canWrite = false;
        }
    }
    
    /**
     * Log an error message
     * 
     * @param string $message The error message
     * @param Exception|null $exception Optional exception to log
     */
    public function error($message, $exception = null) {
        if (!$this->canWrite) {
            // At least write to PHP error log
            error_log("ERROR: [{$this->className}] $message");
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [{$this->className}] ERROR: $message";
        if ($exception) {
            $logMessage .= "\nException: " . $exception->getMessage();
            $logMessage .= "\nStack trace: " . $exception->getTraceAsString();
        }
        $logMessage .= "\n";
        
        try {
            @error_log($logMessage, 3, $this->logFile);
        } catch (Exception $e) {
            // Silent fail
            $this->canWrite = false;
            // At least try to use PHP's error_log
            error_log("ERROR: [{$this->className}] $message");
        }
    }
    
    /**
     * Log a warning message
     * 
     * @param string $message The warning message
     */
    public function warning($message) {
        if (!$this->canWrite) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [{$this->className}] WARNING: $message\n";
        
        try {
            @error_log($logMessage, 3, $this->logFile);
        } catch (Exception $e) {
            // Silent fail
            $this->canWrite = false;
        }
    }
    
    /**
     * Destructor to close the log file
     */
    public function __destruct() {
        if ($this->canWrite) {
            $this->log("Logger shutdown");
        }
    }
    
    /**
     * Get the path to the current log file
     * 
     * @return string The path to the log file
     */
    public function getLogFilePath() {
        return $this->logFile;
    }
    
    /**
     * Static method to read the contents of a log file
     * 
     * @param string $logFile The log file to read
     * @param int $lines The number of lines to read from the end
     * @return string The log file contents
     */
    public static function readLogFile($logFile, $lines = 50) {
        if (!file_exists($logFile)) {
            return "Log file does not exist.";
        }
        
        try {
            $file = new SplFileObject($logFile, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            
            $logContent = "";
            $startLine = max(0, $totalLines - $lines);
            
            $file->rewind();
            $lineCount = 0;
            
            while (!$file->eof()) {
                $line = $file->fgets();
                if ($lineCount >= $startLine) {
                    $logContent .= $line;
                }
                $lineCount++;
            }
            
            return $logContent;
        } catch (Exception $e) {
            return "Error reading log file: " . $e->getMessage();
        }
    }
}
?> 