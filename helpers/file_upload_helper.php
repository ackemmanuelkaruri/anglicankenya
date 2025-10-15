<?php
/**
 * Enhanced File Upload Helper Functions
 * Secure file upload handling with comprehensive validation
 */

// Fixed: Remove the incorrect absolute path require
// The debug_helper will already be loaded by the main file

class FileUploadHandler {
    
    // Configuration constants
    const DEFAULT_MAX_SIZE = 5242880; // 5MB
    const SECURE_PERMISSIONS = 0755;
    const UPLOAD_BASE_DIR = '../uploads/';
    
    // Allowed file types with MIME types for security
    private static $allowedTypes = [
        'passport' => [
            'extensions' => ['jpg', 'jpeg', 'png'],
            'mime_types' => ['image/jpeg', 'image/png'],
            'max_size' => 5242880, // 5MB
            'description' => 'Passport Image'
        ],
        'certificate' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
            'max_size' => 10485760, // 10MB
            'description' => 'Certificate'
        ],
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx'],
            'mime_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'max_size' => 10485760, // 10MB
            'description' => 'Document'
        ],
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif'],
            'max_size' => 5242880, // 5MB
            'description' => 'Image'
        ]
    ];
    
    /**
     * Main file upload handler with comprehensive security checks
     */
    public static function handleFileUpload($fileData, $fileType, $userId = null, $options = []) {
        try {
            // Validate input parameters
            if (!isset($fileData) || !is_array($fileData)) {
                throw new Exception("Invalid file data provided");
            }
            
            if (!isset(self::$allowedTypes[$fileType])) {
                throw new Exception("Unsupported file type: $fileType");
            }
            
            $config = self::$allowedTypes[$fileType];
            $uploadDir = self::getUploadDirectory($fileType);
            
            // Log upload attempt - check if function exists
            if (function_exists('debugLogFileUpload')) {
                debugLogFileUpload($fileType, $fileData);
            } else {
                error_log("DEBUG: File upload attempt - Type: $fileType, Size: " . ($fileData['size'] ?? 0));
            }
            
            // Check if file was actually uploaded
            $uploadResult = self::validateUpload($fileData, $config);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            
            // Skip processing if no file was uploaded (and it's optional)
            if ($uploadResult['no_file']) {
                return ['success' => true, 'message' => 'No file uploaded', 'filename' => null];
            }
            
            // Create secure upload directory
            if (!self::createSecureDirectory($uploadDir)) {
                throw new Exception("Failed to create upload directory for {$config['description']}");
            }
            
            // Generate secure filename
            $filename = self::generateSecureFilename($fileData['name'], $fileType, $userId);
            $uploadPath = $uploadDir . $filename;
            
            // Additional security checks
            self::performSecurityChecks($fileData, $config);
            
            // Move uploaded file
            if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to save {$config['description']} file");
            }
            
            // Set secure file permissions
            chmod($uploadPath, 0644);
            
            // Log successful upload
            if (function_exists('infoLog')) {
                infoLog("File uploaded successfully", [
                    'file_type' => $fileType,
                    'filename' => $filename,
                    'size' => $fileData['size'],
                    'user_id' => $userId
                ]);
            } else {
                error_log("INFO: File uploaded successfully - Type: $fileType, Filename: $filename");
            }
            
            return [
                'success' => true,
                'message' => "{$config['description']} uploaded successfully",
                'filename' => $filename,
                'filepath' => $uploadPath,
                'relative_path' => str_replace('../', '', $uploadPath),
                'size' => $fileData['size']
            ];
            
        } catch (Exception $e) {
            if (function_exists('errorLog')) {
                errorLog("File upload failed", [
                    'error' => $e->getMessage(),
                    'file_type' => $fileType ?? 'unknown',
                    'user_id' => $userId ?? 'unknown'
                ]);
            } else {
                error_log("ERROR: File upload failed - " . $e->getMessage());
            }
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Validate the uploaded file
     */
    private static function validateUpload($fileData, $config) {
        // Check if file was uploaded
        if (!isset($fileData['error'])) {
            return ['success' => false, 'message' => 'No file data provided'];
        }
        
        // Handle no file uploaded (might be optional)
        if ($fileData['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'no_file' => true];
        }
        
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = self::getUploadErrorMessage($fileData['error']);
            return ['success' => false, 'message' => $errorMessage];
        }
        
        // Validate file size
        if ($fileData['size'] > $config['max_size']) {
            $maxSizeMB = round($config['max_size'] / 1048576, 1);
            return ['success' => false, 'message' => "{$config['description']} file is too large. Maximum size: {$maxSizeMB}MB"];
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $config['extensions'])) {
            $allowedExt = strtoupper(implode(', ', $config['extensions']));
            return ['success' => false, 'message' => "Invalid file type for {$config['description']}. Allowed: $allowedExt"];
        }
        
        return ['success' => true];
    }
    
    /**
     * Perform additional security checks
     */
    private static function performSecurityChecks($fileData, $config) {
        // Validate MIME type
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($fileData['tmp_name']);
            if (!in_array($mimeType, $config['mime_types'])) {
                throw new Exception("File content doesn't match allowed types for {$config['description']}");
            }
        }
        
        // Check if uploaded file is actually uploaded
        if (!is_uploaded_file($fileData['tmp_name'])) {
            throw new Exception("Security violation: File was not uploaded via HTTP POST");
        }
        
        // Additional checks for images
        if (strpos($config['mime_types'][0], 'image/') === 0) {
            $imageInfo = getimagesize($fileData['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception("File is not a valid image");
            }
            
            // Check for reasonable image dimensions (prevent memory exhaustion)
            if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
                throw new Exception("Image dimensions too large. Maximum: 5000x5000 pixels");
            }
        }
        
        // Scan for malicious content (basic check)
        $fileContent = file_get_contents($fileData['tmp_name'], false, null, 0, 1024);
        $maliciousPatterns = ['<?php', '<?=', '<script', 'javascript:', 'eval('];
        
        foreach ($maliciousPatterns as $pattern) {
            if (stripos($fileContent, $pattern) !== false) {
                throw new Exception("File contains potentially malicious content");
            }
        }
    }
    
    /**
     * Generate secure filename
     */
    private static function generateSecureFilename($originalName, $fileType, $userId = null) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize base name
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $baseName = substr($baseName, 0, 30); // Limit length
        
        // Create unique identifier
        $timestamp = time();
        $randomStr = bin2hex(random_bytes(8));
        $userPart = $userId ? "u{$userId}_" : '';
        
        return "{$fileType}_{$userPart}{$baseName}_{$timestamp}_{$randomStr}.{$extension}";
    }
    
    /**
     * Create secure upload directory
     */
    private static function createSecureDirectory($directory) {
        if (!is_dir($directory)) {
            if (!mkdir($directory, self::SECURE_PERMISSIONS, true)) {
                return false;
            }
            
            // Create .htaccess file to prevent direct access
            $htaccessContent = "Options -Indexes\n";
            $htaccessContent .= "Order Deny,Allow\n";
            $htaccessContent .= "Deny from all\n";
            $htaccessContent .= "<Files ~ \"\\.(jpg|jpeg|png|gif|pdf)$\">\n";
            $htaccessContent .= "    Allow from all\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($directory . '.htaccess', $htaccessContent);
            
            // Create index.php to prevent directory listing
            file_put_contents($directory . 'index.php', '<?php header("HTTP/1.0 403 Forbidden"); ?>');
        }
        
        return is_writable($directory);
    }
    
    /**
     * Get upload directory for file type
     */
    private static function getUploadDirectory($fileType) {
        $directories = [
            'passport' => self::UPLOAD_BASE_DIR . 'passports/',
            'certificate' => self::UPLOAD_BASE_DIR . 'certificates/',
            'baptism_certificate' => self::UPLOAD_BASE_DIR . 'certificates/baptism/',
            'confirmation_certificate' => self::UPLOAD_BASE_DIR . 'certificates/confirmation/',
            'document' => self::UPLOAD_BASE_DIR . 'documents/',
            'image' => self::UPLOAD_BASE_DIR . 'images/'
        ];
        
        return $directories[$fileType] ?? self::UPLOAD_BASE_DIR . 'misc/';
    }
    
    /**
     * Get human-readable upload error message
     */
    private static function getUploadErrorMessage($errorCode) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File is too large (exceeds form limit)',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        return $errorMessages[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Delete uploaded file safely
     */
    public static function deleteUploadedFile($filepath) {
        try {
            if (file_exists($filepath) && is_file($filepath)) {
                // Ensure file is within upload directory (security check)
                $realPath = realpath($filepath);
                $uploadBasePath = realpath(self::UPLOAD_BASE_DIR);
                
                if (strpos($realPath, $uploadBasePath) === 0) {
                    unlink($filepath);
                    if (function_exists('infoLog')) {
                        infoLog("File deleted successfully", ['filepath' => $filepath]);
                    }
                    return true;
                } else {
                    if (function_exists('errorLog')) {
                        errorLog("Attempted to delete file outside upload directory", ['filepath' => $filepath]);
                    }
                }
            }
        } catch (Exception $e) {
            if (function_exists('errorLog')) {
                errorLog("Failed to delete file", ['filepath' => $filepath, 'error' => $e->getMessage()]);
            }
        }
        
        return false;
    }
    
    /**
     * Get file info for display
     */
    public static function getFileInfo($filepath) {
        if (!file_exists($filepath)) {
            return null;
        }
        
        return [
            'filename' => basename($filepath),
            'size' => filesize($filepath),
            'size_formatted' => self::formatFileSize(filesize($filepath)),
            'type' => mime_content_type($filepath),
            'modified' => filemtime($filepath)
        ];
    }
    
    /**
     * Format file size in human readable format
     */
    private static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max(0, $bytes);
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }
}

