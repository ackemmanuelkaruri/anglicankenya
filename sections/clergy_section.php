<?php
/**
 * clergy_section.php
 * Displays and manages clergy/laity roles for the user
 */
$existing_clergy_roles = [];
if (isset($user) && !empty($user['id'])) {
    try {
        // Check if table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'clergy_roles'");
        if ($tableCheck->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT * FROM clergy_roles WHERE user_id = ? ORDER BY serving_from_date DESC");
            $stmt->execute([$user['id']]);
            $raw_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Role name mapping
            $roleNames = [
                '1' => 'Vicar',
                '2' => 'Curate Vicar',
                '3' => 'Lay Reader',
                '4' => 'Evangelist',
                '5' => 'Church Warden',
                '6' => 'Deacon'
            ];
            
            foreach ($raw_roles as $role) {
                // Normalize the data structure
                $normalized_role = $role;
                
                // Use role name from database if available, otherwise use mapping
                if (!empty($role['role_name'])) {
                    $normalized_role['display_role_name'] = $role['role_name'];
                } else {
                    $normalized_role['display_role_name'] = $roleNames[$role['role_id']] ?? 'Unknown Role';
                }
                
                // Handle is_current
                if (isset($role['is_current'])) {
                    $normalized_role['is_current'] = $role['is_current'] ? 1 : 0;
                } else {
                    // Determine from to date
                    $normalized_role['is_current'] = empty($role['to_date']) ? 1 : 0;
                }
                
                $existing_clergy_roles[] = $normalized_role;
            }
        }
    } catch (Exception $e) {
        error_log("Error loading clergy roles: " . $e->getMessage());
        $existing_clergy_roles = [];
    }
}
// Static available clergy roles
$available_roles = [
    '1' => 'Vicar',
    '2' => 'Curate Vicar',
    '3' => 'Lay Reader',
    '4' => 'Evangelist',
    '5' => 'Church Warden',
    '6' => 'Deacon'
];
$has_clergy_roles = !empty($existing_clergy_roles);
?>
<!-- Clergy data for JS (CSP-safe via data attributes) -->
<div id="clergy-data"
     data-roles='<?= htmlspecialchars(json_encode($existing_clergy_roles), ENT_QUOTES, "UTF-8") ?>'
     data-role-options='<?= htmlspecialchars(json_encode($available_roles), ENT_QUOTES, "UTF-8") ?>'>
</div>
<!-- Clergy/Laity Tab Content -->
<div id="clergy" class="tab-content">
    <div class="clergy-container">
        <h3>Clergy/Laity Information</h3>
        
        <!-- Yes/No Question - Improved Layout -->
        <div class="clergy-yes-no-section">
            <div class="question-header">
                <h4>Do you currently serve or have you served in any clergy or laity roles?</h4>
            </div>
            <div class="radio-options">
                <div class="radio-option">
                    <input type="radio" id="clergy_yes" name="has_clergy_role" value="yes" <?= $has_clergy_roles ? 'checked' : '' ?>>
                    <label for="clergy_yes" class="radio-label">
                        <span class="radio-text">Yes</span>
                    </label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="clergy_no" name="has_clergy_role" value="no" <?= !$has_clergy_roles ? 'checked' : '' ?>>
                    <label for="clergy_no" class="radio-label">
                        <span class="radio-text">No</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Clergy Service Details -->
        <div id="clergy_service_details" class="clergy-details" style="display: <?= $has_clergy_roles ? 'block' : 'none' ?>;">
            
            <!-- Add Role Button -->
            <div class="action-bar">
                <button type="button" id="add_clergy_role_btn" class="btn-add-role">
                    <span class="btn-icon">➕</span> Add New Role
                </button>
            </div>
            
            <!-- Add/Edit Role Form -->
            <div id="clergy_add_role_form" class="clergy-form" style="display: none;">
                <h4>Add/Edit Clergy Role</h4>
                
                <div class="form-group">
                    <label for="role_id">Role *</label>
                    <select id="role_id" name="role_id" required class="form-control">
                        <option value="">-- Select Role --</option>
                        <?php foreach ($available_roles as $id => $name): ?>
                            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Hidden role_name (auto-filled by JS) -->
                <input type="hidden" id="role_name" name="role_name">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="serving_from_date">Serving From *</label>
                        <input type="date" id="serving_from_date" name="serving_from_date" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="serving_to_date">Serving To</label>
                        <input type="date" id="serving_to_date" name="serving_to_date" class="form-control">
                        <small class="form-hint">Leave blank if currently serving</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_current" name="is_current" value="1">
                        <span class="checkmark"></span>
                        Currently serving in this role
                    </label>
                </div>
                
                <div class="form-buttons">
                    <button type="button" id="save_clergy_role_btn" class="btn-save">Save Role</button>
                    <button type="button" id="cancel_clergy_role_btn" class="btn-cancel">Cancel</button>
                </div>
                
                <div id="clergy-save-status"></div>
            </div>
            
            <!-- Existing Roles List -->
            <div id="existing_clergy_roles" class="roles-list" style="display: <?= $has_clergy_roles ? 'block' : 'none' ?>;">
                <!-- Populated dynamically by clergy-handler.js -->
            </div>
        </div>
    </div>
</div>