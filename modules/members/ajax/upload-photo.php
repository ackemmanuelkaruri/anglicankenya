<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/init.php';
start_secure_session();
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if (!isset($_FILES['photo'])) { echo json_encode(['success'=>false,'message'=>'No file']); exit; }
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) { echo json_encode(['success'=>false,'message'=>'CSRF']); exit; }

$user_id = (int)$_SESSION['user_id'];
$uploadDir = __DIR__ . '/../../../uploads/avatars/';
if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
$file = $_FILES['photo'];
$allowed = ['image/jpeg','image/png','image/webp'];
if ($file['error'] !== UPLOAD_ERR_OK) { echo json_encode(['success'=>false,'message'=>'Upload error']); exit; }
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
if (!in_array($mime,$allowed)) { echo json_encode(['success'=>false,'message'=>'Invalid file type']); exit; }
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'user_'.$user_id.'_'.time().'.'.$ext;
$dest = $uploadDir.$filename;
if (!move_uploaded_file($file['tmp_name'],$dest)) { echo json_encode(['success'=>false,'message'=>'Move failed']); exit; }
try {
    $stmt = $pdo->prepare("UPDATE users SET avatar=? WHERE id=?");
    $stmt->execute([$filename,$user_id]);
    echo json_encode(['success'=>true,'message'=>'Uploaded','filename'=>$filename]);
} catch (PDOException $e) {
    error_log("upload-photo: ".$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'DB update failed']);
}
