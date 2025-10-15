<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $db;

    // Set up before each test
    protected function setUp(): void
    {
        try {
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
            $dbname = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'anglicankenya_test';
            $username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
            $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
            
            $this->db = new PDO(
                "mysql:host=$host;dbname=$dbname", 
                $username, 
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Insert test user into the database if it doesn't exist
            $this->insertTestUser('testuser', 'test@example.com', 'password');
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }

    // Clean up after each test
    protected function tearDown(): void
    {
        // Optionally, clean up the test user from the DB after the test
        if ($this->db) {
            $this->db->query("DELETE FROM users WHERE username = 'testuser'");
        }
    }

    // Insert a test user into the database with a hashed password
    private function insertTestUser($username, $email, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
        ]);
    }

    // Test login with a valid user
    public function testLoginWithValidUser()
    {
        $username = 'testuser';
        $password = 'password';
        
        // Fetch the hashed password from the DB
        $stmt = $this->db->prepare("SELECT password FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $hashedPasswordFromDb = $stmt->fetchColumn();
        
        // Verify the password
        $result = password_verify($password, $hashedPasswordFromDb);
        
        // Assert that the password verification is successful
        $this->assertTrue($result, 'Login failed for valid user.');
    }

    // Test for failed login (e.g., invalid password)
    public function testLoginWithInvalidUser()
    {
        $username = 'invaliduser';
        $password = 'wrongpassword';
        
        // Fetch the hashed password from the DB
        $stmt = $this->db->prepare("SELECT password FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $hashedPasswordFromDb = $stmt->fetchColumn();
        
        // Verify the password (should fail)
        $result = password_verify($password, $hashedPasswordFromDb);
        
        // Assert that the login should fail
        $this->assertFalse($result, 'Login should fail for invalid user.');
    }
}