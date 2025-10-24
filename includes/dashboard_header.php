<?php
/**
 * ============================================
 * DASHBOARD HEADER - WITH DYNAMIC PATH CORRECTION
 * Fixes 404 errors for CSS and JS assets in subdirectories.
 * ============================================
 */

// --- DYNAMIC PATH CALCULATION ---
// Determines how many levels up (../) to go to reach the root 'css/' and 'js/' folders.
$current_path = $_SERVER['PHP_SELF'];
$base_path = '';

// If the URL contains '/modules/', we are two levels deep (e.g., /modules/events/index.php)
if (strpos($current_path, '/modules/') !== false) {
    $base_path = '../../'; // Go up two levels to the root /anglicankenya/
} else {
    $base_path = './';       // Stays in the current directory (for root pages like /dashboard.php)
}
// Note: $theme and $is_impersonating are expected to be defined by the including file (e.g., dashboard.php)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Church Management System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="<?php echo $base_path; ?>css/dashboard.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>css/list.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>css/themes.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>css/role-colors.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>css/footer-styles.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>css/impersonation-banner.css" rel="stylesheet">

<script src="<?php echo $base_path; ?>js/dashboard.js" defer></script> 
</head>
<body data-theme="<?php echo htmlspecialchars($theme ?? 'light'); ?>" 
      <?php echo ($is_impersonating ?? false) ? 'class="impersonating"' : ''; ?>>