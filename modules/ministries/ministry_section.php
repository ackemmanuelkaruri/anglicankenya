<?php
/**
 * ministry_section.php
 * Displays and manages ministry/department involvement for the user
 */

// Enable errors for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start(); // Prevent any stray output breaking JS

// =======================
// INCLUDES
// =======================
require_once '../../includes/form_data.php';
require_once '../../db.php';
require_once '../../includes/init.php';

// =======================
// CHECK USER
// =======================
if (!isset($user) || empty($user['id'])) {
    echo '<div class="error-message">User data not available</div>';
    return;
}

// =======================
// SAFE DEFAULTS
// =======================
$departments = $departments ?? [];
$ministries = $ministries ?? [];

$userDepartments = [];
$userMinistries = [];

try {
    // Fetch user's departments
    $stmt = $pdo->prepare("
        SELECT ministry_department_name as name 
        FROM ministries
        WHERE user_id = ? AND assignment_type = 'DEPARTMENT'
        ORDER BY ministry_department_name
    ");
    $stmt->execute([$user['id']]);
    $userDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user's ministries
    $stmt = $pdo->prepare("
        SELECT ministry_department_name as name 
        FROM ministries
        WHERE user_id = ? AND assignment_type = 'MINISTRY'
        ORDER BY ministry_department_name
    ");
    $stmt->execute([$user['id']]);
    $userMinistries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading ministry involvement: " . $e->getMessage());
    $userDepartments = [];
    $userMinistries = [];
}

// Prepare arrays of names for display & JS
$userDepartmentNames = array_column($userDepartments, 'name');
$userMinistryNames = array_column($userMinistries, 'name');
?>

<!-- Ministry Details Section -->
<div id="ministry" class="section ministry-section" role="tabpanel">
    <h3>Ministry Details</h3>
    
    <!-- Current Assignments Display -->
    <div class="current-assignments">
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

    <!-- Form to select Ministries/Departments -->
    <form id="ministry-form">
        <div class="ministry-container">
            <div class="ministry-column">
                <h4>Church Department</h4>
                <p class="selection-limit">Select your departments (multiple allowed)</p>
                <div class="checkbox-group" id="department-options">
                    <?php foreach ($departments as $value => $label): ?>
                        <div class="checkbox-option">
                            <input type="checkbox" id="dept_<?= $value ?>"
                                   name="departments[]" value="<?= $value ?>"
                                   class="department-checkbox"
                                   <?= in_array($value, $userDepartmentNames) ? 'checked' : '' ?>>
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
                                   class="ministry-checkbox"
                                   <?= in_array($value, $userMinistryNames) ? 'checked' : '' ?>>
                            <label for="ministry_<?= $value ?>"><?= htmlspecialchars($label) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="error-message" id="ministry-error"></div>
            </div>
        </div>

        <div class="ministry-actions">
            <button type="button" class="btn-save-section" data-section="ministry">
                <i class="fas fa-save"></i> Save Ministry
            </button>
            <button type="button" class="btn-clear-selections" id="clear-ministry-selections">
                <i class="fas fa-eraser"></i> Clear Selections
            </button>
            <button type="button" class="btn-delete-section" data-section="ministry">
                <i class="fas fa-trash"></i> Delete All
            </button>
        </div>
    </form>

    <div id="ministry-status"></div>
</div>

<!-- Ministry data for JS -->
<div id="ministry-data"
     data-user-ministry='<?= htmlspecialchars(json_encode(["departments" => $userDepartmentNames, "ministries" => $userMinistryNames]), ENT_QUOTES, "UTF-8") ?>'
     data-department-options='<?= htmlspecialchars(json_encode($departments), ENT_QUOTES, "UTF-8") ?>'
     data-ministry-options='<?= htmlspecialchars(json_encode($ministries), ENT_QUOTES, "UTF-8") ?>'>
</div>

<script src="../ministries/ministry_section.js"></script>