<?php
/**
 * Enhanced Debug Helper Functions
 * Centralized logging and debugging utilities with improved functionality
 */

class DebugLogger {
    private static $logDirectory = null;
    private static $maxLogSize = 10485760; // 10MB
    private static $enabledLevels = ['ERROR', 'WARNING', 'INFO', 'DEBUG'];
    
    /**
     * Initialize the logger with configuration
     */
    public static function init($config = []) {
        self::$logDirectory = $config['log_directory'] ?? dirname(__FILE__) . '/../logs/';
        self::$maxLogSize = $config['max_log_size'] ?? 10485760;
        self::$enabledLevels = $config['enabled_levels'] ?? ['ERROR', 'WARNING', 'INFO', 'DEBUG'];
        
        // Create log directory if it doesn't exist
        self::createLogDirectory();
    }
    
    /**
     * Create log directory if it doesn't exist
     */
    private static function createLogDirectory() {
        if (self::$logDirectory === null) {
            self::init(); // Initialize with defaults
        }
        
        if (!is_dir(self::$logDirectory)) {
            if (!mkdir(self::$logDirectory, 0755, true)) {
                error_log("Failed to create log directory: " . self::$logDirectory);
                return false;
            }
        }
        
        // Check if directory is writable
        if (!is_writable(self::$logDirectory)) {
            error_log("Log directory is not writable: " . self::$logDirectory);
            return false;
        }
        
        return true;
    }
    
    /**
     * Main logging function
     */
    public static function log($message, $level = 'INFO', $context = []) {
        // Check if this log level is enabled
        if (!in_array($level, self::$enabledLevels)) {
            return false;
        }
        
        $logFile = self::getLogFilePath($level);
        
        // Check and rotate log if necessary
        self::rotateLogIfNeeded($logFile);
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $formattedMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        return error_log($formattedMessage, 3, $logFile);
    }
    
    /**
     * Get appropriate log file path based on level
     */
    private static function getLogFilePath($level) {
        if (self::$logDirectory === null) {
            self::init();
        }
        
        $filename = match($level) {
            'ERROR' => 'error.log',
            'WARNING' => 'warning.log',
            'DEBUG' => 'debug.log',
            default => 'app.log'
        };
        
        return self::$logDirectory . $filename;
    }
    
    /**
     * Rotate log file if it exceeds maximum size
     */
    private static function rotateLogIfNeeded($logFile) {
        if (!file_exists($logFile)) {
            return;
        }
        
        if (filesize($logFile) > self::$maxLogSize) {
            $rotatedFile = $logFile . '.' . date('Y-m-d-H-i-s');
            rename($logFile, $rotatedFile);
            
            // Keep only last 5 rotated files
            self::cleanupOldLogs(dirname($logFile), basename($logFile));
        }
    }
    
    /**
     * Clean up old log files
     */
    private static function cleanupOldLogs($directory, $baseFilename) {
        $pattern = $directory . '/' . $baseFilename . '.*';
        $files = glob($pattern);
        
        if (count($files) > 5) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files, keep only 5 most recent
            $filesToDelete = array_slice($files, 0, count($files) - 5);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Filter sensitive data from arrays/objects before logging
     * FIXED: Made this method PUBLIC so it can be called from global functions
     */
    public static function filterSensitiveData($data) {
        $sensitiveKeys = ['password', 'passwd', 'secret', 'token', 'key', 'auth', 'credential'];
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($key, $sensitiveKey) !== false) {
                        $data[$key] = '[FILTERED]';
                        break;
                    }
                }
                
                if (is_array($value) || is_object($value)) {
                    $data[$key] = self::filterSensitiveData($value);
                }
            }
        } elseif (is_object($data)) {
            $data = (array) $data;
            return (object) self::filterSensitiveData($data);
        }
        
        return $data;
    }
}

/**
 * Convenience functions for backward compatibility and ease of use
 */

/**
 * Log debugging information
 */
function debugLog($message, $context = []) {
    DebugLogger::log($message, 'DEBUG', $context);
}

/**
 * Log error information
 */
function errorLog($message, $context = []) {
    DebugLogger::log($message, 'ERROR', $context);
}

/**
 * Log warning information
 */
function warningLog($message, $context = []) {
    DebugLogger::log($message, 'WARNING', $context);
}

/**
 * Log info information
 */
function infoLog($message, $context = []) {
    DebugLogger::log($message, 'INFO', $context);
}

/**
 * Log array data in a readable format with sensitive data filtering
 */
function debugLogArray($label, $data, $level = 'DEBUG') {
    $filteredData = DebugLogger::filterSensitiveData($data);
    $message = "$label: " . print_r($filteredData, true);
    DebugLogger::log($message, $level);
}

/**
 * Log SQL query execution details
 */
function debugLogQuery($query, $params = [], $level = 'DEBUG') {
    $filteredParams = DebugLogger::filterSensitiveData($params);
    $context = ['query' => $query, 'parameters' => $filteredParams];
    DebugLogger::log("SQL Query executed", $level, $context);
}

/**
 * Log database operation results
 */
function debugLogDBOperation($operation, $affectedRows, $table, $level = 'INFO') {
    $message = "$operation operation on table '$table': $affectedRows rows affected";
    $context = [
        'operation' => $operation,
        'table' => $table,
        'affected_rows' => $affectedRows
    ];
    DebugLogger::log($message, $level, $context);
}

/**
 * Log file upload information
 */
function debugLogFileUpload($fieldName, $fileData, $level = 'DEBUG') {
    $filteredFileData = [
        'name' => $fileData['name'] ?? 'unknown',
        'size' => $fileData['size'] ?? 0,
        'type' => $fileData['type'] ?? 'unknown',
        'error' => $fileData['error'] ?? 0
    ];
    
    $message = "File upload attempt for field: $fieldName";
    DebugLogger::log($message, $level, ['file_data' => $filteredFileData]);
}

/**
 * Log exception details
 */
function debugLogException($exception, $level = 'ERROR') {
    $context = [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ];
    
    DebugLogger::log("Exception occurred: " . $exception->getMessage(), $level, $context);
}

/**
 * Log request details (useful for debugging form submissions)
 */
function debugLogRequest($level = 'DEBUG') {
    $requestData = [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'post_data' => DebugLogger::filterSensitiveData($_POST),
        'get_data' => $_GET,
        'files' => array_keys($_FILES)
    ];
    
    DebugLogger::log("HTTP Request received", $level, $requestData);
}

/**
 * Initialize the logger (call this in your bootstrap/config file)
 */
function initDebugLogger($config = []) {
    DebugLogger::init($config);
}

// Auto-initialize with default settings
DebugLogger::init();
?>