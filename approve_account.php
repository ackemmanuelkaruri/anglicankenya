<?php
require_once 'db.php';

 $username = 'benique01';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div class='alert alert-danger'>User not found: " . htmlspecialchars($username) . "</div>";
    } else {
        echo "<h2>Account Details for: " . htmlspecialchars($username) . "</h2>";
        echo "<table class='table table-striped'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . $user['id'] . "</td></tr>";
        echo "<tr><td>Username</td><td>" . $user['username'] . "</td></tr>";
        echo "<tr><td>Email</td><td>" . $user['email'] . "</td></tr>";
        echo "<tr><td>Account Status</td><td>" . $user['account_status'] . "</td></tr>";
        echo "<tr><td>Status</td><td>" . $user['status'] . "</td></tr>";
        echo "<tr><td>Role</td><td>" . $user['role'] . "</td></tr>";
        echo "<tr><td>Is Admin</td><td>" . $user['is_admin'] . "</td></tr>";
        echo "<tr><td>Is Super Admin</td><td>" . $user['is_super_admin'] . "</td></tr>";
        echo "</table>";
        
        if ($user['account_status'] === 'active') {
            echo "<div class='alert alert-success'>Your account is active. You should be able to log in.</div>";
        } else {
            echo "<div class='alert alert-warning'>Your account is not active. Status: " . $user['account_status'] . "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check benique01 Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <a href="approve_benique.php" class="btn btn-primary">Approve Account</a>
        <a href="login.php" class="btn btn-success">Go to Login</a>
    </div>
</body>
</html>