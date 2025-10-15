<?php
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        global $pdo;
        $this->pdo = $pdo;
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
    }
}
