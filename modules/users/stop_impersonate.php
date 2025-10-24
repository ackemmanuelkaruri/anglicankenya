<?php
/**
 * Stop User Impersonation Module
 */

require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';
require_once '../../includes/rbac.php';

start_secure_session();

// Check if user is currently impersonating
if (!isset($_SESSION['original_user_id'])) {
    header('Location: ../../dashboard.php');
    exit;
}

// Stop impersonation
if (stop_impersonation()) {
    $_SESSION['success'] = "Impersonation ended successfully";
} else {
    $_SESSION['error'] = "Failed to stop impersonation";
}

header('Location: ../../dashboard.php');
exit;