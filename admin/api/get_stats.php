<?php
header('Content-Type: application/json');
require_once '../db.php'; // Use the existing db.php

// Create a PDO connection using your existing db.php
$pdo = new PDO("mysql:host=localhost;dbname=emmanuelkaruri;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'total_members':
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'leadership_count':
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM user_leadership WHERE is_current = 1");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'clergy_count':
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM clergy_roles WHERE is_current = 1");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'family_count':
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT 
                CASE 
                    WHEN parent_id IS NOT NULL THEN parent_id
                    WHEN user1_id IS NOT NULL THEN LEAST(user1_id, user2_id)
                END
            ) AS count 
            FROM family_relationships
            WHERE parent_id IS NOT NULL OR user1_id IS NOT NULL
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'baptized_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM users 
            WHERE baptized = 'yes'
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'confirmed_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM users 
            WHERE confirmed = 'yes'
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'department_count':
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM departments");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'ministry_count':
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM ministries");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'pcc_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM user_leadership 
            WHERE (leadership_type LIKE '%PCC%' OR department LIKE '%PCC%') AND is_current = 1
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'youth_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM users 
            WHERE TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 35
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'senior_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM users 
            WHERE TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 65
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'single_parent_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM users 
            WHERE marital_status = 'single parent'
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'married_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM users 
            WHERE marital_status = 'married'
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'widowed_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM users 
            WHERE marital_status = 'widowed'
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    case 'divorced_count':
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count 
            FROM users 
            WHERE marital_status = 'divorced'
        ");
        $result = $stmt->fetch();
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>