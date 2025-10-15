<?php
session_start();
require_once '../db.php'; // Ensure database connection is included

// Check if admin is logged in
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'admin') {
    die("Access denied.");
}

// Approve user if approve button is clicked
if (isset($_POST['approve_user'])) {
    $user_id = $_POST['user_id'];
    
    $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
    $stmt->execute([$user_id]);
    
    header("Location: pending_approvals.php"); // Refresh page after approval
    exit();
}

// Fetch all users with pending status
$stmt = $pdo->query("SELECT id, username, email, passport FROM users WHERE status = 'pending'");
$pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Pending Approvals</h2>

        <!-- Back to Dashboard Button -->
        <div class="mb-3">
            <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
        </div>

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Profile Photo</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingUsers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td>
                            <?php if (!empty($user['profile_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="rounded-circle" width="50" height="50">
                            <?php else: ?>
                                <img src="default.png" alt="Default Photo" class="rounded-circle" width="50" height="50">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="approve_user" class="btn btn-success">Approve</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
