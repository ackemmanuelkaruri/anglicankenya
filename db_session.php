<?php
/**
 * Database Session Handler for Render + Supabase
 * Fixes session persistence issues with ephemeral storage
 */

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private $pdo;
    private $table = 'sessions';
    private $lifetime;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->lifetime = ini_get('session.gc_maxlifetime') ?: 1440;
    }

    public function open($save_path, $session_name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($session_id): string
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT session_data 
                FROM {$this->table} 
                WHERE session_id = ? 
                AND last_activity > NOW() - INTERVAL '{$this->lifetime} seconds'
            ");
            $stmt->execute([$session_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update last activity
                $updateStmt = $this->pdo->prepare("
                    UPDATE {$this->table} 
                    SET last_activity = NOW() 
                    WHERE session_id = ?
                ");
                $updateStmt->execute([$session_id]);
                
                return $result['session_data'];
            }
            
            return '';
        } catch (PDOException $e) {
            error_log("Session read error: " . $e->getMessage());
            return '';
        }
    }

    public function write($session_id, $session_data): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (session_id, session_data, last_activity)
                VALUES (?, ?, NOW())
                ON CONFLICT (session_id) 
                DO UPDATE SET 
                    session_data = EXCLUDED.session_data,
                    last_activity = NOW()
            ");
            $stmt->execute([$session_id, $session_data]);
            return true;
        } catch (PDOException $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }

    public function destroy($session_id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE session_id = ?");
            $stmt->execute([$session_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }

    public function gc($maxlifetime): int
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->table} 
                WHERE last_activity < NOW() - INTERVAL '{$maxlifetime} seconds'
            ");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Session GC error: " . $e->getMessage());
            return 0;
        }
    }
}

/**
 * Initialize database sessions
 * Call this BEFORE session_start()
 */
function init_database_sessions(PDO $pdo)
{
    $handler = new DatabaseSessionHandler($pdo);
    session_set_save_handler($handler, true);
    
    // Configure session settings for Render + Supabase
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', $is_https ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 86400); // 24 hours
    ini_set('session.cookie_lifetime', 0); // Until browser closes
    
    // Set session name to avoid conflicts
    session_name('CHURCH_SESS');
    
    // Important for Render: Use custom session path
    $session_path = sys_get_temp_dir() . '/php_sessions';
    if (!is_dir($session_path)) {
        @mkdir($session_path, 0700, true);
    }
    ini_set('session.save_path', $session_path);
}
