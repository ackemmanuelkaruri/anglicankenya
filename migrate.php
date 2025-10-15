<?php
/**
 * ============================================
 * DATABASE MIGRATION MANAGER
 * Run from command line: php migrate.php
 * ============================================
 */

require_once 'config.php';
require_once 'db.php';

class MigrationManager {
    private $pdo;
    private $migrations_path = __DIR__ . '/migrations';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureMigrationsTable();
    }
    
    // Create migrations tracking table
    private function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->pdo->exec($sql);
        echo "✓ Migrations table ready\n";
    }
    
    // Get all migration files
    private function getMigrationFiles() {
        if (!is_dir($this->migrations_path)) {
            mkdir($this->migrations_path, 0755, true);
            echo "✓ Created migrations directory\n";
        }
        
        $files = glob($this->migrations_path . '/*.sql');
        sort($files);
        return $files;
    }
    
    // Get executed migrations
    private function getExecutedMigrations() {
        $stmt = $this->pdo->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Run pending migrations
    public function migrate() {
        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        
        // Get current batch number
        $batch = $this->getCurrentBatch() + 1;
        
        $pending = array_filter($files, function($file) use ($executed) {
            return !in_array(basename($file), $executed);
        });
        
        if (empty($pending)) {
            echo "✓ No pending migrations\n";
            return;
        }
        
        echo "\nRunning " . count($pending) . " migration(s)...\n\n";
        
        foreach ($pending as $file) {
            $migration_name = basename($file);
            echo "→ Running: {$migration_name}... ";
            
            try {
                $sql = file_get_contents($file);
                $this->pdo->exec($sql);
                
                // Record migration
                $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                $stmt->execute([$migration_name, $batch]);
                
                echo "✓ Done\n";
            } catch (PDOException $e) {
                echo "✗ Failed\n";
                echo "Error: " . $e->getMessage() . "\n";
                die("Migration stopped at: {$migration_name}\n");
            }
        }
        
        echo "\n✓ All migrations completed successfully!\n";
    }
    
    // Get current batch number
    private function getCurrentBatch() {
        $stmt = $this->pdo->query("SELECT COALESCE(MAX(batch), 0) as max_batch FROM migrations");
        return $stmt->fetch()['max_batch'];
    }
    
    // Show migration status
    public function status() {
        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        
        echo "\n=== Migration Status ===\n\n";
        echo "Environment: " . current_environment() . "\n";
        echo "Database: " . DB_NAME . "\n\n";
        
        foreach ($files as $file) {
            $name = basename($file);
            $status = in_array($name, $executed) ? '✓ Executed' : '○ Pending';
            echo "{$status}  {$name}\n";
        }
        
        echo "\nTotal: " . count($files) . " | Executed: " . count($executed) . " | Pending: " . (count($files) - count($executed)) . "\n";
    }
    
    // Rollback last batch
    public function rollback() {
        $batch = $this->getCurrentBatch();
        
        if ($batch == 0) {
            echo "✓ No migrations to rollback\n";
            return;
        }
        
        $stmt = $this->pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$batch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "\nRolling back " . count($migrations) . " migration(s)...\n\n";
        
        foreach ($migrations as $migration_name) {
            echo "→ Rolling back: {$migration_name}... ";
            
            // Look for rollback file
            $rollback_file = str_replace('.sql', '.rollback.sql', $this->migrations_path . '/' . $migration_name);
            
            if (file_exists($rollback_file)) {
                try {
                    $sql = file_get_contents($rollback_file);
                    $this->pdo->exec($sql);
                    
                    // Remove from migrations table
                    $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
                    $stmt->execute([$migration_name]);
                    
                    echo "✓ Done\n";
                } catch (PDOException $e) {
                    echo "✗ Failed\n";
                    echo "Error: " . $e->getMessage() . "\n";
                }
            } else {
                echo "⚠ No rollback file found\n";
            }
        }
        
        echo "\n✓ Rollback completed!\n";
    }
}

// CLI Handler
if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'status';
    
    echo "\n╔════════════════════════════════════╗\n";
    echo "║   DATABASE MIGRATION MANAGER       ║\n";
    echo "╚════════════════════════════════════╝\n";
    
    $manager = new MigrationManager($pdo);
    
    switch ($command) {
        case 'migrate':
        case 'up':
            $manager->migrate();
            break;
            
        case 'rollback':
        case 'down':
            $manager->rollback();
            break;
            
        case 'status':
            $manager->status();
            break;
            
        default:
            echo "\nUsage:\n";
            echo "  php migrate.php migrate   - Run pending migrations\n";
            echo "  php migrate.php rollback  - Rollback last batch\n";
            echo "  php migrate.php status    - Show migration status\n\n";
    }
} else {
    die("This script must be run from command line.\n");
}