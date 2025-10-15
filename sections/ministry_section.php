<?php
/**
 * ministry_section.php
 * Displays and manages ministry/department involvement for the user
 */
// Include form data
require_once '../includes/form_data.php';
// Debug: Check if user data is available
if (!isset($user) || empty($user['id'])) {
    echo '<div class="error-message">User data not available</div>';
    return;
}
// Fetch user's current ministry involvement from database
$userDepartments = [];
$userMinistries = [];
try {
    // Get user's departments
    $stmt = $pdo->prepare("
        SELECT department_name as name 
        FROM user_ministry_department
        WHERE user_id = ? AND department_name IS NOT NULL
        ORDER BY department_name
    ");
    $stmt->execute([$user['id']]);
    $userDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's ministries
    $stmt = $pdo->prepare("
        SELECT ministry_name as name 
        FROM user_ministry_department
        WHERE user_id = ? AND ministry_name IS NOT NULL
        ORDER BY ministry_name
    ");
    $stmt->execute([$user['id']]);
    $userMinistries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading ministry involvement: " . $e->getMessage());
    $userDepartments = [];
    $userMinistries = [];
}
// Prepare user department and ministry names for checking (not IDs)
$userDepartmentNames = array_column($userDepartments, 'name');
$userMinistryNames = array_column($userMinistries, 'name');
// Debug output
error_log("User Departments: " . print_r($userDepartmentNames, true));
error_log("User Ministries: " . print_r($userMinistryNames, true));
?>
<!-- Ministry Details Section -->
<div id="ministry" class="section ministry-section tab-content" style="display: none;">
    <h3>Ministry Details</h3>
    
    <!-- Current Assignments Display -->
    <div class="current-assignments" id="current-assignments">
        <div class="assignment-display">
            <h4>Your Current Assignments</h4>
            <div class="assignments-grid">
                <div class="assignment-column">
                    <h5>Departments</h5>
                    <div id="current-departments" class="assignment-list">
                        <?php if (!empty($userDepartmentNames)): ?>
                            <?php foreach ($userDepartmentNames as $deptName): ?>
                                <span class="assignment-badge dept-badge"><?= htmlspecialchars($deptName) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="no-assignment">No departments assigned</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="assignment-column">
                    <h5>Ministries</h5>
                    <div id="current-ministries" class="assignment-list">
                        <?php if (!empty($userMinistryNames)): ?>
                            <?php foreach ($userMinistryNames as $ministryName): ?>
                                <span class="assignment-badge ministry-badge"><?= htmlspecialchars($ministryName) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="no-assignment">No ministries assigned</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="ministry-container">
        <div class="ministry-column">
            <h4>Church Department</h4>
            <p class="selection-limit">Select your departments (multiple allowed)</p>
            <div class="checkbox-group" id="department-options">
                <?php foreach ($departments as $value => $label): ?>
                    <div class="checkbox-option">
                        <input type="checkbox" id="dept_<?= $value ?>"
                               name="departments[]" value="<?= $value ?>"
                               class="department-checkbox" <?= in_array($value, $userDepartmentNames) ? 'checked' : '' ?>>
                        <label for="dept_<?= $value ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="error-message" id="department-error"></div>
        </div>
        <div class="ministry-column">
            <h4>Ministry/Committee</h4>
            <p class="selection-limit">Select your ministries (multiple allowed)</p>
            <div class="checkbox-group" id="ministry-options">
                <?php foreach ($ministries as $value => $label): ?>
                    <div class="checkbox-option">
                        <input type="checkbox" id="ministry_<?= $value ?>"
                               name="ministries[]" value="<?= $value ?>"
                               class="ministry-checkbox" <?= in_array($value, $userMinistryNames) ? 'checked' : '' ?>>
                        <label for="ministry_<?= $value ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="error-message" id="ministry-error"></div>
        </div>
    </div>
    <div class="ministry-actions">
        <button type="button" class="btn-save-section" data-section="ministry">Save Ministry</button>
        <button type="button" class="btn-delete-section" data-section="ministry">Delete All</button>
    </div>
    <div id="ministry-status"></div>
</div>

<!-- Ministry data for JS (CSP-safe via data attributes) -->
<div id="ministry-data"
     data-user-ministry='<?= htmlspecialchars(json_encode(["departments" => $userDepartmentNames, "ministries" => $userMinistryNames]), ENT_QUOTES, "UTF-8") ?>'
     data-department-options='<?= htmlspecialchars(json_encode($departments), ENT_QUOTES, "UTF-8") ?>'
     data-ministry-options='<?= htmlspecialchars(json_encode($ministries), ENT_QUOTES, "UTF-8") ?>'>
</div>
