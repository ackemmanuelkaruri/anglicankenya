<?php
/**
 * church_section.php
 * Church Details Section - Fetches and displays church-related data
 */

// Fetch church-specific data for this user if not already loaded
if (!isset($memberGroups) || !isset($sacramentRecords)) {
    try {
        // Fetch member groups data
        $groupsStmt = $pdo->prepare("SELECT * FROM member_groups WHERE user_id = ?");
        $groupsStmt->execute([$user['id']]);
        $memberGroups = $groupsStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$memberGroups) {
            $memberGroups = [
                'service_attending' => '',
                'kikuyu_cell_group' => '',
                'english_service_team' => '',
                'family_group' => ''
            ];
        }
        
        // Fetch sacrament records
        $sacramentStmt = $pdo->prepare("SELECT * FROM sacrament_records WHERE user_id = ?");
        $sacramentStmt->execute([$user['id']]);
        $sacramentRecords = $sacramentStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sacramentRecords) {
            $sacramentRecords = [
                'baptized' => '',
                'confirmed' => '',
                'baptism_interest' => '',
                'confirmation_interest' => '',
                'baptism_certificate' => '',
                'confirmation_certificate' => '',
                'want_to_be_baptized' => '',
                'want_to_be_confirmed' => ''
            ];
        }
        
        // Merge into $user array for easy access
        $user = array_merge($user, $memberGroups, $sacramentRecords);
        
        
    } catch (Exception $e) {
        error_log("Error loading church data: " . $e->getMessage());
        // Set empty defaults if query fails
        $memberGroups = [];
        $sacramentRecords = [];
    }
}
?>

<!-- Church Details Section -->
<div id="church" class="church-section">
    <h3>Church Details</h3>
    
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">

    <!-- Service Attending -->
    <div class="mb-3">
        <label for="service_attending" class="form-label">Service Attending <span class="text-danger">*</span></label>
        <select class="form-select" id="service_attending" name="service_attending" required>
            <option value="">--Select--</option>
            <option value="english" <?= (isset($user['service_attending']) && $user['service_attending'] == 'english') ? 'selected' : '' ?>>English Service</option>
            <option value="kikuyu" <?= (isset($user['service_attending']) && $user['service_attending'] == 'kikuyu') ? 'selected' : '' ?>>Kikuyu Service</option>
            <option value="teens" <?= (isset($user['service_attending']) && $user['service_attending'] == 'teens') ? 'selected' : '' ?>>Teens Service</option>
            <option value="sunday_school" <?= (isset($user['service_attending']) && $user['service_attending'] == 'sunday_school') ? 'selected' : '' ?>>Sunday School</option>
        </select>
    </div>

    <!-- English Service Teams -->
    <div id="english_service_team_section" class="mb-3" style="display: none;">
        <label for="english_service_team" class="form-label">English Service Team</label>
        <select class="form-select" name="english_service_team" id="english_service_team">
            <option value="">--Select--</option>
            <?php
            $englishTeams = ['ANTIOCH', 'BASHAN', 'CANAAN', 'SHILOH'];
            foreach ($englishTeams as $team) {
                echo '<option value="' . $team . '"' .
                    (isset($user['english_service_team']) && $user['english_service_team'] == $team ? ' selected' : '') .
                    '>' . $team . '</option>';
            }
            ?>
        </select>
    </div>

    <!-- Kikuyu Cell Group -->
    <div id="kikuyu_cell_group_section" class="mb-3" style="display: none;">
        <label for="kikuyu_cell_group" class="form-label">Kikuyu Cell Group</label>
        <select class="form-select" name="kikuyu_cell_group" id="kikuyu_cell_group">
            <option value="">--Select--</option>
            <?php
            $kikuyuCellGroups = ['GACHORUE', 'MOMBASA', 'POSTAA', 'POSTA B', 'KAMBARA', 'GITHIRIA'];
            foreach ($kikuyuCellGroups as $group) {
                echo '<option value="' . $group . '"' .
                    (isset($user['kikuyu_cell_group']) && $user['kikuyu_cell_group'] == $group ? ' selected' : '') .
                    '>' . $group . '</option>';
            }
            ?>
        </select>
    </div>

    <!-- Family Group -->
    <div id="family_group_section" class="mb-3" style="display: none;">
        <label for="family_group" class="form-label">Family Group</label>
        <select class="form-select" name="family_group" id="family_group">
            <option value="">--Select Family Group--</option>
            <?php if (!empty($user['family_group'])): ?>
                <option value="<?= htmlspecialchars($user['family_group']) ?>" selected><?= htmlspecialchars($user['family_group']) ?></option>
            <?php endif; ?>
        </select>
    </div>

    <!-- Baptism -->
    <div class="mb-3">
        <label class="form-label">Are you baptized? <span class="text-danger">*</span></label>
        <div class="form-check">
           <input class="form-check-input" type="radio" name="baptized" id="baptized_yes" value="yes" <?= (isset($user['baptized']) && strtolower($user['baptized']) == 'yes') ? 'checked' : '' ?> required>
