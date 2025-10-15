<?php
class DataQueries {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // PCC Members (Current and Previous)
    public function getPCCMembers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                    u.email,
                    u.phone_number,
                    ul.role AS pcc_role,
                    ul.from_date,
                    ul.to_date,
                    CASE 
                        WHEN ul.is_current = 1 THEN 'Current'
                        WHEN ul.to_date IS NOT NULL THEN 'Previous'
                        ELSE 'Unknown'
                    END AS status,
                    TIMESTAMPDIFF(YEAR, ul.from_date, COALESCE(ul.to_date, CURDATE())) AS years_served
                FROM user_leadership ul
                JOIN users u ON ul.user_id = u.id
                WHERE (ul.leadership_type LIKE '%PCC%' OR ul.department LIKE '%PCC%') AND ul.is_current = 1
                ORDER BY ul.from_date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getPCCMembers: " . $e->getMessage());
            return [];
        }
    }
    
    // Family Relationships (Parent-Child)
    public function getFamilyRelationships() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(parent.first_name, ' ', parent.last_name) AS parent_name,
                    parent.email AS parent_email,
                    parent.phone_number AS parent_phone,
                    CONCAT(child.first_name, ' ', child.last_name) AS child_name,
                    child.email AS child_email,
                    child.phone_number AS child_phone,
                    fr.relationship_type,
                    TIMESTAMPDIFF(YEAR, child.date_of_birth, CURDATE()) AS child_age
                FROM family_relationships fr
                JOIN users parent ON fr.parent_id = parent.id
                JOIN users child ON fr.child_id = child.id
                WHERE fr.relationship_type = 'parent-child'
                ORDER BY parent.last_name, parent.first_name, child_age
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getFamilyRelationships: " . $e->getMessage());
            return [];
        }
    }
    
    // Department Heads (using user_leadership table)
    public function getDepartmentHeads() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id AS department_id,
                    d.name AS department_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS head_name,
                    u.email,
                    u.phone_number
                FROM departments d
                LEFT JOIN user_leadership ul ON d.name = ul.department AND ul.is_current = 1
                LEFT JOIN users u ON ul.user_id = u.id
                ORDER BY d.name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getDepartmentHeads: " . $e->getMessage());
            return [];
        }
    }
    
    // Clergy Members
    public function getClergyMembers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                    u.email,
                    u.phone_number,
                    cr.role_name AS clergy_role,
                    'Parish' AS parish,  -- Since parish is not in clergy_roles table
                    cr.serving_from_date,
                    cr.to_date,
                    CASE 
                        WHEN cr.is_current = 1 THEN 'Current'
                        WHEN cr.to_date IS NOT NULL THEN 'Previous'
                        ELSE 'Unknown'
                    END AS status
                FROM clergy_roles cr
                JOIN users u ON cr.user_id = u.id
                WHERE cr.is_current = 1
                ORDER BY cr.serving_from_date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getClergyMembers: " . $e->getMessage());
            return [];
        }
    }
    
    // Leadership History (using user_leadership table)
    public function getLeadershipHistory() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS leader_name,
                    ul.role,
                    ul.department,
                    ul.from_date,
                    ul.to_date,
                    CASE 
                        WHEN ul.is_current = 1 THEN 'Current'
                        WHEN ul.to_date IS NOT NULL THEN 'Previous'
                        ELSE 'Unknown'
                    END AS status
                FROM user_leadership ul
                JOIN users u ON ul.user_id = u.id
                ORDER BY ul.from_date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getLeadershipHistory: " . $e->getMessage());
            return [];
        }
    }
    
    // Spouse Relationships
    public function getSpouseRelationships() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(spouse1.first_name, ' ', spouse1.last_name) AS spouse1_name,
                    CONCAT(spouse2.first_name, ' ', spouse2.last_name) AS spouse2_name,
                    spouse1.email AS spouse1_email,
                    spouse2.email AS spouse2_email,
                    fr.marriage_date,
                    fr.wedding_type
                FROM family_relationships fr
                JOIN users spouse1 ON fr.user1_id = spouse1.id
                JOIN users spouse2 ON fr.user2_id = spouse2.id
                WHERE fr.relationship_type = 'spouse'
                ORDER BY fr.marriage_date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getSpouseRelationships: " . $e->getMessage());
            return [];
        }
    }
    
    // Family Units (Parents and Children)
    public function getFamilyUnits() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(parent.first_name, ' ', parent.last_name) AS parent_name,
                    parent.email AS parent_email,
                    parent.phone_number AS parent_phone,
                    GROUP_CONCAT(
                        CONCAT(child.first_name, ' ', child.last_name, ' (', TIMESTAMPDIFF(YEAR, child.date_of_birth, CURDATE()), ')')
                        SEPARATOR ', '
                    ) AS children_names,
                    COUNT(child.id) AS number_of_children
                FROM family_relationships fr
                JOIN users parent ON fr.parent_id = parent.id
                JOIN users child ON fr.child_id = child.id
                WHERE fr.relationship_type = 'parent-child'
                GROUP BY parent.id, parent.first_name, parent.last_name, parent.email, parent.phone_number
                ORDER BY parent.last_name, parent.first_name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getFamilyUnits: " . $e->getMessage());
            return [];
        }
    }
    
    // Extended Family Relationships (Siblings, Cousins, etc.)
    public function getExtendedFamily() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(user1.first_name, ' ', user1.last_name) AS person1_name,
                    CONCAT(user2.first_name, ' ', user2.last_name) AS person2_name,
                    fr.relationship_type,
                    fr.relationship_description
                FROM family_relationships fr
                JOIN users user1 ON fr.user1_id = user1.id
                JOIN users user2 ON fr.user2_id = user2.id
                WHERE fr.relationship_type NOT IN ('spouse', 'parent-child')
                ORDER BY fr.relationship_type, user1.last_name, user1.first_name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getExtendedFamily: " . $e->getMessage());
            return [];
        }
    }
    
    // Single Parents
    public function getSingleParents() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(parent.first_name, ' ', parent.last_name) AS parent_name,
                    parent.email AS parent_email,
                    parent.phone_number AS parent_phone,
                    ul.role AS leadership_role,
                    GROUP_CONCAT(
                        CONCAT(child.first_name, ' ', child.last_name, ' (', TIMESTAMPDIFF(YEAR, child.date_of_birth, CURDATE()), ')')
                        SEPARATOR ', '
                    ) AS children_names,
                    COUNT(child.id) AS number_of_children
                FROM users parent
                LEFT JOIN user_leadership ul ON parent.id = ul.user_id AND ul.is_current = 1
                JOIN family_relationships fr ON parent.id = fr.parent_id
                JOIN users child ON fr.child_id = child.id
                WHERE parent.marital_status = 'single parent' AND fr.relationship_type = 'parent-child'
                GROUP BY parent.id, parent.first_name, parent.last_name, parent.email, parent.phone_number, ul.role
                ORDER BY parent.last_name, parent.first_name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getSingleParents: " . $e->getMessage());
            return [];
        }
    }
    
    // Youth Members (18-35 years)
    public function getYouthMembers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.email,
                    u.phone_number,
                    TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS age,
                    u.service_attending
                FROM users u
                WHERE TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) BETWEEN 18 AND 35
                ORDER BY age
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getYouthMembers: " . $e->getMessage());
            return [];
        }
    }
    
    // Senior Members (65+ years)
    public function getSeniorMembers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.email,
                    u.phone_number,
                    TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS age,
                    u.service_attending
                FROM users u
                WHERE TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) >= 65
                ORDER BY age DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getSeniorMembers: " . $e->getMessage());
            return [];
        }
    }
    
    // Ministry Members (using user_ministry_department table)
    public function getMinistryMembers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                    u.email,
                    u.phone_number,
                    umd.ministry_name,
                    umd.department_name,
                    u.service_attending
                FROM user_ministry_department umd
                JOIN users u ON umd.user_id = u.id
                ORDER BY umd.ministry_name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getMinistryMembers: " . $e->getMessage());
            return [];
        }
    }
    
    // Employment History
    public function getEmploymentHistory() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
                    u.email,
                    u.phone_number,
                    eh.job_title,
                    eh.company,
                    eh.from_date,
                    eh.to_date,
                    CASE 
                        WHEN eh.is_current = 1 THEN 'Current'
                        ELSE 'Previous'
                    END AS status
                FROM employment_history eh
                JOIN users u ON eh.user_id = u.id
                ORDER BY eh.from_date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in getEmploymentHistory: " . $e->getMessage());
            return [];
        }
    }
}
?>