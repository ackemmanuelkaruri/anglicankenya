<!-- Personal Information Section - FIXED VERSION -->
<div id="personal" class="section personal-section tab-content active">
    <h3>Personal Information</h3>
    
    <!-- Add these hidden fields that your backend expects -->
    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id'] ?? $_SESSION['user_id'] ?? '') ?>">
    <input type="hidden" name="section_type" value="personal">
    
    <label>Username</label>
    <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
    
    <label>First Name</label>
    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
    
    <label>Last Name</label>
    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
    
    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
    
    <label>Phone Number</label>
    <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" required>
    
    <label>Gender</label>
    <select name="gender">
        <option value="">--Select Gender--</option>
        <option value="Male" <?= (isset($user['gender']) && $user['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= (isset($user['gender']) && $user['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
        <option value="Other" <?= (isset($user['gender']) && $user['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
        <option value="Prefer_not_to_say" <?= (isset($user['gender']) && $user['gender'] == 'prefer_not_to_say') ? 'selected' : '' ?>>Prefer not to say</option>
    </select>
    
    <label>Country</label>
    <input type="text" name="country" value="<?= htmlspecialchars($user['country'] ?? '') ?>" required>
    
   <label>Passport</label>
<div>
    <?php if (!empty($user['passport'])): ?>
        <p>Current Passport: <a href="../<?= htmlspecialchars($user['passport']) ?>" target="_blank">View Current Passport</a></p>
        <p><small>Current file: <?= htmlspecialchars(basename($user['passport'])) ?></small></p>
    <?php else: ?>
        <p><small>No passport currently uploaded</small></p>
    <?php endif; ?>
    <input type="file" name="passport" accept="image/*">
    <small>Upload a new passport image (optional). Current file will be replaced if you upload a new one.</small>
</div>
    
    <label>Occupation</label>
    <select name="occupation" required>
        <option value="">--Select Occupation--</option>
        <?php
        if (isset($occupations) && is_array($occupations)) {
            foreach ($occupations as $occupation) {
                $selected = (isset($user['occupation']) && $user['occupation'] == $occupation) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($occupation) . '" ' . $selected . '>' . htmlspecialchars($occupation) . '</option>';
            }
        }
        ?>
    </select>
    
    <label>Marital Status</label>
    <select name="marital_status" required>
        <option value="">--Select Marital Status--</option>
        <?php
        if (isset($maritalStatuses) && is_array($maritalStatuses)) {
            foreach ($maritalStatuses as $status) {
                $selected = (isset($user['marital_status']) && $user['marital_status'] == $status) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($status) . '" ' . $selected . '>' . htmlspecialchars($status) . '</option>';
            }
        }
        ?>
    </select>
    
    <label>Wedding Type</label>
    <select name="wedding_type">
        <option value="">--Select Wedding Type--</option>
        <?php
        if (isset($weddingTypes) && is_array($weddingTypes)) {
            foreach ($weddingTypes as $type) {
                $selected = (isset($user['wedding_type']) && $user['wedding_type'] == $type) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($type) . '" ' . $selected . '>' . htmlspecialchars($type) . '</option>';
            }
        }
        ?>
    </select>
    
    <label>Education Level</label>
    <select name="education_level" required>
        <option value="">--Select Education Level--</option>
        <?php
        if (isset($educationLevels) && is_array($educationLevels)) {
            foreach ($educationLevels as $level) {
                $selected = (isset($user['education_level']) && $user['education_level'] == $level) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($level) . '" ' . $selected . '>' . htmlspecialchars($level) . '</option>';
            }
        }
        ?>
    </select>
    
    <button type="button" class="btn-save-section" data-section="personal">Save Personal Info</button>
    <div id="save-status" style="margin-top: 10px;"></div>
</div>

