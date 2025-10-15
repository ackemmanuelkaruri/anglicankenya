<?php
require_once '../db.php'; // Ensure correct path to db.php

// Enable error reporting for detailed debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log debugging information
function debugLog($message) {
    error_log($message, 3, '../debug_update.log');
    echo $message . "<br>";
}

// Function to handle file uploads
function handleFileUpload($fileInputName, $uploadDir, $allowedTypes) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == 0) {
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Validate file type
        $fileExtension = strtolower(pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            die("<p style='color: red;'>Invalid file type for " . str_replace('_', ' ', $fileInputName) . ". Only " . strtoupper(implode(', ', $allowedTypes)) . " allowed.</p>");
        }

        $filename = uniqid() . '_' . basename($_FILES[$fileInputName]['name']);
        $uploadPath = $uploadDir . $filename;

        debugLog(ucfirst(str_replace('_', ' ', $fileInputName)) . " Upload Path: " . $uploadPath);

        if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $uploadPath)) {
            debugLog(ucfirst(str_replace('_', ' ', $fileInputName)) . " Uploaded Successfully");
            return $filename;
        } else {
            debugLog(ucfirst(str_replace('_', ' ', $fileInputName)) . " Upload Failed");
            debugLog("Upload Error: " . $_FILES[$fileInputName]['error']);
            return null;
        }
    }
    return null;
}

// Ensure user ID is set
if (!isset($_POST['id'])) {
    die("<p style='color: red;'>User ID is missing.</p>");
}
$id = $_POST['id'];

// Debug: Log all received POST data
debugLog("Received POST Data:");
foreach ($_POST as $key => $value) {
    if (is_array($value)) {
        debugLog($key . ": " . implode(', ', $value));
    } else {
        debugLog($key . ": " . $value);
    }
}

// Debug: Log all received FILES data
debugLog("\nReceived FILES Data:");
foreach ($_FILES as $key => $file) {
    debugLog($key . ": " . print_r($file, true));
}

// Personal Information Update - ADD NULL COALESCING AND SANITIZATION
$first_name = $_POST['first_name'] ?? null;
$last_name = $_POST['last_name'] ?? null;
$email = $_POST['email'] ?? null;
$phone_number = $_POST['phone_number'] ?? null;
$gender = $_POST['gender'] ?? null;
$country = $_POST['country'] ?? null;
$occupation = $_POST['occupation'] ?? null;
$marital_status = $_POST['marital_status'] ?? null;
$wedding_type = $_POST['wedding_type'] ?? null;
$education_level = $_POST['education_level'] ?? null;

// Validate required fields
$required_fields = ['first_name', 'last_name', 'email'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    die("<p style='color: red;'>Missing required fields: " . implode(', ', $missing_fields) . "</p>");
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("<p style='color: red;'>Invalid email format.</p>");
}

// Church Details
// Service Attending - Multiple Choice - FIXED HANDLING
$service_attending = isset($_POST['service_attending']) ? (is_array($_POST['service_attending']) ? implode(',', $_POST['service_attending']) : $_POST['service_attending']) : null;
debugLog("Service Attending (Before DB): " . ($service_attending ? $service_attending : 'NULL'));

// Family Group - Multiple Choice - FIXED HANDLING
$family_group = isset($_POST['family_group']) ? (is_array($_POST['family_group']) ? implode(',', $_POST['family_group']) : $_POST['family_group']) : null;
debugLog("Family Group (Before DB): " . ($family_group ? $family_group : 'NULL'));

// Baptism Details
$baptized = $_POST['baptized'] ?? null;
$want_to_be_baptized = $_POST['want_to_be_baptized'] ?? null;

// Handle baptism certificate upload
$baptism_certificate = handleFileUpload('baptism_certificate', '../uploads/baptism_certificates/', ['pdf', 'jpg', 'jpeg', 'png']);

// Confirmation Details
$confirmed = $_POST['confirmed'] ?? null;
$want_to_be_confirmed = $_POST['want_to_be_confirmed'] ?? null;

// Handle confirmation certificate upload
$confirmation_certificate = handleFileUpload('confirmation_certificate', '../uploads/confirmation_certificates/', ['pdf', 'jpg', 'jpeg', 'png']);

// Church Membership No - to be filled later by admin (left as null)
$church_membership_no = null;

try {
    // Check if user exists first
    $check_sql = "SELECT id FROM users WHERE id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $id]);

    if ($check_stmt->rowCount() === 0) {
        die("<p style='color: red;'>User not found.</p>");
    }

    // Update users table
    $sql = "UPDATE users SET 
                first_name = :first_name, 
                last_name = :last_name, 
                email = :email, 
                phone_number = :phone_number, 
                gender = :gender, 
                country = :country, 
                occupation = :occupation, 
                marital_status = :marital_status, 
                wedding_type = :wedding_type, 
                education_level = :education_level,
                service_attending = :service_attending,
                family_group = :family_group,
                baptized = :baptized,
                baptism_certificate = :baptism_certificate,
                want_to_be_baptized = :want_to_be_baptized,
                confirmed = :confirmed,
                confirmation_certificate = :confirmation_certificate,
                want_to_be_confirmed = :want_to_be_confirmed,
                church_membership_no = :church_membership_no
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $params = [
        ':id' => $id,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':phone_number' => $phone_number,
        ':gender' => $gender,
        ':country' => $country,
        ':occupation' => $occupation,
        ':marital_status' => $marital_status,
        ':wedding_type' => $wedding_type,
        ':education_level' => $education_level,
        ':service_attending' => $service_attending,
        ':family_group' => $family_group,
        ':baptized' => $baptized,
        ':baptism_certificate' => $baptism_certificate,
        ':want_to_be_baptized' => $want_to_be_baptized,
        ':confirmed' => $confirmed,
        ':confirmation_certificate' => $confirmation_certificate,
        ':want_to_be_confirmed' => $want_to_be_confirmed,
        ':church_membership_no' => $church_membership_no
    ];

    // Debug: Log all parameters
    debugLog("\nDatabase Update Parameters:");
    foreach ($params as $key => $value) {
        debugLog($key . ": " . ($value === null ? 'NULL' : $value));
    }

    $result = $stmt->execute($params);

    if ($result && $stmt->rowCount() > 0) {
        debugLog("<p style='color: green;'>Information Updated Successfully!</p>");
        echo "<p style='color: green;'>Information Updated Successfully!</p>";
    } else {
        debugLog("<p style='color: orange;'>No changes were made or user not found.</p>");
        echo "<p style='color: orange;'>No changes were made or user not found.</p>";
    }

} catch (PDOException $e) {
    debugLog("<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>");
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    debugLog("<p style='color: red;'>General Error: " . $e->getMessage() . "</p>");
    echo "<p style='color: red;'>General Error: " . $e->getMessage() . "</p>";
}
?>