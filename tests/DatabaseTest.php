<?php
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        try {
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbname = getenv('DB_DATABASE') ?: 'anglicankenya';
            $username = getenv('DB_USERNAME') ?: 'root';
            $password = getenv('DB_PASSWORD') ?: '';
            
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname", 
                $username, 
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // Clean up test data
        if ($this->pdo) {
            $this->pdo->exec("DELETE FROM users WHERE username = 'testuser'");
        }
    }

    public function testUsersTableExists()
    {
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'users'");
        $result = $stmt->fetch();
        $this->assertNotFalse($result, "Users table does not exist in the database.");
    }

    public function testInsertUser()
    {
        // Insert a test user
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, role_level) VALUES (?, ?, ?)");
        $stmt->execute(['testuser', 'test@example.com', 4]);
        
        // Verify it exists
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['testuser']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($user, "Test user was not inserted.");
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('test@example.com', $user['email']);
    }
}