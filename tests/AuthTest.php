<?php

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $db;

    // Set up before each test
    protected function setUp(): void
    {
        // Initialize your database connection here
        // Assuming you're using PDO, update the DB connection as needed
        $this->db = new PDO('mysql:host=localhost;dbname=anglicankenya', 'root', ''); // Use your correct DB settings

        // Insert test user into the database if it doesn't exist
        $this->insertTestUser('testuser', 'test@example.com', 'password');
    }

    // Clean up after each test
    protected function tearDown(): void
    {
        // Optionally, clean up the test user from the DB after the test
        $this->db->query("DELETE FROM users WHERE username = 'testuser'");  // Delete the test user
    }

    // Insert a test user into the database with a hashed password
    private function insertTestUser($username, $email, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hash password for security
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
        $password = 'password'; // Plain text password for testing
        
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
        $password = 'wrongpassword'; // Invalid password
        
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
