<?php
/**
 * ============================================
 * CHURCH CREATION HANDLER
 * Processes new church registration
 * ============================================
 * 
 * Create this file at: /admin/create-church.php
 */

define('DB_INCLUDED', true);

require_once '../db.php';
require_once '../includes/security.php';

start_secure_session();
require_login();

// Only Super Admin can create churches
if (!is_super_admin()) {
    header('HTTP/1.1 403 Forbidden');
    die('Access Denied: Only Super Admins can create churches.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $org_name = sanitize_input($_POST['org_name'] ?? '');
    $org_code = strtoupper(sanitize_input($_POST['org_code'] ?? ''));
    $diocese = sanitize_input($_POST['diocese'] ?? '');
    $archdeaconry = sanitize_input($_POST['archdeaconry'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $country = sanitize_input($_POST['country'] ?? 'Kenya');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $max_users = intval($_POST['max_users'] ?? 500);
    $subscription_status = sanitize_input($_POST['subscription_status'] ?? 'active');
    
    // Validation
    if (empty($org_name)) {
        $error = 'Church name is required.';
    } elseif (empty($org_code)) {
        $error = 'Church code is required.';
    } elseif (empty($city)) {
        $error = 'City is required.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            // Check if org_code already exists
            $stmt = $pdo->prepare("SELECT id FROM organizations WHERE org_code = ?");
            $stmt->execute([$org_code]);
            if ($stmt->fetch()) {
                $error = 'Church code already exists. Please use a unique code.';
            } else {
                // Insert new organization
                $stmt = $pdo->prepare("
                    INSERT INTO organizations (
                        org_name,
                        org_code,
                        diocese,
                        archdeaconry,
                        address,
                        city,
                        country,
                        phone,
                        email,
                        max_users,
                        subscription_status,
                        registration_date,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $org_name,
                    $org_code,
                    $diocese,
                    $archdeaconry,
                    $address,
                    $city,
                    $country,
                    $phone,
                    $email,
                    $max_users,
                    $subscription_status
                ]);
                
                $new_org_id = $pdo->lastInsertId();
                
                // Log activity
                log_activity(
                    'CHURCH_CREATED', 
                    'organizations', 
                    $new_org_id,
                    null,
                    [
                        'org_name' => $org_name,
                        'org_code' => $org_code,
                        'city' => $city
                    ]
                );
                
                $success = "Church '$org_name' created successfully! Church ID: $new_org_id";
                
                // Optionally create default departments and ministries for this church
                create_default_departments($pdo, $new_org_id);
                create_default_ministries($pdo, $new_org_id);
                
                // Redirect to view church
                header('Location: super-admin-dashboard.php?success=' . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            error_log("Church creation error: " . $e->getMessage());
            $error = 'An error occurred while creating the church. Please try again.';
        }
    }
    
    // If there's an error, redirect back with error message
    if (!empty($error)) {
        header('Location: super-admin-dashboard.php?error=' . urlencode($error));
        exit;
    }
}

/**
 * Create default departments for new church
 */
function create_default_departments($pdo, $org_id) {
    $default_departments = [
        'Youth Ministry',
        'Women Ministry',
        'Men Ministry',
        'Children Ministry',
        'Worship & Music',
        'Evangelism',
        'Prayer Ministry',
        'Social Services',
        'Education & Training',
        'Finance'
    ];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO departments (name, created_at) VALUES (?, NOW())");
        foreach ($default_departments as $dept) {
            $stmt->execute([$dept]);
        }
    } catch (Exception $e) {
        error_log("Could not create default departments: " . $e->getMessage());
    }
}

/**
 * Create default ministries for new church
 */
function create_default_ministries($pdo, $org_id) {
    $default_ministries = [
        'Sunday School',
        'Choir',
        'Ushering',
        'Media & Technology',
        'Hospitality',
        'Intercession',
        'Community Outreach',
        'Counseling',
        'Church Planting',
        'Missions'
    ];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO ministries (name, created_at) VALUES (?, NOW())");
        foreach ($default_ministries as $ministry) {
            $stmt->execute([$ministry]);
        }
    } catch (Exception $e) {
        error_log("Could not create default ministries: " . $e->getMessage());
    }
}

// If accessed directly via GET, redirect to dashboard
header('Location: super-admin-dashboard.php');
exit;