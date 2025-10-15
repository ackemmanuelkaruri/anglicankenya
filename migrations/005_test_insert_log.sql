-- Migration: Insert system log for testing
INSERT INTO audit_logs (user_id, action, description)
VALUES (NULL, 'migration_test', '✅ Migration system executed successfully.');