<label class="form-check-label" for="baptized_yes">Yes</label>
</div>
<div class="form-check">
<input class="form-check-input" type="radio" name="baptized" id="baptized_no" value="no" <?= (isset($user['baptized']) && strtolower($user['baptized']) == 'no') ? 'checked' : '' ?>>
            <label class="form-check-label" for="baptized_no">No</label>
        </div>
    </div>

    <!-- Baptism Certificate -->
    <div id="baptism_certificate_section" class="mb-3" style="display: none;">
        <label for="baptism_certificate" class="form-label">Baptism Certificate</label>
        <?php if (!empty($user['baptism_certificate'])): ?>
            <div class="mb-2">
                <p>Current Certificate: 
                    <a href="../uploads/certificates/<?= htmlspecialchars(basename($user['baptism_certificate'])) ?>" target="_blank">View Current Certificate</a>
                </p>
                <small>Current file: <?= htmlspecialchars(basename($user['baptism_certificate'])) ?></small>
            </div>
        <?php endif; ?>
        <input type="file" class="form-control" name="baptism_certificate" accept="image/*,application/pdf">
        <small class="form-text text-muted">Upload a new baptism certificate (optional). Current file will be replaced if you upload a new one.</small>
    </div>

    <!-- Baptism Interest -->
    <div id="baptism_interest_section" class="mb-3" style="display: none;">
        <div class="alert alert-info">Baptism is an important sacrament in our church. If you're interested in being baptized, please let us know.</div>
        <label class="form-label">Are you interested in being baptized?</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="baptism_interest" id="baptism_interest_yes" value="yes" <?= (isset($user['baptism_interest']) && $user['baptism_interest'] == 'interested') ? 'checked' : '' ?>>
            <label class="form-check-label" for="baptism_interest_yes">Yes</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="baptism_interest" id="baptism_interest_no" value="no" <?= (isset($user['baptism_interest']) && $user['baptism_interest'] == 'not_interested') ? 'checked' : '' ?>>
            <label class="form-check-label" for="baptism_interest_no">No</label>
        </div>
    </div>

    <!-- Confirmation -->
    <div class="mb-3">
        <label class="form-label">Are you confirmed? <span class="text-danger">*</span></label>
        <div class="form-check">
           <input class="form-check-input" type="radio" name="confirmed" id="confirmed_yes" value="yes" <?= (isset($user['confirmed']) && strtolower($user['confirmed']) == 'yes') ? 'checked' : '' ?> required>
<label class="form-check-label" for="confirmed_yes">Yes</label>
</div>
<div class="form-check">
<input class="form-check-input" type="radio" name="confirmed" id="confirmed_no" value="no" <?= (isset($user['confirmed']) && strtolower($user['confirmed']) == 'no') ? 'checked' : '' ?>>
            <label class="form-check-label" for="confirmed_no">No</label>
        </div>
    </div>

    <!-- Confirmation Certificate -->
    <div id="confirmation_certificate_section" class="mb-3" style="display: none;">
        <label for="confirmation_certificate" class="form-label">Confirmation Certificate</label>
        <?php if (!empty($user['confirmation_certificate'])): ?>
            <div class="mb-2">
                <p>Current Certificate: 
                    <a href="../uploads/certificates/<?= htmlspecialchars(basename($user['confirmation_certificate'])) ?>" target="_blank">View Current Certificate</a>
                </p>
                <small>Current file: <?= htmlspecialchars(basename($user['confirmation_certificate'])) ?></small>
            </div>
        <?php endif; ?>
        <input type="file" class="form-control" name="confirmation_certificate" accept="image/*,application/pdf">
        <small class="form-text text-muted">Upload a new confirmation certificate (optional). Current file will be replaced if you upload a new one.</small>
    </div>

    <!-- Confirmation Interest -->
    <div id="confirmation_interest_section" class="mb-3" style="display: none;">
        <div class="alert alert-info">Confirmation is an important step in your faith journey. If you're interested in being confirmed, please let us know.</div>
        <label class="form-label">Are you interested in being confirmed?</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="confirmation_interest" id="confirmation_interest_yes" value="yes" <?= (isset($user['confirmation_interest']) && $user['confirmation_interest'] == 'interested') ? 'checked' : '' ?>>
            <label class="form-check-label" for="confirmation_interest_yes">Yes</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="confirmation_interest" id="confirmation_interest_no" value="no" <?= (isset($user['confirmation_interest']) && $user['confirmation_interest'] == 'not_interested') ? 'checked' : '' ?>>
            <label class="form-check-label" for="confirmation_interest_no">No</label>
        </div>
    </div>

    <!-- Official Details -->
    <h4 class="mt-4">Official Details</h4>
    <div class="mb-3">
        <label for="church_membership_no" class="form-label">Church Membership No</label>
        <input type="text" class="form-control" name="church_membership_no" value="<?= htmlspecialchars($user['church_membership_no'] ?? '') ?>" readonly>
        <small class="form-text text-muted">This will be filled in by church officials once your registration is complete.</small>
    </div>

    <button type="button" class="btn btn-primary section-save-btn" data-section="church">
        <i class="fas fa-save"></i> Save Church Details
    </button>
</div>

<!-- Load Church Section JavaScript -->
<script src="../service/church_section.js"></script>