<?php
// modules/members/ajax/update-personal.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/security.php';
start_secure_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit;
}
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit;
}
$user_id = (int)$_SESSION['user_id'];
$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$dob   = $_POST['dob'] ?? null;
$gender= $_POST['gender'] ?? null;

if (empty($first) || empty($last)) {
    echo json_encode(['success'=>false,'message'=>'First and last name required']); exit;
}
try {
    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, dob=?, gender=? WHERE id=?");
    $stmt->execute([$first,$last,$dob,$gender,$user_id]);
    echo json_encode(['success'=>true,'message'=>'Saved']);
} catch (PDOException $e) {
    error_log("update-personal: ".$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Save failed']);
}
