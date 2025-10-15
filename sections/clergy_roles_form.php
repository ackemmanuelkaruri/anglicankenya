<?php
require '../config/db.php'; // adjust path

// Handle new role submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['role_name'])) {
    $roleName = trim($_POST['role_name']);
    $stmt = $pdo->prepare("INSERT INTO clergy_roles_lookup (role_name) VALUES (?)");
    if ($stmt->execute([$roleName])) {
        $message = "Role '$roleName' added successfully!";
    } else {
        $message = "Error adding role.";
    }
}

// Fetch all roles
$roles = $pdo->query("SELECT * FROM clergy_roles_lookup ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clergy Roles Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; }
        .form-box { border:1px solid #ccc; padding:15px; border-radius:6px; width:300px; }
        .message { margin:10px 0; color:green; }
        table { border-collapse: collapse; margin-top:20px; }
        th, td { border:1px solid #ccc; padding:6px 12px; }
    </style>
</head>
<body>
    <h2>Manage Clergy Roles</h2>
    
    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <div class="form-box">
        <form method="POST">
            <label for="role_name">New Role:</label><br>
            <input type="text" name="role_name" id="role_name" required>
            <br><br>
            <button type="submit">Add Role</button>
        </form>
    </div>

    <h3>Existing Roles</h3>
    <table>
        <tr><th>ID</th><th>Role Name</th></tr>
        <?php foreach ($roles as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['role_name']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
