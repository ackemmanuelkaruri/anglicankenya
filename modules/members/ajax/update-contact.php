<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/init.php';
start_secure_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false]); exit; }
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'CSRF']); exit;
}
$user_id = (int)$_SESSION['user_id'];
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
try {
    $stmt = $pdo->prepare("UPDATE users SET phone=?, email=?, address=? WHERE id=?");
    $stmt->execute([$phone, $email, $address, $user_id]);
    echo json_encode(['success'=>true,'message'=>'Contact updated']);
} catch (PDOException $e) {
    error_log("update-contact: ".$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Save failed']);
}
