-- ===============================================
-- Migration: 006_audit_trigger.sql
-- Purpose: Automatically log changes made to `users`
-- ===============================================

-- 1️⃣ Create audit_log table (if not exists)
CREATE TABLE IF NOT EXISTS audit_log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action_type ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    changed_by VARCHAR(100) DEFAULT NULL,
    change_details TEXT,
    change_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2️⃣ Drop old triggers if they exist
DROP TRIGGER IF EXISTS trg_users_after_insert;
DROP TRIGGER IF EXISTS trg_users_after_update;
DROP TRIGGER IF EXISTS trg_users_after_delete;

-- 3️⃣ Trigger: AFTER INSERT
CREATE TRIGGER trg_users_after_insert
AFTER INSERT ON users
FOR EACH ROW
INSERT INTO audit_log (table_name, record_id, action_type, changed_by, change_details)
VALUES (
    'users',
    NEW.id,
    'INSERT',
    COALESCE(NEW.username,'system'),
    CONCAT('New user created: ', NEW.username)
);

-- 4️⃣ Trigger: AFTER UPDATE
CREATE TRIGGER trg_users_after_update
AFTER UPDATE ON users
FOR EACH ROW
INSERT INTO audit_log (table_name, record_id, action_type, changed_by, change_details)
VALUES (
    'users',
    NEW.id,
    'UPDATE',
    COALESCE(NEW.username,'system'),
    CONCAT('User updated: ', NEW.username)
);

-- 5️⃣ Trigger: AFTER DELETE
CREATE TRIGGER trg_users_after_delete
AFTER DELETE ON users
FOR EACH ROW
INSERT INTO audit_log (table_name, record_id, action_type, changed_by, change_details)
VALUES (
    'users',
    OLD.id,
    'DELETE',
    COALESCE(OLD.username,'system'),
    CONCAT('User deleted: ', OLD.username)
);

-- ✅ Done
SELECT "✅ Audit triggers for users created successfully" AS completion_status;
