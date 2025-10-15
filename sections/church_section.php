<!-- Church Details Section -->
<div class="section church-section tab-content" id="church">
    <h3>Church Details</h3>

    <!-- Hidden user ID -->
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">

    <label>Service Attending</label>
    <select name="service_attending" id="service_attending" required>
        <option value="">--Select--</option>
        <option value="english" <?= (isset($user['service_attending']) && $user['service_attending'] == 'english') ? 'selected' : '' ?>>English Service</option>
        <option value="kikuyu" <?= (isset($user['service_attending']) && $user['service_attending'] == 'kikuyu') ? 'selected' : '' ?>>Kikuyu Service</option>
        <option value="teens" <?= (isset($user['service_attending']) && $user['service_attending'] == 'teens') ? 'selected' : '' ?>>Teens Service</option>
        <option value="sunday_school" <?= (isset($user['service_attending']) && $user['service_attending'] == 'sunday_school') ? 'selected' : '' ?>>Sunday School</option>
    </select>

    <!-- English Service Teams -->
    <div id="english_service_team_section" style="display: <?= (isset($user['service_attending']) && $user['service_attending'] == 'english') ? 'block' : 'none' ?>;">
        <label>English Service Team</label>
        <select name="english_service_team">
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
    <div id="kikuyu_cell_group_section" style="display: <?= (isset($user['service_attending']) && $user['service_attending'] == 'kikuyu') ? 'block' : 'none' ?>;">
        <label>Kikuyu Cell Group</label>
        <select name="kikuyu_cell_group" id="kikuyu_cell_group">
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
    <div id="family_group_section" style="display: <?= (isset($user['service_attending']) && $user['service_attending'] == 'kikuyu' && !empty($user['kikuyu_cell_group'])) ? 'block' : 'none' ?>;">
        <label>Family Group</label>
        <select name="family_group" id="family_group" data-current-value="<?= htmlspecialchars($user['family_group'] ?? '') ?>">
            <option value="">--Select--</option>
            <?php if (!empty($user['family_group'])): ?>
                <option value="<?= htmlspecialchars($user['family_group']) ?>" selected><?= htmlspecialchars($user['family_group']) ?></option>
            <?php endif; ?>
        </select>
    </div>

    <!-- Baptism -->
    <label>Are you baptized?</label>
    <div class="radio-group">
        <div class="radio-option">
            <input type="radio" id="baptized_yes" name="baptized" value="yes" <?= (isset($user['baptized']) && $user['baptized'] == 'yes') ? 'checked' : '' ?> required>
            <label for="baptized_yes">Yes</label>
        </div>
        <div class="radio-option">
            <input type="radio" id="baptized_no" name="baptized" value="no" <?= (isset($user['baptized']) && $user['baptized'] == 'no') ? 'checked' : '' ?>>
            <label for="baptized_no">No</label>
        </div>
    </div>

    <!-- Baptism Certificate -->
<div id="baptism_certificate_section" style="display: <?= (isset($user['baptized']) && $user['baptized'] == 'yes') ? 'block' : 'none' ?>;">
    <label>Baptism Certificate</label>
    <div>
        <?php if (!empty($user['baptism_certificate'])): ?>
            <?php 
            // Force consistent path structure
            $baptismFilename = basename($user['baptism_certificate']);
            $baptismPath = 'uploads/certificates/' . $baptismFilename;
            $fullFilePath = '../' . $baptismPath;
            ?>
            <p>Current Certificate: 
                <?php if (file_exists($fullFilePath)): ?>
                    <a href="../<?= htmlspecialchars($baptismPath) ?>" target="_blank">View Current Certificate</a>
                <?php else: ?>
                    <span style="color: red;">Certificate file not found</span>
                <?php endif; ?>
            </p>
            <p><small>Current file: <?= htmlspecialchars($baptismFilename) ?></small></p>
        <?php else: ?>
            <p><small>No baptism certificate currently uploaded</small></p>
        <?php endif; ?>
        <input type="file" name="baptism_certificate" accept="image/*,application/pdf">
        <small>Upload a new baptism certificate (optional). Current file will be replaced if you upload a new one.</small>
    </div>
</div>

