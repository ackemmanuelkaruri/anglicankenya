<?php
require_once 'anglican_province.php';

header('Content-Type: application/json');

// Get parameters with better validation
 $level = isset($_GET['level']) ? strtolower(trim($_GET['level'])) : '';
 $diocese = isset($_GET['diocese']) ? trim($_GET['diocese']) : '';
 $archdeaconry = isset($_GET['archdeaconry']) ? trim($_GET['archdeaconry']) : '';
 $deanery = isset($_GET['deanery']) ? trim($_GET['deanery']) : '';

 $result = [];

switch ($level) {
    case 'diocese':
        // Get all dioceses from the file-based system
        $dioceses = getAllDioceses();
        if ($dioceses) {
            foreach ($dioceses as $d) {
                $result[] = ['name' => $d['name']];
            }
        }
        break;
        
    case 'archdeaconry':
        // Load the specific diocese
        if (!empty($diocese)) {
            $dioceseData = loadDiocese($diocese);
            if ($dioceseData && isset($dioceseData['archdeaconries'])) {
                foreach ($dioceseData['archdeaconries'] as $a) {
                    $result[] = ['name' => $a['name']];
                }
            }
        }
        break;
        
    case 'deanery':
        // Load the diocese
        if (!empty($diocese) && !empty($archdeaconry)) {
            $dioceseData = loadDiocese($diocese);
            if ($dioceseData) {
                // Find the archdeaconry
                foreach ($dioceseData['archdeaconries'] as $a) {
                    if ($a['name'] === $archdeaconry) {
                        if (isset($a['deaneries'])) {
                            foreach ($a['deaneries'] as $de) {
                                $result[] = ['name' => $de['name']];
                            }
                        }
                        break;
                    }
                }
            }
        }
        break;
        
    case 'parish':
        // Load the diocese
        if (!empty($diocese) && !empty($archdeaconry) && !empty($deanery)) {
            $dioceseData = loadDiocese($diocese);
            if ($dioceseData) {
                // Find the archdeaconry
                foreach ($dioceseData['archdeaconries'] as $a) {
                    if ($a['name'] === $archdeaconry) {
                        // Find the deanery
                        foreach ($a['deaneries'] as $de) {
                            if ($de['name'] === $deanery) {
                                if (isset($de['parishes'])) {
                                    foreach ($de['parishes'] as $p) {
                                        // Return parish as object with name property
                                        $result[] = ['name' => $p];
                                    }
                                }
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        }
        break;
        
    default:
        // Handle invalid level parameter
        $result = ['error' => 'Invalid level parameter'];
        break;
}

echo json_encode($result);
?>