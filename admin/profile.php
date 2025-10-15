<?php
// Database connection
include '../db.php';
session_start();


// Check if user is logged in
if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];
} else {
    die("Invalid User ID. Please log in.");
}




// Fetch user details from 'users' and 'user_details' table
$stmt = $pdo->prepare("
    SELECT u.*, d.* 
    FROM users u
    LEFT JOIN user_details d ON u.id = d.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['first_name']) ?>'s Profile</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: url('../img/churchinview.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
        }

        .container {
            width: 80%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .passport-container {
            position: absolute;
            top: 20px;
            left: 20px;
            text-align: center;
            border: 2px solid #2980b9;
            border-radius: 10px;
            padding: 10px;
            background: #f8f9fa;
            max-width: 150px;
        }

        .passport-container img {
            width: 100%;
            max-height: 120px;
            object-fit: cover;
            border-radius: 8px;
        }

        .passport-container iframe {
            width: 100%;
            height: 120px;
            border: none;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            font-size: 2em;
        }

        .profile-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .profile-section:hover {
            transform: translateY(-2px);
        }

        .btn {
            display: block;
            text-align: center;
            padding: 12px;
            margin-top: 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: linear-gradient(135deg, #2980b9, #2573a7);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<div class="container">
    <h2><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>'s Profile</h2>

 <!-- Passport Section -->
    <?php if (!empty($user['passport'])): ?>
        <div class="passport-container">
            <?php 
                $file_extension = pathinfo($user['passport'], PATHINFO_EXTENSION);
                if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])):
            ?>
                <img src="<?= htmlspecialchars($user['passport']) ?>" alt="Passport">
            <?php elseif (strtolower($file_extension) === 'pdf'): ?>
                <iframe src="<?= htmlspecialchars($user['passport']) ?>"></iframe>
            <?php else: ?>
                <p><a href="<?= htmlspecialchars($user['passport']) ?>" target="_blank">View Passport</a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="profile-section">
        <h3>Personal Information</h3>
        <div class="details">
            <p><span>Username:</span> <?= htmlspecialchars($user['username']) ?></p>
            <p><span>Email:</span> <?= htmlspecialchars($user['email']) ?></p>
            <p><span>Phone:</span> <?= htmlspecialchars($user['phone_number']) ?></p>
            <p><span>Gender:</span> <?= isset($user['gender']) ? htmlspecialchars(ucfirst($user['gender'])) : 'Not specified' ?></p>
            <<p><span>Marital Status:</span> <?= isset($user['marital_status']) ? htmlspecialchars($user['marital_status']) : 'Not specified' ?></p>
            <<p><span>Occupation:</span> <?= isset($user['occupation']) ? htmlspecialchars($user['occupation']) : 'Not specified' ?></p>

            <p><span>Education Level:</span> <?= isset($user['education_level']) ? htmlspecialchars($user['education_level']) : 'Not specified' ?></p>


            <p><span>Country:</span> <?= htmlspecialchars($user['country']) ?></p>
        </div>
    </div>

    <div class="profile-section">
        <h3>Church Information</h3>
        <div class="details">
            <p><span>Church ID:</span> <?= htmlspecialchars($user['member_id'] ?? 'Not assigned') ?></p>
            <p><span>Service Attending:</span> <?= isset($user['service_attending']) ? htmlspecialchars($user['service_attending']) : 'Not specified' ?></p>


            <?php if ($user['service_attending'] === 'KIKUYU SERVICE'): ?>
                <p><span>Kikuyu Cell Group:</span> <?= htmlspecialchars($user['kikuyu_cell_group']) ?></p>
                <p><span>Family Group:</span> <?= htmlspecialchars($user['family_group']) ?></p>
            <?php endif; ?>

            <?php if ($user['service_attending'] === 'ENGLISH SERVICE'): ?>
                <p><span>English Service Team:</span> <?= htmlspecialchars($user['english_service_team']) ?></p>
            <?php endif; ?>

            <p><span>Church Department:</span> <?= isset($user['church_department']) ? htmlspecialchars($user['church_department']) : 'Not specified' ?></p>

            <p><span>Ministry/Committee:</span> <?= isset($user['ministry_committee']) ? htmlspecialchars($user['ministry_committee']) : 'Not specified' ?></p>

        </div>
    </div>

    <div class="profile-section">
        <h3>Spiritual Status</h3>
        <div class="details">
            <p><span>Baptized:</span> <?= ($user['baptized'] == 1) ? 'Yes' : 'No' ?></p>
            <p><span>Confirmed:</span> <?= ($user['confirmed'] == 1) ? 'Yes' : 'No' ?></p>
        </div>
    </div>

    <div class="profile-section">
        <h3>Additional Documents</h3>
        <div class="details">
            <p><span>Passport:</span> 
                <?php if (!empty($user['passport'])): ?>
                    <a href="<?= htmlspecialchars($user['passport']) ?>" target="_blank">View Passport</a>
                <?php else: ?>
                    Not Uploaded
                <?php endif; ?>
            </p>
        </div>
    </div>

    <<a href="edit_profile.php?id=<?= isset($user['id']) ? htmlspecialchars($user['id']) : '' ?>" class="btn">Edit Profile</a>

</div>
</body>
</html>
