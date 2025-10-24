<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/rbac.php';
start_secure_session();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name,last_name,dob,gender FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$csrf = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(16));
$_SESSION['csrf_token'] = $csrf;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Personal Info</title>
  <link rel="stylesheet" href="/modules/members/css/members.css">
</head>
<body>
  <div class="container">
    <h2>Edit Personal Info</h2>
    <form id="personalForm" method="post" action="ajax/update-personal.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      <label>First name
        <input name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
      </label>
      <label>Last name
        <input name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
      </label>
      <label>Date of birth
        <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>">
      </label>
      <label>Gender
        <select name="gender">
          <option value="">Select</option>
          <option <?php if($user['gender']=='Male') echo 'selected'; ?>>Male</option>
          <option <?php if($user['gender']=='Female') echo 'selected'; ?>>Female</option>
          <option <?php if($user['gender']=='Other') echo 'selected'; ?>>Other</option>
        </select>
      </label>
      <button class="btn" type="submit">Save</button>
    </form>
    <div id="personalMsg"></div>
  </div>
  <script src="/modules/members/js/members.js"></script>
</body>
</html>