<!-- Baptism Interest -->
<div id="baptism_interest_section" style="display: <?= (isset($user['baptized']) && $user['baptized'] == 'no') ? 'block' : 'none' ?>;">
    <div class="note">Baptism is an important sacrament in our church. If you're interested in being baptized, please let us know.</div>
    <label>Are you interested in being baptized?</label>
    <div class="radio-group">
        <div class="radio-option">
            <input type="radio" id="baptism_interest_yes" name="baptism_interest" value="yes" <?= (isset($user['baptism_interest']) && $user['baptism_interest'] == 'interested') ? 'checked' : '' ?>>
            <label for="baptism_interest_yes">Yes</label>
        </div>
        <div class="radio-option">
            <input type="radio" id="baptism_interest_no" name="baptism_interest" value="no" <?= (isset($user['baptism_interest']) && $user['baptism_interest'] == 'not_interested') ? 'checked' : '' ?>>
            <label for="baptism_interest_no">No</label>
        </div>
    </div>
</div>

<!-- Confirmation -->
<label>Are you confirmed?</label>
<div class="radio-group">
    <div class="radio-option">
        <input type="radio" id="confirmed_yes" name="confirmed" value="yes" <?= (isset($user['confirmed']) && $user['confirmed'] == 'yes') ? 'checked' : '' ?> required>
        <label for="confirmed_yes">Yes</label>
    </div>
    <div class="radio-option">
        <input type="radio" id="confirmed_no" name="confirmed" value="no" <?= (isset($user['confirmed']) && $user['confirmed'] == 'no') ? 'checked' : '' ?>>
        <label for="confirmed_no">No</label>
    </div>
</div>

<!-- Confirmation Certificate -->
<div id="confirmation_certificate_section" style="display: <?= (isset($user['confirmed']) && $user['confirmed'] == 'yes') ? 'block' : 'none' ?>;">
    <label>Confirmation Certificate</label>
    <div>
        <?php if (!empty($user['confirmation_certificate'])): ?>
            <?php 
            // Force consistent path structure
            $confirmationFilename = basename($user['confirmation_certificate']);
            $confirmationPath = 'uploads/certificates/' . $confirmationFilename;
            $fullFilePath = '../' . $confirmationPath;
            ?>
            <p>Current Certificate: 
                <?php if (file_exists($fullFilePath)): ?>
                    <a href="../<?= htmlspecialchars($confirmationPath) ?>" target="_blank">View Current Certificate</a>
                <?php else: ?>
                    <span style="color: red;">Certificate file not found</span>
                <?php endif; ?>
            </p>
            <p><small>Current file: <?= htmlspecialchars($confirmationFilename) ?></small></p>
        <?php else: ?>
            <p><small>No confirmation certificate currently uploaded</small></p>
        <?php endif; ?>
        <input type="file" name="confirmation_certificate" accept="image/*,application/pdf">
        <small>Upload a new confirmation certificate (optional). Current file will be replaced if you upload a new one.</small>
    </div>
</div>

<!-- Confirmation Interest -->
<div id="confirmation_interest_section" style="display: <?= (isset($user['confirmed']) && $user['confirmed'] == 'no') ? 'block' : 'none' ?>;">
    <div class="note">Confirmation is an important step in your faith journey. If you're interested in being confirmed, please let us know.</div>
    <label>Are you interested in being confirmed?</label>
    <div class="radio-group">
        <div class="radio-option">
            <input type="radio" id="confirmation_interest_yes" name="confirmation_interest" value="yes" <?= (isset($user['confirmation_interest']) && $user['confirmation_interest'] == 'interested') ? 'checked' : '' ?>>
            <label for="confirmation_interest_yes">Yes</label>
        </div>
        <div class="radio-option">
            <input type="radio" id="confirmation_interest_no" name="confirmation_interest" value="no" <?= (isset($user['confirmation_interest']) && $user['confirmation_interest'] == 'not_interested') ? 'checked' : '' ?>>
            <label for="confirmation_interest_no">No</label>
        </div>
    </div>
</div>
    <!-- Official Details -->
    <h3>Official Details</h3>
    <label>Church Membership No</label>
    <input type="text" name="church_membership_no" value="<?= htmlspecialchars($user['church_membership_no'] ?? '') ?>" readonly>
    <small>This will be filled in by church officials once your registration is complete.</small>

    <button type="button" class="btn-save-section" data-section="church">Save Church Details</button>
</div>
