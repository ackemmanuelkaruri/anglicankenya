<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once 'data_queries.php';

try {
    // Create a PDO connection using your existing db.php
    $pdo = new PDO("mysql:host=localhost;dbname=emmanuelkaruri;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $queries = new DataQueries($pdo);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'pcc_members':
            $data = $queries->getPCCMembers();
            echo json_encode($data ?: ['error' => 'No PCC members found']);
            break;
            
        case 'family_relationships':
            $data = $queries->getFamilyRelationships();
            echo json_encode($data ?: ['error' => 'No family relationships found']);
            break;
            
        case 'department_heads':
            $data = $queries->getDepartmentHeads();
            echo json_encode($data ?: ['error' => 'No department heads found']);
            break;
            
        case 'clergy_members':
            $data = $queries->getClergyMembers();
            echo json_encode($data ?: ['error' => 'No clergy members found']);
            break;
            
        case 'leadership_history':
            $data = $queries->getLeadershipHistory();
            echo json_encode($data ?: ['error' => 'No leadership history found']);
            break;
            
        case 'spouse_relationships':
            $data = $queries->getSpouseRelationships();
            echo json_encode($data ?: ['error' => 'No spouse relationships found']);
            break;
            
        case 'family_units':
            $data = $queries->getFamilyUnits();
            echo json_encode($data ?: ['error' => 'No family units found']);
            break;
            
        case 'extended_family':
            $data = $queries->getExtendedFamily();
            echo json_encode($data ?: ['error' => 'No extended family relationships found']);
            break;
            
        case 'single_parents':
            $data = $queries->getSingleParents();
            echo json_encode($data ?: ['error' => 'No single parents found']);
            break;
            
        case 'youth_members':
            $data = $queries->getYouthMembers();
            echo json_encode($data ?: ['error' => 'No youth members found']);
            break;
            
        case 'senior_members':
            $data = $queries->getSeniorMembers();
            echo json_encode($data ?: ['error' => 'No senior members found']);
            break;
            
        case 'ministry_members':
            $data = $queries->getMinistryMembers();
            echo json_encode($data ?: ['error' => 'No ministry members found']);
            break;
            
        case 'employment_history':
            $data = $queries->getEmploymentHistory();
            echo json_encode($data ?: ['error' => 'No employment history found']);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
}
?>