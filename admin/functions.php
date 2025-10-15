<?php
// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['username']);
}

// Function to redirect to a page
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to sanitize input
function sanitize($input) {
    global $pdo;
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to get user by ID
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get user by email
function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get user by username
function getUserByUsername($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to create a new user
function createUser($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, username, email, phone_number, password, gender, marital_status, occupation, education_level, country, date_of_birth, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['username'],
            $data['email'],
            $data['phone_number'],
            $data['password'],
            $data['gender'],
            $data['marital_status'],
            $data['occupation'],
            $data['education_level'],
            $data['country'],
            $data['date_of_birth'],
            $data['role'],
            $data['status']
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to update user
function updateUser($id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                username = ?,
                email = ?,
                phone_number = ?,
                gender = ?,
                marital_status = ?,
                occupation = ?,
                education_level = ?,
                country = ?,
                date_of_birth = ?,
                role = ?,
                status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['username'],
            $data['email'],
            $data['phone_number'],
            $data['gender'],
            $data['marital_status'],
            $data['occupation'],
            $data['education_level'],
            $data['country'],
            $data['date_of_birth'],
            $data['role'],
            $data['status'],
            $id
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to delete user
function deleteUser($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get all users
function getAllUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM users ORDER BY last_name, first_name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get users by role
function getUsersByRole($role) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? ORDER BY last_name, first_name");
    $stmt->execute([$role]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get users by status
function getUsersByStatus($status) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE status = ? ORDER BY last_name, first_name");
    $stmt->execute([$status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all departments
function getAllDepartments() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get department by ID
function getDepartmentById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to create department
function createDepartment($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO departments (name)
            VALUES (?)
        ");
        
        $stmt->execute([$data['name']]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to update department
function updateDepartment($id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE departments SET
                name = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$data['name'], $id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to delete department
function deleteDepartment($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get all ministries
function getAllMinistries() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM ministries ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get ministry by ID
function getMinistryById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM ministries WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to create ministry
function createMinistry($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO ministries (name)
            VALUES (?)
        ");
        
        $stmt->execute([$data['name']]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to update ministry
function updateMinistry($id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE ministries SET
                name = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$data['name'], $id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to delete ministry
function deleteMinistry($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM ministries WHERE id = ?");
        $stmt->execute([$id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get user leadership by user ID
function getUserLeadershipByUserId($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM user_leadership 
        WHERE user_id = ? 
        ORDER BY from_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add user leadership
function addUserLeadership($userId, $leadershipType, $department, $ministry, $role, $otherRole, $fromDate, $toDate, $isCurrent) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_leadership (user_id, leadership_type, department, ministry, role, other_role, from_date, to_date, is_current)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $leadershipType, $department, $ministry, $role, $otherRole, $fromDate, $toDate, $isCurrent]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to update user leadership
function updateUserLeadership($id, $leadershipType, $department, $ministry, $role, $otherRole, $fromDate, $toDate, $isCurrent) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_leadership SET
                leadership_type = ?,
                department = ?,
                ministry = ?,
                role = ?,
                other_role = ?,
                from_date = ?,
                to_date = ?,
                is_current = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$leadershipType, $department, $ministry, $role, $otherRole, $fromDate, $toDate, $isCurrent, $id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get clergy roles by user ID
function getClergyRolesByUserId($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM clergy_roles 
        WHERE user_id = ? 
        ORDER BY serving_from_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add clergy role
function addClergyRole($userId, $roleId, $roleName, $servingFromDate, $toDate, $isCurrent) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO clergy_roles (user_id, role_id, role_name, serving_from_date, to_date, is_current)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $roleId, $roleName, $servingFromDate, $toDate, $isCurrent]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to update clergy role
function updateClergyRole($id, $roleId, $roleName, $servingFromDate, $toDate, $isCurrent) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE clergy_roles SET
                role_id = ?,
                role_name = ?,
                serving_from_date = ?,
                to_date = ?,
                is_current = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$roleId, $roleName, $servingFromDate, $toDate, $isCurrent, $id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get family relationships by user ID
function getFamilyRelationshipsByUserId($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM family_relationships 
        WHERE user1_id = ? OR user2_id = ? OR parent_id = ? OR child_id = ?
    ");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add family relationship
function addFamilyRelationship($user1Id, $user2Id, $parentId, $childId, $relationshipType, $relationshipDescription, $marriageDate, $weddingType) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO family_relationships (user1_id, user2_id, parent_id, child_id, relationship_type, relationship_description, marriage_date, wedding_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$user1Id, $user2Id, $parentId, $childId, $relationshipType, $relationshipDescription, $marriageDate, $weddingType]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to update family relationship
function updateFamilyRelationship($id, $relationshipType, $relationshipDescription, $marriageDate, $weddingType) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE family_relationships SET
                relationship_type = ?,
                relationship_description = ?,
                marriage_date = ?,
                wedding_type = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$relationshipType, $relationshipDescription, $marriageDate, $weddingType, $id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get user ministry department by user ID
function getUserMinistryDepartmentByUserId($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM user_ministry_department 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add user ministry department
function addUserMinistryDepartment($userId, $ministryName, $departmentName) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_ministry_department (user_id, ministry_name, department_name)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$userId, $ministryName, $departmentName]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to update user ministry department
function updateUserMinistryDepartment($id, $ministryName, $departmentName) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_ministry_department SET
                ministry_name = ?,
                department_name = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$ministryName, $departmentName, $id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get employment history by user ID
function getEmploymentHistoryByUserId($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM employment_history 
        WHERE user_id = ? 
        ORDER BY from_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add employment history
function addEmploymentHistory($userId, $jobTitle, $company, $fromDate, $toDate, $isCurrent) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO employment_history (user_id, job_title, company, from_date, to_date, is_current)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $jobTitle, $company, $fromDate, $toDate, $isCurrent]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to update employment history
function updateEmploymentHistory($id, $jobTitle, $company, $fromDate, $toDate, $isCurrent) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE employment_history SET
                job_title = ?,
                company = ?,
                from_date = ?,
                to_date = ?,
                is_current = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$jobTitle, $company, $fromDate, $toDate, $isCurrent, $id]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>