/**
 * Backward compatibility functions
 */

function handleFileUpload($fileData, $uploadDir, $allowedTypes, $fileTypeDescription) {
    // Map old parameters to new system
    $fileType = 'document'; // Default
    
    // Try to determine file type from description or directory
    if (stripos($fileTypeDescription, 'passport') !== false) {
        $fileType = 'passport';
    } elseif (stripos($fileTypeDescription, 'certificate') !== false) {
        $fileType = 'certificate';
    } elseif (stripos($fileTypeDescription, 'image') !== false) {
        $fileType = 'image';
    }
    
    $result = FileUploadHandler::handleFileUpload($fileData, $fileType);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    // Return the relative path instead of just the filename
    // This ensures the full path like "uploads/certificates/filename.ext" is stored
    return $result['relative_path'];
}

function getAllowedFileTypes($category) {
    $allowedTypes = [
        'passport' => ['jpg', 'jpeg', 'png'],
        'certificate' => ['pdf', 'jpg', 'jpeg', 'png'],
        'document' => ['pdf', 'doc', 'docx'],
        'image' => ['jpg', 'jpeg', 'png', 'gif']
    ];
    return $allowedTypes[$category] ?? ['pdf', 'jpg', 'jpeg', 'png'];
}

function getUploadDirectory($fileType) {
    $directories = [
        'passport' => '../uploads/passports/',
        'baptism_certificate' => '../uploads/certificates/baptism/',
        'confirmation_certificate' => '../uploads/certificates/confirmation/',
        'document' => '../uploads/documents/'
    ];
    return $directories[$fileType] ?? '../uploads/misc/';
}
?>