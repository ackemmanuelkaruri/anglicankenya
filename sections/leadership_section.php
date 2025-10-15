<?php
/**
 * leadership_section.php
 * Displays and manages leadership roles for the user
 */
// Include form data
require_once '../includes/form_data.php';
// Debug: Check if user data is available
if (!isset($user) || empty($user['id'])) {
    echo '<div class="error-message">User data not available</div>';
    return;
}
// Fetch user's current leadership roles from database
$userLeadership = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM user_leadership 
        WHERE user_id = ? 
        ORDER BY from_date DESC
    ");
    $stmt->execute([$user['id']]);
    $userLeadership = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading leadership roles: " . $e->getMessage());
    $userLeadership = [];
}
?>
<!-- Leadership Details Section -->
<div id="leadership" class="section leadership-section tab-content" style="display: none;">
    <h3>Leadership Details</h3>
    <p>Please provide details about your leadership roles in the church.</p>
    
    <!-- Leadership Summary Section -->
    <?php if (!empty($userLeadership)): ?>
    <div class="leadership-summary">
        <h4>Your Leadership Roles</h4>
        <div class="leadership-cards">
            <?php foreach ($userLeadership as $role): ?>
                <div class="leadership-card">
                    <div class="card-header">
                        <h5><?= htmlspecialchars($role['role'] === 'OTHER' ? $role['other_role'] : ($leadershipRoles[$role['role']] ?? $role['role'])) ?></h5>
                        <?php if ($role['is_current']): ?>
                            <span class="current-badge">Current</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($role['leadership_type'] === 'department' && !empty($role['department'])): ?>
                            <div class="role-detail">
                                <span class="detail-label">Department:</span>
                                <span class="detail-value"><?= htmlspecialchars($departments[$role['department']] ?? $role['department']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($role['leadership_type'] === 'ministry' && !empty($role['ministry'])): ?>
                            <div class="role-detail">
                                <span class="detail-label">Ministry:</span>
                                <span class="detail-value"><?= htmlspecialchars($ministries[$role['ministry']] ?? $role['ministry']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="role-detail">
                            <span class="detail-label">Period:</span>
                            <span class="detail-value">
                                <?= date('M Y', strtotime($role['from_date'])) ?> - 
                                <?= $role['is_current'] ? 'Present' : date('M Y', strtotime($role['to_date'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="edit-toggle">
<button type="button" class="btn-edit-leadership">
                <span class="edit-icon">✏️</span> Edit Leadership Roles
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Leadership Form Section -->
    <div id="leadership-form-container" class="<?= !empty($userLeadership) ? 'hidden' : '' ?>">
        <div id="leadership-roles-container">
            <?php if (empty($userLeadership)): ?>
                <!-- Initial empty form -->
                <div class="leadership-role" data-role-id="1">
                    <h4>Leadership Role <span class="role-number">1</span></h4>
                    <div class="form-group">
                        <label for="leadership_type_1">Department/Ministry</label>
<select name="leadership_type[]" id="leadership_type_1" class="leadership-type" required>
                            <option value="">--Select Type--</option>
                            <option value="department">Department</option>
                            <option value="ministry">Ministry</option>
                        </select>
                    </div>
                    
                    <div class="form-group department-options" style="display: none;">
                        <label for="leadership_department_1">Department</label>
                        <select name="leadership_department[]" id="leadership_department_1" class="leadership-department">
                            <option value="">--Select Department--</option>
                            <?php foreach ($departments as $value => $label): ?>
                                <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group ministry-options" style="display: none;">
                        <label for="leadership_ministry_1">Ministry</label>
                        <select name="leadership_ministry[]" id="leadership_ministry_1" class="leadership-ministry">
                            <option value="">--Select Ministry--</option>
                            <?php foreach ($ministries as $value => $label): ?>
                                <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="leadership_role_1">Leadership Role</label>
<select name="leadership_role[]" id="leadership_role_1" class="leadership-role-select" required>
                            <option value="">--Select Role--</option>
                            <?php foreach ($leadershipRoles as $value => $label): ?>
                                <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group other-role-field" style="display: none;">
                        <label for="other_leadership_role_1">Specify Other Role</label>
                        <input type="text" name="other_leadership_role[]" id="other_leadership_role_1" class="other-leadership-role" placeholder="Enter your leadership role">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="leadership_from_date_1">From Date</label>
                            <input type="date" name="leadership_from_date[]" id="leadership_from_date_1" class="leadership-from-date" required>
                        </div>
                        <div class="form-group">
                            <label for="leadership_to_date_1">To Date</label>
                            <input type="date" name="leadership_to_date[]" id="leadership_to_date_1" class="leadership-to-date">
                            <div class="checkbox-label">
<input type="checkbox" id="is_current_leadership_1" class="is-current-leadership">
                                <span class="checkmark"></span>
                                <span>Current Position</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
<button type="button" class="btn-remove-role" style="display: none;">Remove</button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Populate with existing data -->
                <?php foreach ($userLeadership as $index => $role): ?>
                    <div class="leadership-role" data-role-id="<?= $index + 1 ?>">
                        <h4>Leadership Role <span class="role-number"><?= $index + 1 ?></span></h4>
                        <div class="form-group">
                            <label for="leadership_type_<?= $index + 1 ?>">Department/Ministry</label>
<select name="leadership_type[]" id="leadership_type_<?= $index + 1 ?>" class="leadership-type" required>
                                <option value="">--Select Type--</option>
                                <option value="department" <?= $role['leadership_type'] === 'department' ? 'selected' : '' ?>>Department</option>
                                <option value="ministry" <?= $role['leadership_type'] === 'ministry' ? 'selected' : '' ?>>Ministry</option>
                            </select>
                        </div>
                        
                        <div class="form-group department-options" <?= $role['leadership_type'] === 'department' ? '' : 'style="display: none;"' ?>>
                            <label for="leadership_department_<?= $index + 1 ?>">Department</label>
                            <select name="leadership_department[]" id="leadership_department_<?= $index + 1 ?>" class="leadership-department">
                                <option value="">--Select Department--</option>
                                <?php foreach ($departments as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $role['department'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group ministry-options" <?= $role['leadership_type'] === 'ministry' ? '' : 'style="display: none;"' ?>>
                            <label for="leadership_ministry_<?= $index + 1 ?>">Ministry</label>
                            <select name="leadership_ministry[]" id="leadership_ministry_<?= $index + 1 ?>" class="leadership-ministry">
                                <option value="">--Select Ministry--</option>
                                <?php foreach ($ministries as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $role['ministry'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="leadership_role_<?= $index + 1 ?>">Leadership Role</label>
<select name="leadership_role[]" id="leadership_role_<?= $index + 1 ?>" class="leadership-role-select" required>
                                <option value="">--Select Role--</option>
                                <?php foreach ($leadershipRoles as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $role['role'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group other-role-field" <?= $role['role'] === 'OTHER' ? '' : 'style="display: none;"' ?>>
                            <label for="other_leadership_role_<?= $index + 1 ?>">Specify Other Role</label>
                            <input type="text" name="other_leadership_role[]" id="other_leadership_role_<?= $index + 1 ?>" class="other-leadership-role" placeholder="Enter your leadership role" value="<?= htmlspecialchars($role['other_role'] ?? '') ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="leadership_from_date_<?= $index + 1 ?>">From Date</label>
                                <input type="date" name="leadership_from_date[]" id="leadership_from_date_<?= $index + 1 ?>" class="leadership-from-date" value="<?= htmlspecialchars($role['from_date']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="leadership_to_date_<?= $index + 1 ?>">To Date</label>
                                <input type="date" name="leadership_to_date[]" id="leadership_to_date_<?= $index + 1 ?>" class="leadership-to-date" value="<?= htmlspecialchars($role['to_date'] ?? '') ?>" <?= $role['is_current'] ? 'disabled' : '' ?>>
                                <div class="checkbox-label">
<input type="checkbox" id="is_current_leadership_<?= $index + 1 ?>" class="is-current-leadership" <?= $role['is_current'] ? 'checked' : '' ?>>
                                    <span class="checkmark"></span>
                                    <span>Current Position</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
<button type="button" class="btn-remove-role">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="leadership-actions">
<button type="button" class="btn-add-role">Add Another Role</button>
            <button type="button" class="btn-save-section" data-section="leadership">Save Leadership Roles</button>
            <?php if (!empty($userLeadership)): ?>
<button type="button" class="btn-cancel-edit">Cancel</button>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="leadership-status"></div>
    
    <!-- Make sure to include the JavaScript file at the end -->
    <script src="../js/leadership-roles.js"></script>
</div>