-- ===============================================
-- Migration: Add missing foreign keys where applicable
-- Safe version: Skips if constraint already exists
-- ===============================================

-- Ministries → Users (Leader)
SET @exists_fk := (
    SELECT COUNT(*) 
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_NAME = 'fk_ministries_leader'
    AND CONSTRAINT_SCHEMA = DATABASE()
);
SET @sql := IF(@exists_fk = 0,
    'ALTER TABLE ministries 
        ADD CONSTRAINT fk_ministries_leader 
        FOREIGN KEY (leader_user_id) 
        REFERENCES users(id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE;',
    'SELECT "⚠️ fk_ministries_leader already exists" AS msg;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Leadership Roles → Users
SET @exists_fk := (
    SELECT COUNT(*) 
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_NAME = 'fk_leadership_user'
    AND CONSTRAINT_SCHEMA = DATABASE()
);
SET @sql := IF(@exists_fk = 0,
    'ALTER TABLE leadership_roles 
        ADD CONSTRAINT fk_leadership_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE;',
    'SELECT "⚠️ fk_leadership_user already exists" AS msg;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- User Roles → Users & Roles
SET @exists_fk := (
    SELECT COUNT(*) 
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_NAME = 'fk_userroles_user'
    AND CONSTRAINT_SCHEMA = DATABASE()
);
SET @sql := IF(@exists_fk = 0,
    'ALTER TABLE user_roles 
        ADD CONSTRAINT fk_userroles_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE;',
    'SELECT "⚠️ fk_userroles_user already exists" AS msg;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_fk := (
    SELECT COUNT(*) 
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_NAME = 'fk_userroles_role'
    AND CONSTRAINT_SCHEMA = DATABASE()
);
SET @sql := IF(@exists_fk = 0,
    'ALTER TABLE user_roles 
        ADD CONSTRAINT fk_userroles_role 
        FOREIGN KEY (role_id) 
        REFERENCES roles(role_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE;',
    'SELECT "⚠️ fk_userroles_role already exists" AS msg;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ✅ Done
SELECT "✅ All missing foreign keys checked and created (if absent)" AS completion_status;
