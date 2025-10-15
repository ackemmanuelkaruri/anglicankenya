<?php
session_start();
include 'db.php'; // Include your database connection file
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_code = $_POST['code'];
    if ($input_code == $_SESSION['verification_code']) {
        // Code is correct, allow user to set a new password
        header("Location: set_new_password.php");
        exit();
    } else {
        $error_message = "Incorrect verification code.";
    }
}
?>
<?php include 'header.php'; ?> <!-- Include the header -->
<!DOCTYPE html>
<html>
<head>
    <title>Verify Code</title>
    <?php include 'includes/styles.php'; ?>
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS file -->
</head>
<body>
    <div class="container mt-5">
        <div class="verify-form border rounded p-4 shadow">
            <h2 class="text-center">Verify Code</h2>
            <?php if (isset($error_message)) { echo "<div class='alert alert-danger'>$error_message</div>"; } ?>
            <form method="POST">
                <div class="mb-3">
                    <input type="text" class="form-control" name="code" placeholder="Enter Verification Code" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify</button>
            </form>
        </div>
    </div>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>