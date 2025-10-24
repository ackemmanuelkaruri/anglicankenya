<!-- Personal Information Section -->
<div id="personal" class="tab-pane fade show active">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-user"></i> Personal Information</h4>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Username -->
                <div class="col-md-6">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    <div class="invalid-feedback">Username is required</div>
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                </div>

                <!-- First Name -->
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                    <div class="invalid-feedback">First name is required</div>
                </div>

                <!-- Last Name -->
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                    <div class="invalid-feedback">Last name is required</div>
                </div>

                <!-- Phone Number -->
                <div class="col-md-6">
                    <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                           value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" 
                           placeholder="+254..." required>
                    <div class="invalid-feedback">Phone number is required</div>
                </div>

                <!-- Gender -->
                <div class="col-md-6">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">--Select Gender--</option>
                        <option value="Male" <?= (isset($user['gender']) && $user['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= (isset($user['gender']) && $user['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= (isset($user['gender']) && $user['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                    <div class="invalid-feedback">Please select your gender</div>
                </div>

                <!-- Date of Birth -->
                <div class="col-md-6">
                    <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                           value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>" required>
                    <div class="invalid-feedback">Date of birth is required</div>
                    <div class="form-text">
                        <i class="fas fa-info-circle"></i> You must be at least 13 years old to register.
                    </div>
                </div>

                <!-- Country -->
                <div class="col-md-6">
                    <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="country" name="country" 
                           value="<?= htmlspecialchars($userDetails['country'] ?? 'Kenya') ?>" required>
                    <div class="invalid-feedback">Country is required</div>
                </div>

                <!-- Occupation -->
                <div class="col-md-6">
                    <label for="occupation" class="form-label">Occupation <span class="text-danger">*</span></label>
                    <select class="form-select" id="occupation" name="occupation" required>
                        <option value="">--Select Occupation--</option>
                        <?php
                        if (isset($occupations) && is_array($occupations)) {
                            foreach ($occupations as $occupation) {
                                $selected = (isset($userDetails['occupation']) && $userDetails['occupation'] == $occupation) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($occupation) . '" ' . $selected . '>' . htmlspecialchars($occupation) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Please select your occupation</div>
                </div>

                <!-- Marital Status -->
                <div class="col-md-6">
                    <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="marital_status" name="marital_status" required>
                        <option value="">--Select Marital Status--</option>
                        <?php
                        if (isset($maritalStatuses) && is_array($maritalStatuses)) {
                            foreach ($maritalStatuses as $status) {
                                $selected = (isset($userDetails['marital_status']) && $userDetails['marital_status'] == $status) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($status) . '" ' . $selected . '>' . htmlspecialchars($status) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Please select your marital status</div>
                </div>

                <!-- Wedding Type -->
                <div class="col-md-6">
                    <label for="wedding_type" class="form-label">Wedding Type</label>
                    <select class="form-select" id="wedding_type" name="wedding_type">
                        <option value="">--Select Wedding Type--</option>
                        <?php
                        if (isset($weddingTypes) && is_array($weddingTypes)) {
                            foreach ($weddingTypes as $type) {
                                $selected = (isset($userDetails['wedding_type']) && $userDetails['wedding_type'] == $type) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($type) . '" ' . $selected . '>' . htmlspecialchars($type) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Education Level -->
                <div class="col-md-6">
                    <label for="education_level" class="form-label">Education Level <span class="text-danger">*</span></label>
                    <select class="form-select" id="education_level" name="education_level" required>
                        <option value="">--Select Education Level--</option>
                        <?php
                        if (isset($educationLevels) && is_array($educationLevels)) {
                            foreach ($educationLevels as $level) {
                                $selected = (isset($userDetails['education_level']) && $userDetails['education_level'] == $level) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($level) . '" ' . $selected . '>' . htmlspecialchars($level) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Please select your education level</div>
                </div>

                <!-- Passport Photo Upload -->
                <div class="col-12">
                    <label for="passport" class="form-label">Passport Photo</label>
                    <?php if (!empty($userDetails['passport'])): ?>
                        <div class="mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <img src="../../<?= htmlspecialchars($userDetails['passport']) ?>" 
                                     alt="Current Passport" 
                                     class="img-thumbnail" 
                                     style="width: 100px; height: 100px; object-fit: cover;">
                                <div>
                                    <p class="mb-1"><strong>Current Photo:</strong> <?= htmlspecialchars(basename($userDetails['passport'])) ?></p>
                                    <a href="../../<?= htmlspecialchars($userDetails['passport']) ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Full Size
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-2"><i class="fas fa-info-circle"></i> No passport photo uploaded yet</p>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="passport" name="passport" 
                           accept="image/jpeg,image/jpg,image/png">
                    <div class="form-text">
                        <i class="fas fa-info-circle"></i> Upload a clear passport-size photo (JPG or PNG, max 5MB). 
                        <?= !empty($userDetails['passport']) ? 'Uploading a new photo will replace the current one.' : '' ?>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-primary section-save-btn" data-section="personal">
                    <i class="fas fa-save"></i> Save Personal Information
                </button>
                <div id="personal-save-status"></div>
            </div>
        </div>
    </div>
</div>