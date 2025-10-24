<?php
/**
 * ============================================
 * MULTI-TENANT REGISTRATION SYSTEM
 * Endpoint for Real-time Credential Checking (AJAX)
 * ============================================
 */

// Define to include necessary files without full page load context
define('DB_INCLUDED', true);

// Include necessary files
require_once 'db.php';
require_once 'includes/security.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

// Get the action from the request
$action = $_POST['action'] ?? '';

// ============================================
// VALIDATION FUNCTIONS (Copied from register.php to avoid circular dependency)
// ============================================
function validate_username($username) {
    if (strlen($username) < 4 || strlen($username) > 30) {
        return false;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return false;
    }
    
    $reserved = ['admin', 'administrator', 'root', 'system', 'user', 'moderator', 'mod'];
    if (in_array(strtolower($username), $reserved)) {
        return false;
    }
    
    return true;
}

function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "at least 8 characters";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "one special character";
    }
    
    return $errors;
}

// ============================================
// Security: Simple Rate Limiting for this endpoint
// ============================================
function check_ajax_rate_limit($ip_address) {
    global $pdo;
    $limit = 60; // 60 checks per minute per IP
    
    try {
        // First, check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'ajax_attempts'");
        if ($stmt->rowCount() === 0) {
            // Table doesn't exist, create it
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS ajax_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    attempted_at DATETIME NOT NULL,
                    INDEX idx_ip_time (ip_address, attempted_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM ajax_attempts 
            WHERE ip_address = ? 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$ip_address]);
        $result = $stmt->fetch();
        
        if ($result['attempt_count'] >= $limit) {
            return false;
        }
        
        // Log the attempt
        $stmt = $pdo->prepare("
            INSERT INTO ajax_attempts (ip_address, attempted_at) 
            VALUES (?, NOW())
        ");
        $stmt->execute([$ip_address]);
        
        // Clean up old records (older than 5 minutes)
        $pdo->exec("DELETE FROM ajax_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        
        return true;
        
    } catch (PDOException $e) {
        error_log("AJAX Rate limit check failed: " . $e->getMessage());
        return true; // Fail open but log
    }
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!check_ajax_rate_limit($ip_address)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests. Try again shortly.']);
    exit;
}

// ============================================
// Core Checking Logic
// ============================================

$response = ['status' => 'error', 'message' => 'Invalid action.'];

try {
    
    if ($action === 'check_username') {
        $username = sanitize_input($_POST['username'] ?? '');
        
        if (!validate_username($username)) {
            $response = ['status' => 'invalid', 'message' => 'Invalid format. 4-30 characters, letters, numbers, underscore or dash only.'];
        } else {
            // Check in database
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $response = ['status' => 'taken', 'message' => 'Username already in use.'];
            } else {
                $response = ['status' => 'available', 'message' => 'Username available.'];
            }
        }
        
    } elseif ($action === 'check_email') {
        $email = sanitize_input($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $response = ['status' => 'invalid', 'message' => 'Invalid email format.'];
        } else {
            // Check in database
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $response = ['status' => 'taken', 'message' => 'Email already registered.'];
            } else {
                $response = ['status' => 'available', 'message' => 'Email available.'];
            }
        }
        
    } elseif ($action === 'check_password_strength') {
        $password = $_POST['password'] ?? '';
        
        $errors = validate_password_strength($password);
        
        if (empty($errors)) {
            $response = ['status' => 'strong', 'message' => 'Password is strong.'];
        } else {
            $response = ['status' => 'weak', 'errors' => $errors, 'message' => 'Password must contain: ' . implode(', ', $errors)];
        }
    }

} catch (PDOException $e) {
    error_log("Database error in check_credentials.php: " . $e->getMessage());
    $response = ['status' => 'error', 'message' => 'A server error occurred.'];
} catch (Exception $e) {
    error_log("General error in check_credentials.php: " . $e->getMessage());
    $response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];
}

echo json_encode($response);
exit;