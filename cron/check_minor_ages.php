<?php
require_once '../config/database.php';

echo "Starting minor age check...\n";

try {
    // Find minors who have turned 18 and are ready for activation
    $stmt = $pdo->prepare("
        SELECT mp.id, mp.user_id, fm.minor_first_name, fm.minor_last_name,
               FLOOR(DATEDIFF(CURDATE(), fm.minor_date_of_birth) / 365.25) as current_age
        FROM minor_profiles mp
        JOIN family_members fm ON mp.family_member_id = fm.id
        WHERE fm.is_minor = 1 
          AND fm.can_activate_at_18 = 1
          AND FLOOR(DATEDIFF(CURDATE(), fm.minor_date_of_birth) / 365.25) >= 18
          AND mp.is_ready_for_activation = 0
    ");
    
    $stmt->execute();
    $minors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($minors)) {
        echo "No minors have reached 18 years old.\n";
        exit;
    }
    
    echo "Found " . count($minors) . " minors who have reached 18 years old.\n";
    
    foreach ($minors as $minor) {
        // Update minor profile
        $updateStmt = $pdo->prepare("
            UPDATE minor_profiles 
            SET is_ready_for_activation = 1, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([$minor['id']]);
        
        echo "Updated minor profile for {$minor['minor_first_name']} {$minor['minor_last_name']} (ID: {$minor['id']})\n";
        
        // Notify guardian (you can implement email notification here)
        $guardianStmt = $pdo->prepare("
            SELECT email, first_name FROM users WHERE id = ?
        ");
        $guardianStmt->execute([$minor['user_id']]);
        $guardian = $guardianStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($guardian) {
            $subject = "Minor Ready for Account Activation";
            $message = "
                Dear {$guardian['first_name']},
                
                Your minor family member, {$minor['minor_first_name']} {$minor['minor_last_name']}, 
                has reached 18 years of age and is now eligible for account activation.
                
                Please log in to your account to request activation.
                
                Best regards,
                Church Management System
            ";
            
            // Use your email service here
            // sendEmail($guardian['email'], $subject, $message);
            
            echo "Notification sent to guardian: {$guardian['email']}\n";
        }
    }
    
    echo "Minor age check completed successfully.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Cron job error: " . $e->getMessage());
}
?>