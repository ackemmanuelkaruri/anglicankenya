<?php
/**
 * employment_section.php
 * Employment History Section - Fetches and displays records.
 */
// Fetch existing employment records
$stmt = $pdo->prepare("SELECT * FROM employment_history WHERE user_id = ? ORDER BY from_date DESC");
$stmt->execute([$user['id']]);
$existingEmployment = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="section employment-section" data-section="employment">
    <h3>Employment Details</h3>
    <p>Please provide details about your current and past employment.</p>
    
    <div id="employment-roles-container">
        <?php if (!empty($existingEmployment)): ?>
            <?php foreach ($existingEmployment as $index => $employment): ?>
                <div class="employment-role" data-role-id="<?= $index + 1 ?>" data-employment-id="<?= $employment['id'] ?>">
                    <h4>Employment Role <span class="role-number"><?= $index + 1 ?></span></h4>
                    
                    <input type="hidden" name="employment_id[]" value="<?= $employment['id'] ?>">
                    
                    <label>Job Title</label>
                    <input type="text" name="job_title[]" class="job-title form-control" value="<?= htmlspecialchars($employment['job_title'] ?? '') ?>" required>
                    
                    <label>Company/Employer</label>
                    <input type="text" name="company[]" class="company form-control" value="<?= htmlspecialchars($employment['company'] ?? '') ?>" required>
                           
                    <div class="date-group">
                        <label>From Date</label>
                        <input type="date" name="employment_from_date[]" class="employment-from-date form-control" value="<?= htmlspecialchars($employment['from_date'] ?? '') ?>" required>
                    </div>
                    
                    <div class="date-group">
                        <label>To Date</label>
                        <input type="date" name="employment_to_date[]" class="employment-to-date form-control" value="<?= htmlspecialchars($employment['to_date'] ?? '') ?>">
                        <small class="form-text text-muted">Leave blank if currently employed.</small>
                    </div>
                    
                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_current_employment_checkbox_<?= $index ?>" class="is-current-employment-checkbox form-check-input" id="is_current_<?= $index ?>" data-index="<?= $index ?>" <?= ($employment['is_current'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_current_<?= $index ?>">
                            Currently Employed Here
                        </label>
                        <input type="hidden" name="is_current_employment[]" value="<?= ($employment['is_current'] ?? 0) ?>" class="is-current-employment-hidden">
                    </div>
                    <button type="button" class="btn btn-danger btn-sm mt-3 btn-remove-employment-role">
                        Remove Role
                    </button>
                    <hr>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" id="add-employment-btn" class="btn btn-secondary mt-3 mb-4">
        <i class="fas fa-plus"></i> Add New Employment Role
    </button>
    
    <div id="employment-save-status" class="mt-3"></div>
    <button type="button" class="btn btn-primary section-save-btn" data-section="employment">
        <i class="fas fa-save"></i> Save Employment Details
    </button>
</div>
<script src="../employment/employment_section.js"></script>