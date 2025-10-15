<?php
session_start();
require_once '../db.php'; // Use your existing database connection
require_once 'functions.php'; // Use your existing functions file
require_once 'admin_auth.php'; // Make sure this exists or create it

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get all pending activation requests
try {
    $requestsStmt = $pdo->prepare("
        SELECT mar.*, mp.*, fm.minor_first_name, fm.minor_last_name, fm.minor_email,
               fm.minor_date_of_birth,
               u.first_name as guardian_first_name, u.last_name as guardian_last_name, u.email as guardian_email,
               FLOOR(DATEDIFF(CURDATE(), fm.minor_date_of_birth) / 365.25) as current_age
        FROM minor_activation_requests mar
        JOIN minor_profiles mp ON mar.minor_profile_id = mp.id
        JOIN family_members fm ON mp.family_member_id = fm.id
        JOIN users u ON mar.requested_by_user_id = u.id
        WHERE mar.status = 'pending'
        ORDER BY mar.created_at DESC
    ");
    $requestsStmt->execute();
    $requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $requests = [];
    $error = "Database error: " . $e->getMessage();
}

// Handle form submission for approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];
    $response = $_POST['response'] ?? '';
    $adminId = $_SESSION['user_id'];
    
    try {
        // Get the activation request
        $requestStmt = $pdo->prepare("
            SELECT * FROM minor_activation_requests WHERE id = ?
        ");
        $requestStmt->execute([$requestId]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            throw new Exception('Activation request not found');
        }
        
        if ($action === 'approve') {
            // Approve the request
            $updateStmt = $pdo->prepare("
                UPDATE minor_activation_requests 
                SET status = 'approved', admin_response = ?, processed_by_admin_id = ?, processed_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([$response, $adminId, $requestId]);
            
            // Update minor profile
            $profileStmt = $pdo->prepare("
                UPDATE minor_profiles 
                SET admin_approval_status = 'approved', admin_approval_date = CURRENT_TIMESTAMP, 
                    approved_by_admin_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $profileStmt->execute([$adminId, $request['minor_profile_id']]);
            
            // Unlock all tabs for the minor
            $unlockStmt = $pdo->prepare("
                UPDATE minor_tab_permissions 
                SET is_accessible = 1, unlocked_at = CURRENT_TIMESTAMP, unlocked_by_admin_id = ?
                WHERE minor_profile_id = ?
            ");
            $unlockStmt->execute([$adminId, $request['minor_profile_id']]);
            
            $successMessage = "Activation request approved successfully";
            
        } elseif ($action === 'reject') {
            // Reject the request
            $updateStmt = $pdo->prepare("
                UPDATE minor_activation_requests 
                SET status = 'rejected', admin_response = ?, processed_by_admin_id = ?, processed_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([$response, $adminId, $requestId]);
            
            // Update minor profile
            $profileStmt = $pdo->prepare("
                UPDATE minor_profiles 
                SET admin_approval_status = 'rejected', admin_approval_date = CURRENT_TIMESTAMP, 
                    approved_by_admin_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $profileStmt->execute([$adminId, $request['minor_profile_id']]);
            
            $successMessage = "Activation request rejected successfully";
        } else {
            throw new Exception('Invalid action');
        }
        
        // Redirect to refresh the page
        header("Location: admin_activation_requests.php?success=" . urlencode($successMessage));
        exit;
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minor Activation Requests - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background-image: url('../img/face.jpg'); 
            background-size: cover; 
            background-attachment: fixed;
        }
        .dashboard-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }
        .activation-request-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .request-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .minor-info h3 {
            margin: 0 0 10px 0;
        }
        .age-badge {
            background: #6c757d;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        .date-requested {
            color: #6c757d;
            font-size: 0.9em;
        }
        .request-actions {
            display: flex;
            gap: 10px;
        }
        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .request-details {
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .detail-section h4 {
            margin: 0 0 15px 0;
            color: #495057;
        }
        .admin-response-form {
            background: #f8f9fa;
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: none;
        }
        .admin-response-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .response-actions {
            display: flex;
            gap: 10px;
        }
        .btn-confirm-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-confirm-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-cancel-response {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .no-requests {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-info" style="min-height: 100vh; margin-left: -30px;">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action bg-info text-white text-center">Dashboard</a>
                    <a href="administrators.php" class="list-group-item list-group-item-action bg-info text-white text-center">Administrator</a>
                    <a href="users.php" class="list-group-item list-group-item-action bg-info text-white text-center">Users</a>
                    <a href="parishioners.php" class="list-group-item list-group-item-action bg-info text-white text-center">Parishioners</a>
                    <a href="admin_activation_requests.php" class="list-group-item list-group-item-action bg-info text-white text-center active">Minor Activations</a>
                </div>
            </div>
            <div class="col-md-10">
                <div class="dashboard-container">
                    <h1>Minor Account Activation Requests</h1>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="requests-container">
                        <?php if (empty($requests)): ?>
                            <div class="no-requests">
                                <p>No pending activation requests.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <div class="activation-request-card" data-request-id="<?= $request['id'] ?>">
                                    <div class="request-header">
                                        <div class="minor-info">
                                            <h3><?= htmlspecialchars($request['minor_first_name'] . ' ' . $request['minor_last_name']) ?></h3>
                                            <span class="age-badge"><?= $request['current_age'] ?> years old</span>
                                            <span class="date-requested">Requested: <?= date('M d, Y', strtotime($request['created_at'])) ?></span>
                                        </div>
                                        
                                        <div class="request-actions">
                                            <button type="button" class="btn-approve" data-request-id="<?= $request['id'] ?>">
                                                Approve Activation
                                            </button>
                                            <button type="button" class="btn-reject" data-request-id="<?= $request['id'] ?>">
                                                Reject Request
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="request-details">
                                        <div class="detail-section">
                                            <h4>Minor Details</h4>
                                            <p><strong>Email:</strong> <?= htmlspecialchars($request['minor_email'] ?? 'Not provided') ?></p>
                                            <p><strong>Date of Birth:</strong> <?= date('M d, Y', strtotime($request['minor_date_of_birth'])) ?></p>
                                        </div>
                                        
                                        <div class="detail-section">
                                            <h4>Guardian Information</h4>
                                            <p><strong>Guardian:</strong> <?= htmlspecialchars($request['guardian_first_name'] . ' ' . $request['guardian_last_name']) ?></p>
                                            <p><strong>Email:</strong> <?= htmlspecialchars($request['guardian_email']) ?></p>
                                        </div>
                                        
                                        <div class="detail-section">
                                            <h4>Request Reason</h4>
                                            <p><?= htmlspecialchars($request['request_reason'] ?? 'No reason provided') ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Response form (initially hidden) -->
                                    <div class="admin-response-form" id="response-form-<?= $request['id'] ?>" style="display: none;">
                                        <h4>Admin Response</h4>
                                        <textarea name="admin_response" placeholder="Optional message to guardian..." rows="3"></textarea>
                                        <div class="response-actions">
                                            <button type="button" class="btn-confirm-approve" data-request-id="<?= $request['id'] ?>">
                                                Confirm Approval
                                            </button>
                                            <button type="button" class="btn-confirm-reject" data-request-id="<?= $request['id'] ?>">
                                                Confirm Rejection
                                            </button>
                                            <button type="button" class="btn-cancel-response">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Approve button
            document.querySelectorAll('.btn-approve').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    const form = document.getElementById(`response-form-${requestId}`);
                    form.style.display = 'block';
                });
            });
            
            // Reject button
            document.querySelectorAll('.btn-reject').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    const form = document.getElementById(`response-form-${requestId}`);
                    form.style.display = 'block';
                });
            });
            
            // Cancel response
            document.querySelectorAll('.btn-cancel-response').forEach(button => {
                button.addEventListener('click', function() {
                    const form = this.closest('.admin-response-form');
                    form.style.display = 'none';
                });
            });
            
            // Confirm approval
            document.querySelectorAll('.btn-confirm-approve').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    const form = document.getElementById(`response-form-${requestId}`);
                    const response = form.querySelector('textarea').value;
                    
                    if (confirm('Are you sure you want to approve this activation request?')) {
                        // Create a form and submit it
                        const formElement = document.createElement('form');
                        formElement.method = 'POST';
                        formElement.action = '';
                        
                        const actionInput = document.createElement('input');
                        actionInput.name = 'action';
                        actionInput.value = 'approve';
                        formElement.appendChild(actionInput);
                        
                        const requestIdInput = document.createElement('input');
                        requestIdInput.name = 'request_id';
                        requestIdInput.value = requestId;
                        formElement.appendChild(requestIdInput);
                        
                        const responseInput = document.createElement('input');
                        responseInput.name = 'response';
                        responseInput.value = response;
                        formElement.appendChild(responseInput);
                        
                        document.body.appendChild(formElement);
                        formElement.submit();
                    }
                });
            });
            
            // Confirm rejection
            document.querySelectorAll('.btn-confirm-reject').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    const form = document.getElementById(`response-form-${requestId}`);
                    const response = form.querySelector('textarea').value;
                    
                    if (confirm('Are you sure you want to reject this activation request?')) {
                        // Create a form and submit it
                        const formElement = document.createElement('form');
                        formElement.method = 'POST';
                        formElement.action = '';
                        
                        const actionInput = document.createElement('input');
                        actionInput.name = 'action';
                        actionInput.value = 'reject';
                        formElement.appendChild(actionInput);
                        
                        const requestIdInput = document.createElement('input');
                        requestIdInput.name = 'request_id';
                        requestIdInput.value = requestId;
                        formElement.appendChild(requestIdInput);
                        
                        const responseInput = document.createElement('input');
                        responseInput.name = 'response';
                        responseInput.value = response;
                        formElement.appendChild(responseInput);
                        
                        document.body.appendChild(formElement);
                        formElement.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>