<?php
// Fetch existing employment history for this user
$existingEmployment = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM employment_history WHERE user_id = ? ORDER BY from_date DESC");
    $stmt->execute([$user['id']]);
    $existingEmployment = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Log error but don't break the page
    error_log("Error fetching employment history: " . $e->getMessage());
}
?>

<!-- Employment Details Section -->
<div class="section employment-section tab-content" id="employment" style="display: none;">
    <h3>Employment Details</h3>
    <p>Please provide details about your current and past employment.</p>
    
    <div id="employment-roles-container">
        <?php if (!empty($existingEmployment)): ?>
            <?php foreach ($existingEmployment as $index => $employment): ?>
                <div class="employment-role" data-role-id="<?= $index + 1 ?>" data-employment-id="<?= $employment['id'] ?>">
                    <h4>Employment Role <span class="role-number"><?= $index + 1 ?></span></h4>
                    
                    <!-- Hidden field to store the employment ID for existing records -->
                    <input type="hidden" name="employment_id[]" value="<?= $employment['id'] ?>">
                    
                    <label>Job Title</label>
                    <input type="text" 
                           name="job_title[]" 
                           class="job-title" 
                           placeholder="Enter your job title" 
                           value="<?= htmlspecialchars($employment['job_title'] ?? '') ?>" 
                           required>
                    
                    <label>Company/Organization</label>
                    <input type="text" 
                           name="company[]" 
                           class="company" 
                           placeholder="Enter company or organization name" 
                           value="<?= htmlspecialchars($employment['company'] ?? '') ?>" 
                           required>
                    
                    <label>Employment Period</label>
                    <div class="date-range">
                        <div>
                            <div class="year-label">From</div>
                            <input type="date" 
                                   name="employment_from_date[]" 
                                   class="employment-from-date"
                                   value="<?= htmlspecialchars($employment['from_date'] ?? '') ?>">
                        </div>
                        <span>to</span>
                        <div>
                            <div class="year-label">To</div>
                            <input type="date" 
                                   name="employment_to_date[]" 
                                   class="employment-to-date"
                                   value="<?= htmlspecialchars($employment['to_date'] ?? '') ?>"
                                   <?= $employment['is_current'] ? 'disabled' : '' ?>>
                        </div>
                        <div class="current-checkbox">
                            <input type="checkbox" 
                                   id="is_current_employment_<?= $index + 1 ?>" 
                                   name="is_current_employment[]" 
                                   value="<?= $index ?>" 
                                   class="is-current-employment" 
                                   <?= $employment['is_current'] ? 'checked' : '' ?>>
                            <label for="is_current_employment_<?= $index + 1 ?>">Current</label>
                        </div>
                    </div>
                    
                    <!-- FIXED DELETE BUTTON - This will NOT submit the form -->
<button type="button" 
                            class="btn-remove-employment btn-delete-from-db" 
                            data-employment-id="<?= $employment['id'] ?>"
                            data-job-title='<?= htmlspecialchars(addslashes($employment['job_title'])) ?>'
                            data-company='<?= htmlspecialchars(addslashes($employment['company'])) ?>'
                            style="background-color: #dc3545; color: white; margin-top: 10px; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                        🗑️ Delete This Role
                    </button>
                    <hr>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Default empty form if no existing employment -->
            <div class="employment-role" data-role-id="1" data-employment-id="new">
                <h4>Employment Role <span class="role-number">1</span></h4>
                
                <!-- No employment ID for new records -->
                <input type="hidden" name="employment_id[]" value="">
                
                <label>Job Title</label>
                <input type="text" name="job_title[]" class="job-title" placeholder="Enter your job title" required>
                
                <label>Company/Organization</label>
                <input type="text" name="company[]" class="company" placeholder="Enter company or organization name" required>
                
                <label>Employment Period</label>
                <div class="date-range">
                    <div>
                        <div class="year-label">From</div>
                        <input type="date" name="employment_from_date[]" class="employment-from-date">
                    </div>
                    <span>to</span>
                    <div>
                        <div class="year-label">To</div>
                        <input type="date" name="employment_to_date[]" class="employment-to-date">
                    </div>
                    <div class="current-checkbox">
                        <input type="checkbox" 
                               id="is_current_employment_1" 
                               name="is_current_employment[]" 
                               value="0" 
                               class="is-current-employment">
                        <label for="is_current_employment_1">Current</label>
                    </div>
                </div>
                
                <!-- Remove button for new records (frontend only) -->
<button type="button" 
                        class="btn-remove-employment btn-remove-from-form btn-remove-employment-role" 
                        style="display: none; background-color: #6c757d; color: white; margin-top: 10px; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                    ✖️ Remove This Role
                </button>
                <hr>
            </div>
        <?php endif; ?>
    </div>
    
<button type="button" class="btn-add-employment" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 5px;">
        + Add Another Employment Role
    </button>
    <button type="button" class="btn-save-section" data-section="employment" style="background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 5px;">
        💾 Save Employment
    </button>
</div>

<!-- Custom Confirmation Modal -->
<div id="deleteConfirmationModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 10% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <h3 style="color: #dc3545; margin-bottom: 15px; font-size: 24px;">⚠️ Confirm Deletion</h3>
        <p id="deleteConfirmationMessage" style="margin-bottom: 20px; font-size: 16px; line-height: 1.5;"></p>
        <div style="background-color: #fff3cd; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
            <strong>⚠️ Warning:</strong> This action cannot be undone. The employment record will be permanently deleted from the database.
        </div>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button id="confirmDeleteBtn" style="background-color: #dc3545; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
                🗑️ Yes, Delete It
            </button>
            <button id="cancelDeleteBtn" style="background-color: #6c757d; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
                ❌ Cancel
            </button>
        </div>
    </div>
</div>

<!-- Make sure to include your employment-roles.js file -->
