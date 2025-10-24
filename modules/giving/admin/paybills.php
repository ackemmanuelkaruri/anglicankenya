<?php
/**
 * Paybill Management Page for Parish Admins
 */

require_once __DIR__ . '/../../../db.php';
require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/security.php';

start_secure_session();

// Ensure user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

 $userId = $_SESSION['user_id'];
 $roleLevel = $_SESSION['role_level'] ?? 'member';

// Only parish admins and above can access this page
if (!in_array($roleLevel, ['parish_admin', 'deanery_admin', 'archdeaconry_admin', 'diocese_admin', 'national_admin', 'super_admin'])) {
    header('Location: ../../dashboard.php');
    exit;
}

// Get user details
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$userId]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get parish Paybills
require_once __DIR__ . '/../includes/giving_functions.php';
 $paybills = getParishPaybills($user['parish_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }
    
    if (isset($_POST['add_paybill'])) {
        // Add new paybill
        $paybillNumber = htmlspecialchars($_POST['paybill_number']);
        $account = htmlspecialchars($_POST['account'] ?? '');
        $purpose = htmlspecialchars($_POST['purpose']);
        $description = htmlspecialchars($_POST['description'] ?? '');
        
        if (empty($paybillNumber) || empty($purpose)) {
            $error = "Paybill number and purpose are required";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO parish_paybills 
                    (parish_id, paybill_number, account, purpose, description, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $user['parish_id'],
                    $paybillNumber,
                    $account,
                    $purpose,
                    $description
                ]);
                
                $success = "Paybill added successfully";
                header('Location: paybills.php?success=1');
                exit;
            } catch (PDOException $e) {
                $error = "Error adding paybill: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_paybill'])) {
        // Update existing paybill
        $paybillId = (int)$_POST['paybill_id'];
        $paybillNumber = htmlspecialchars($_POST['paybill_number']);
        $account = htmlspecialchars($_POST['account'] ?? '');
        $purpose = htmlspecialchars($_POST['purpose']);
        $description = htmlspecialchars($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($paybillNumber) || empty($purpose)) {
            $error = "Paybill number and purpose are required";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE parish_paybills 
                    SET paybill_number = ?, account = ?, purpose = ?, description = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ? AND parish_id = ?
                ");
                $stmt->execute([
                    $paybillNumber,
                    $account,
                    $purpose,
                    $description,
                    $isActive,
                    $paybillId,
                    $user['parish_id']
                ]);
                
                $success = "Paybill updated successfully";
            } catch (PDOException $e) {
                $error = "Error updating paybill: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_paybill'])) {
        // Delete paybill
        $paybillId = (int)$_POST['paybill_id'];
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM parish_paybills 
                WHERE id = ? AND parish_id = ?
            ");
            $stmt->execute([$paybillId, $user['parish_id']]);
            
            $success = "Paybill deleted successfully";
        } catch (PDOException $e) {
            $error = "Error deleting paybill: " . $e->getMessage();
        }
    }
}

// Page title
$page_title = "Manage Paybills - " . htmlspecialchars($user['parish_name'] ?? 'Parish');

// Include header

?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?php echo $page_title; ?></h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Giving Dashboard
                </a>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Paybill Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add New Paybill</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="add_paybill" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="paybill_number" class="form-label">Paybill Number</label>
                                <input type="text" class="form-control" id="paybill_number" name="paybill_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="account" class="form-label">Account (Optional)</label>
                                <input type="text" class="form-control" id="account" name="account">
                                <div class="form-text">Some paybills require an account number</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <input type="text" class="form-control" id="purpose" name="purpose" required>
                                <div class="form-text">e.g., Tithe, Offering, Building Fund</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="description" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="1"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Add Paybill
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Existing Paybills -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Existing Paybills</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($paybills)): ?>
                        <p class="text-muted">No paybills have been added yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Paybill Number</th>
                                        <th>Account</th>
                                        <th>Purpose</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paybills as $paybill): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($paybill['paybill_number']); ?></td>
                                            <td><?php echo htmlspecialchars($paybill['account'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($paybill['purpose']); ?></td>
                                            <td><?php echo htmlspecialchars($paybill['description'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($paybill['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#editModal<?php echo $paybill['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $paybill['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $paybill['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Paybill</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="post">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                            <input type="hidden" name="update_paybill" value="1">
                                                            <input type="hidden" name="paybill_id" value="<?php echo $paybill['id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="paybill_number_<?php echo $paybill['id']; ?>" class="form-label">Paybill Number</label>
                                                                <input type="text" class="form-control" id="paybill_number_<?php echo $paybill['id']; ?>" 
                                                                       name="paybill_number" value="<?php echo htmlspecialchars($paybill['paybill_number']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="account_<?php echo $paybill['id']; ?>" class="form-label">Account (Optional)</label>
                                                                <input type="text" class="form-control" id="account_<?php echo $paybill['id']; ?>" 
                                                                       name="account" value="<?php echo htmlspecialchars($paybill['account'] ?? ''); ?>">
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="purpose_<?php echo $paybill['id']; ?>" class="form-label">Purpose</label>
                                                                <input type="text" class="form-control" id="purpose_<?php echo $paybill['id']; ?>" 
                                                                       name="purpose" value="<?php echo htmlspecialchars($paybill['purpose']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="description_<?php echo $paybill['id']; ?>" class="form-label">Description (Optional)</label>
                                                                <textarea class="form-control" id="description_<?php echo $paybill['id']; ?>" 
                                                                          name="description" rows="2"><?php echo htmlspecialchars($paybill['description'] ?? ''); ?></textarea>
                                                            </div>
                                                            
                                                            <div class="mb-3 form-check">
                                                                <input type="checkbox" class="form-check-input" id="is_active_<?php echo $paybill['id']; ?>" 
                                                                       name="is_active" <?php echo $paybill['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="is_active_<?php echo $paybill['id']; ?>">
                                                                    Active
                                                                </label>
                                                            </div>
                                                            
                                                            <div class="d-grid">
                                                                <button type="submit" class="btn btn-primary">Update Paybill</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $paybill['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete the paybill <strong><?php echo htmlspecialchars($paybill['purpose']); ?></strong>?</p>
                                                        <p class="text-danger">This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                            <input type="hidden" name="delete_paybill" value="1">
                                                            <input type="hidden" name="paybill_id" value="<?php echo $paybill['id']; ?>">
                                                            <button type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../../../includes/footer.php';
?>