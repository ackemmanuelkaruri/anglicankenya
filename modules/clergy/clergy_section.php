<?php
/**
 * clergy_section.php
 * Displays and manages clergy/laity roles for the user
 */

// Fetch existing clergy roles
$stmt = $pdo->prepare("SELECT * FROM clergy_roles WHERE user_id = ? ORDER BY serving_from_date DESC");
$stmt->execute([$user['id']]);
$existing_clergy_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Role name mapping (MUST match handler for consistency)
$roleNames = [
    '1' => 'Vicar', '2' => 'Curate Vicar', '3' => 'Lay Reader',
    '4' => 'Evangelist', '5' => 'Church Warden', '6' => 'Deacon'
];

foreach ($existing_clergy_roles as &$role) {
    $role['display_role_name'] = $roleNames[$role['role_id']] ?? $role['role_name'] ?? 'N/A';
}
$has_clergy_roles = !empty($existing_clergy_roles);
?>

<div class="section clergy-section" data-section="clergy">
    <h3>Clergy & Leadership Roles</h3>
    <p>Please enter all roles you have served or are currently serving in the clergy/laity.</p>
    
    <div id="clergy-management-container">
        
        <div class="clergy-role-form card p-3 mb-4" id="clergy-role-form">
            <h4><span id="role-form-title">Add New Role</span></h4>
            <input type="hidden" id="clergy_id" name="clergy_id" value="">
            
            <div class="form-group mb-3">
                <label for="role_id">Role Type</label>
                <select id="role_id" name="role_id" class="form-select form-control" required>
                    <option value="">Select Role</option>
                    <option value="1">Vicar</option>
                    <option value="2">Curate Vicar</option>
                    <option value="3">Lay Reader</option>
                    <option value="4">Evangelist</option>
                    <option value="5">Church Warden</option>
                    <option value="6">Deacon</option>
                    <option value="99">Other (Specify Below)</option>
                </select>
            </div>
            
            <div class="form-group mb-3" id="other_role_name_group" style="display: none;">
                <label for="role_name">Specify Other Role Name</label>
                <input type="text" id="role_name" name="role_name" class="form-control">
            </div>
            
            <div class="date-range-group row">
                <div class="form-group col-md-6 mb-3">
                    <label for="serving_from_date">Serving From</label>
                    <input type="date" id="serving_from_date" name="serving_from_date" class="form-control" required>
                </div>
                
                <div class="form-group col-md-6 mb-3">
                    <label for="serving_to_date">Serving To</label>
                    <input type="date" id="serving_to_date" name="serving_to_date" class="form-control">
                    <small class="form-hint">Leave blank if currently serving</small>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label class="checkbox-label form-check">
                    <input type="checkbox" id="is_current" name="is_current" value="1" class="form-check-input">
                    <span class="checkmark"></span>
                    Currently serving in this role
                </label>
            </div>
            
            <div class="form-buttons">
                <button type="button" id="save_clergy_role_btn" class="btn btn-success">Save Role</button>
                <button type="button" id="cancel_clergy_role_btn" class="btn btn-secondary">Cancel</button>
            </div>
            
            <div id="clergy-save-status-role" class="mt-2"></div>
        </div>
        
        <hr class="my-4">
        
        <h4 class="mt-4">Existing Roles</h4>
        <div id="existing_clergy_roles" class="roles-list">
            <?php if (!empty($existing_clergy_roles)): ?>
                <?php foreach ($existing_clergy_roles as $role): ?>
                    <div class="role-item p-3 mb-2 border rounded" data-id="<?= $role['id'] ?>">
                        <strong><?= htmlspecialchars($role['display_role_name']) ?></strong> 
                        (<?= date('Y/m/d', strtotime($role['serving_from_date'])) ?> - <?= $role['serving_to_date'] ? date('Y/m/d', strtotime($role['serving_to_date'])) : 'Current' ?>)
                        <div class="float-end">
                            <button type="button" class="btn btn-sm btn-info edit-clergy-role" data-id="<?= $role['id'] ?>">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger delete-clergy-role" data-id="<?= $role['id'] ?>">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p id="no-roles-message">No clergy roles recorded.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="clergy-save-status" class="mt-3"></div>

    <button type="button" class="btn btn-primary section-save-btn" data-section="clergy">
        <i class="fas fa-save"></i> Save Clergy Section
    </button>
</div>
<script src="../clergy/clergy_section.js"></script>