<!-- Family Members Section - REWRITTEN TO MATCH PERSONAL SECTION -->
<div id="family" class="section family-section tab-content" style="display: none;">
    <h3>Family Members</h3>
    
    <!-- Hidden fields for form processing -->
    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id'] ?? $_SESSION['user_id'] ?? '') ?>">
    <input type="hidden" name="section_type" value="family">
    
    <!-- Action field to determine what operation to perform -->
    <input type="hidden" name="action" id="family-action" value="">
    
    <!-- Family Members Display -->
    <div class="family-members-container">
        <div id="existing-family-members">
            <?php
            if (isset($user['id']) && isset($pdo)) {
                try {
                    $familyStmt = $pdo->prepare("
                        SELECT fm.*, u.username, u.first_name as user_first_name, u.last_name as user_last_name, 
                               u.email as user_email, u.date_of_birth as user_dob,
                               mp.id as minor_profile_id,
                               FLOOR(DATEDIFF(CURDATE(), fm.minor_date_of_birth) / 365.25) as current_age
                        FROM family_members fm 
                        LEFT JOIN users u ON fm.related_user_id = u.id 
                        LEFT JOIN minor_profiles mp ON fm.id = mp.family_member_id
                        WHERE fm.user_id = ? 
                        ORDER BY fm.relationship, 
                                 COALESCE(fm.minor_first_name, u.first_name),
                                 COALESCE(fm.minor_last_name, u.last_name)
                    ");
                    $familyStmt->execute([$user['id']]);
                    $familyMembers = $familyStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($familyMembers)) {
                        foreach ($familyMembers as $member) {
                            $isMinor = $member['is_minor'];
                            $firstName = $isMinor ? $member['minor_first_name'] : $member['user_first_name'];
                            $lastName = $isMinor ? $member['minor_last_name'] : $member['user_last_name'];
                            $email = $isMinor ? $member['minor_email'] : $member['user_email'];
                            $fullName = htmlspecialchars($firstName . ' ' . $lastName);
                            $currentAge = $member['current_age'] ?? 0;
                            
                            echo '<div class="family-member-item ' . ($isMinor ? 'minor-member' : 'user-member') . '" data-id="' . htmlspecialchars($member['id']) . '">';
                            echo '<div class="member-header">';
                            echo '<h4>' . $fullName . '</h4>';
                            echo '<span class="relationship-badge">' . htmlspecialchars($member['relationship']) . '</span>';
                            
                            if ($isMinor) {
                                echo '<span class="minor-badge">Minor (' . $currentAge . ' years)</span>';
                                if ($currentAge >= 18) {
                                    echo '<span class="ready-badge">Ready for Activation</span>';
                                }
                            }
                            
                            echo '<div class="member-actions">';
                            
                            if ($isMinor && $member['minor_profile_id']) {
                                echo '<a href="minor_profile.php?id=' . htmlspecialchars($member['minor_profile_id']) . '" class="btn-view-profile">View Profile</a>';
                            } else if ($isMinor) {
                                echo '<button type="button" class="btn-create-profile" data-member-id="' . htmlspecialchars($member['id']) . '">Create Profile</button>';
                            }
                            
                            echo '<button type="button" class="btn-delete-member" data-id="' . htmlspecialchars($member['id']) . '">Remove</button>';
                            echo '</div>';
                            echo '</div>';
                            
                            echo '<div class="member-details">';
                            if (!$isMinor && $member['username']) {
                                echo '<p><strong>Username:</strong> ' . htmlspecialchars($member['username']) . '</p>';
                            }
                            if ($email) {
                                echo '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>';
                            }
                            if ($isMinor && $member['minor_date_of_birth']) {
                                $dob = new DateTime($member['minor_date_of_birth']);
                                echo '<p><strong>Date of Birth:</strong> ' . $dob->format('M d, Y') . '</p>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="no-family-members">No family members added yet.</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="no-family-members">No family members added yet.</p>';
                }
            }
            ?>
        </div>
        
        <!-- Add Family Member Button -->
        <div class="add-family-member-container">
            <button type="button" class="btn-add-new-family">+ Add Family Member</button>
        </div>
        
        <!-- Member Type Selection (initially hidden) -->
        <div id="member-type-selection" class="member-type-selection" style="display: none;">
            <h4>What type of family member would you like to add?</h4>
            <div class="member-type-options">
                <button type="button" class="btn-member-type" data-type="existing">Existing User</button>
                <button type="button" class="btn-member-type" data-type="minor">Minor Family Member</button>
            </div>
        </div>
        
        <!-- Existing User Form (initially hidden) -->
        <div id="existing-user-form" class="family-member-form" style="display: none;">
            <h4>Add Existing User</h4>
            
            <div class="form-group">
                <label for="existing-user-select">Select User</label>
                <select id="existing-user-select" name="related_user_id" required>
                    <option value="">-- Select a user --</option>
                    <?php
                    if (isset($user['id']) && isset($pdo)) {
                        try {
                            // Get users not already in family
                            $usersStmt = $pdo->prepare("
                                SELECT u.id, u.username, u.first_name, u.last_name, u.email
                                FROM users u
                                LEFT JOIN family_members fm ON u.id = fm.related_user_id AND fm.user_id = ?
                                WHERE u.id != ? AND fm.id IS NULL
                                ORDER BY u.first_name, u.last_name
                            ");
                            $usersStmt->execute([$user['id'], $user['id']]);
                            $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($users as $u) {
                                echo '<option value="' . htmlspecialchars($u['id']) . '">' . 
                                     htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['username'] . ')') . 
                                     '</option>';
                            }
                        } catch (Exception $e) {
                            // Silent error handling
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="relationship-existing">Relationship</label>
                <input type="text" id="relationship-existing" name="relationship" required 
                       placeholder="e.g., Spouse, Parent, Sibling">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-save-family" data-action="add_existing_user_family">Add Family Member</button>
                <button type="button" class="btn-cancel-family">Cancel</button>
            </div>
        </div>
        
        <!-- Minor Form (initially hidden) -->
        <div id="minor-form" class="family-member-form" style="display: none;">
            <h4>Add Minor Family Member</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="minor-first-name">First Name</label>
                    <input type="text" id="minor-first-name" name="minor_first_name" required>
                </div>
                <div class="form-group">
                    <label for="minor-last-name">Last Name</label>
                    <input type="text" id="minor-last-name" name="minor_last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="minor-email">Email</label>
                    <input type="email" id="minor-email" name="minor_email">
                </div>
                <div class="form-group">
                    <label for="minor-phone">Phone</label>
                    <input type="tel" id="minor-phone" name="minor_phone">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="minor-dob">Date of Birth</label>
                    <input type="date" id="minor-dob" name="minor_date_of_birth" required>
                </div>
                <div class="form-group">
                    <label for="minor-gender">Gender</label>
                    <select id="minor-gender" name="minor_gender">
                        <option value="">-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="relationship-minor">Relationship</label>
                <input type="text" id="relationship-minor" name="relationship" required 
                       placeholder="e.g., Child, Grandchild, Sibling">
            </div>
            
            <div class="form-group">
                <label for="minor-notes">Notes (Optional)</label>
                <textarea id="minor-notes" name="minor_notes" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="can_activate_at_18" checked>
                    Allow automatic account creation when this minor turns 18
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-save-family" data-action="add_minor_family">Add Minor</button>
                <button type="button" class="btn-cancel-family">Cancel</button>
            </div>
        </div>
    </div>
    
    <button type="button" class="btn-save-section" data-section="family">Save Family Info</button>
    <div id="family-save-status" class="save-status" style="margin-top: 10px;"></div>
</div>