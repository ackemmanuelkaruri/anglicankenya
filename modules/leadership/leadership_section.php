<?php
/**
 * leadership_section.php
 * Displays and manages leadership roles for the user
 */

// Fetch existing leadership records
$existingLeadership = [];
$dbError = null;

try {
    // Use the actual leadership_roles table
    $stmt = $pdo->prepare("SELECT * FROM leadership_roles WHERE created_by = ? ORDER BY is_active DESC, start_date DESC");
    $stmt->execute([$user['id']]);
    $existingLeadership = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbError = "Database error: " . $e->getMessage();
    error_log("Error fetching leadership records: " . $e->getMessage());
}

// Define available role options
$leadershipRoles = [
    1 => 'Deacon', 
    2 => 'Elder', 
    3 => 'Department Head', 
    4 => 'Ministry Lead', 
    99 => 'Other'
];

$departments = [
    1 => 'Finance', 
    2 => 'Technical', 
    3 => 'Logistics'
];
?>

<div class="section leadership-section" data-section="leadership">
    <div class="section-header mb-4">
        <h3><i class="fas fa-users-cog"></i> Leadership & Ministry Roles</h3>
        <p class="text-muted">Document your leadership history within the church, including start dates, roles, and associated departments.</p>
    </div>
    
    <?php if ($dbError): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <strong>Database Error:</strong> <?= htmlspecialchars($dbError) ?>
        </div>
    <?php endif; ?>
    
    <div id="leadership-roles-container">
        <?php if (!empty($existingLeadership)): ?>
            <?php foreach ($existingLeadership as $index => $role): ?>
                <div class="leadership-role card mb-3 p-3" data-role-id="<?= $index + 1 ?>" data-leadership-id="<?= $role['id'] ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-crown text-warning"></i> 
                            Leadership Assignment <span class="role-number badge bg-secondary"><?= $index + 1 ?></span>
                            <?php if ($role['is_active'] ?? 0): ?>
                                <span class="badge bg-success ms-2">Active</span>
                            <?php endif; ?>
                        </h5>
                        <button type="button" class="btn btn-danger btn-sm btn-remove-leadership-role" data-leadership-id="<?= $role['id'] ?>">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                    
                    <input type="hidden" name="leadership_id[]" value="<?= $role['role_id'] ?>">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="role_name_<?= $index ?>" class="form-label">
                                Role Name <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="role_name_<?= $index ?>"
                                name="role_name[]" 
                                class="form-control" 
                                value="<?= htmlspecialchars($role['role_name'] ?? '') ?>"
                                placeholder="e.g., Youth Ministry Leader"
                                required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="description_<?= $index ?>" class="form-label">Description</label>
                            <textarea 
                                id="description_<?= $index ?>"
                                name="description[]" 
                                class="form-control" 
                                rows="2"
                                placeholder="Brief description of responsibilities"><?= htmlspecialchars($role['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="leadership_from_date_<?= $index ?>" class="form-label">
                                Start Date <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="leadership_from_date_<?= $index ?>"
                                name="start_date[]" 
                                class="form-control" 
                                value="<?= htmlspecialchars($role['start_date'] ?? '') ?>" 
                                max="<?= date('Y-m-d') ?>"
                                required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="leadership_to_date_<?= $index ?>" class="form-label">
                                End Date
                            </label>
                            <input 
                                type="date" 
                                id="leadership_to_date_<?= $index ?>"
                                name="end_date[]" 
                                class="form-control" 
                                value="<?= htmlspecialchars($role['end_date'] ?? '') ?>"
                                max="<?= date('Y-m-d') ?>"
                                <?= ($role['is_active'] ?? 0) ? 'disabled' : '' ?>>
                            <small class="form-text text-muted">Leave blank if currently active</small>
                        </div>
                    </div>
                    
                    <div class="form-check mt-2">
                        <input 
                            type="checkbox" 
                            name="is_active_checkbox_<?= $index ?>" 
                            class="is-active-leadership-checkbox form-check-input" 
                            id="is_active_<?= $index ?>" 
                            data-index="<?= $index ?>" 
                            <?= ($role['is_active'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active_<?= $index ?>">
                            <i class="fas fa-check-circle"></i> Currently Active in This Role
                        </label>
                        <input type="hidden" name="is_active_leadership[]" value="<?= ($role['is_active'] ?? 0) ?>" class="is-active-leadership-hidden">
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info" id="no-leadership-message">
                <i class="fas fa-info-circle"></i> No leadership roles recorded. Click the button below to add your leadership history.
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center my-4">
        <button type="button" id="add-leadership-btn" class="btn btn-outline-primary">
            <i class="fas fa-plus-circle"></i> Add New Leadership Assignment
        </button>
    </div>
    
    <div id="leadership-save-status" class="mt-3"></div>

    <div class="section-footer mt-4 pt-3 border-top">
        <button type="button" class="btn btn-success section-save-btn" data-section="leadership">
            <i class="fas fa-save"></i> Save Leadership Details
        </button>
        <button type="button" class="btn btn-secondary ms-2" onclick="location.reload()">
            <i class="fas fa-undo"></i> Reset Changes
        </button>
    </div>
</div>

<script src="../leadership/leadership_section.js"></script>