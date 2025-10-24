<?php
// scripts/convert_dependents_to_users.php
require_once __DIR__ . '/../includes/init.php';
$cutoff = date('Y-m-d', strtotime('-18 years'));
$stmt = $pdo->prepare("SELECT * FROM dependents WHERE is_converted_to_user = 0 AND date_of_birth <= ?");
$stmt->execute([$cutoff]);
$deps = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($deps as $d){
  try {
    $pdo->beginTransaction();
    $username = strtolower(preg_replace('/[^a-z0-9]/','', $d['first_name'].$d['last_name'])).time();
    $password_hash = password_hash(bin2hex(random_bytes(6)), PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (first_name,last_name,username,password,email,parish_id,created_at) VALUES (?,?,?,?,?,?,NOW())");
    $ins->execute([$d['first_name'],$d['last_name'],$username,$password_hash,null,$d['parish_id']]);
    $new_user_id = $pdo->lastInsertId();
    $upd = $pdo->prepare("UPDATE dependents SET is_converted_to_user=1, converted_user_id=? WHERE dependent_id=?");
    $upd->execute([$new_user_id, $d['dependent_id']]);
    // Optionally copy relationships: create user_relationships entries linking parent->new user
    $pdo->commit();
  } catch (Exception $e) {
    $pdo->rollBack();
    error_log("convert error: ".$e->getMessage());
  }
}


# Add to your crontab (run crontab -e)
0 8 * * * /usr/bin/php /path/to/your/church-management/modules/events/send_event_reminders.php