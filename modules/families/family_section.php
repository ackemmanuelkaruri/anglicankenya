<?php
/**
 * family_section.php
 * Displays and manages family members and dependents
 */

// Fetch linked users (approved relationships)
try {
    $stmt = $pdo->prepare("
        SELECT ur.*, 
               u.first_name, u.last_name, u.email,
               CASE 
                   WHEN ur.user1_id = ? THEN ur.relationship_type1
                   ELSE ur.relationship_type2
               END as my_relationship
        FROM user_relationships ur
        JOIN users u ON (
            CASE 
                WHEN ur.user1_id = ? THEN ur.user2_id = u.id
                ELSE ur.user1_id = u.id
            END
        )
        WHERE (ur.user1_id = ? OR ur.user2_id = ?) 
        AND ur.status = 'APPROVED'
        ORDER BY ur.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
    $linkedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching linked users: " . $e->getMessage());
    $linkedUsers = [];
}

// Fetch dependents (minors)
try {
    $stmt = $pdo->prepare("SELECT * FROM dependents WHERE parent_user_id = ? ORDER BY date_of_birth DESC");
    $stmt->execute([$user['id']]);
    $dependents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching dependents: " . $e->getMessage());
    $dependents = [];
}
?>

<div class="section family-section" data-section="family">
    <div class="section-header mb-4">
        <h3><i class="fas fa-users"></i> Family Members</h3>
        <p class="text-muted">Manage your family connections and dependents.</p>
    </div>

    <!-- Request Family Link Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-link"></i> Link to Existing Family Member</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Connect with adult family members who already have accounts.</p>
            
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label for="target_username" class="form-label">Username or Email <span class="text-danger">*</span></label>
                    <input type="text" id="target_username" class="form-control" placeholder="Enter username or email">
                </div>
                
                <div class="col-md-5 mb-3">
                    <label for="relationship_type" class="form-label">Relationship <span class="text-danger">*</span></label>
                    <select id="relationship_type" class="form-select">
                        <option value="">Select Relationship</option>
                        <option value="Spouse">Spouse</option>
                        <option value="Parent">Parent</option>
                        <option value="Child">Child (Adult)</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Guardian">Guardian</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="button" id="request_link_btn" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane"></i> Send Request
                    </button>
                </div>
            </div>
            
            <div id="request-link-status"></div>
        </div>
    </div>

    <!-- Linked Family Members -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-user-friends"></i> Linked Family Members</h5>
        </div>
        <div class="card-body">
            <div id="linked-users-list">
                <?php if (!empty($linkedUsers)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Relationship</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linkedUsers as $link): ?>
                                    <tr data-id="<?= $link['id'] ?>">
                                        <td><?= htmlspecialchars($link['first_name'] . ' ' . $link['last_name']) ?></td>
                                        <td><?= htmlspecialchars($link['email']) ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($link['my_relationship']) ?></span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger delete-relationship-btn" data-id="<?= $link['id'] ?>">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><i class="fas fa-info-circle"></i> No linked family members yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Dependent Section -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-child"></i> Add Dependent (Minor)</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Add children or other dependents under 18 years old.</p>
            
            <form id="add-dependent-form">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="dep_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="dep_first_name" name="first_name" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="dep_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" id="dep_last_name" name="last_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="dep_dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" id="dep_dob" name="date_of_birth" class="form-control" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="dep_gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select id="dep_gender" name="gender" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="dep_relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                        <select id="dep_relationship" name="relationship_to_parent" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Son">Son</option>
                            <option value="Daughter">Daughter</option>
                            <option value="Grandson">Grandson</option>
                            <option value="Granddaughter">Granddaughter</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="dep_school" class="form-label">School Name</label>
                        <input type="text" id="dep_school" name="school_name" class="form-control" placeholder="Optional">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Dependent
                </button>
            </form>
            
            <div id="add-dependent-status" class="mt-3"></div>
        </div>
    </div>

    <!-- Dependents List -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-baby"></i> My Dependents</h5>
        </div>
        <div class="card-body">
            <div id="dependents-list">
                <?php if (!empty($dependents)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>DOB</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Relationship</th>
                                    <th>School</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dependents as $dep): 
                                    $dob = new DateTime($dep['date_of_birth']);
                                    $age = $dob->diff(new DateTime())->y;
                                ?>
                                    <tr data-id="<?= $dep['dependent_id'] ?>">
                                        <td><?= htmlspecialchars($dep['first_name'] . ' ' . $dep['last_name']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($dep['date_of_birth'])) ?></td>
                                        <td><?= $age ?> yrs</td>
                                        <td><?= htmlspecialchars($dep['gender']) ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($dep['relationship_to_parent']) ?></span></td>
                                        <td><?= htmlspecialchars($dep['school_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger delete-dependent-btn" data-id="<?= $dep['dependent_id'] ?>">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><i class="fas fa-info-circle"></i> No dependents added yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="family-status-global" class="mt-3"></div>
</div>

<script src="../families/family_section.js"></script>