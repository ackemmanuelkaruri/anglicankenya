-- Migration: Optimize frequently queried columns
ALTER TABLE users 
    ADD INDEX idx_role_level (role_level),
    ADD INDEX idx_account_status (account_status);

ALTER TABLE login_attempts 
    ADD INDEX idx_successful (was_successful),
    ADD INDEX idx_ip_address (ip_address);

ALTER TABLE giving_transactions 
    ADD INDEX idx_method (method),
    ADD INDEX idx_date_given (date_given);

ALTER TABLE dependents 
    ADD INDEX idx_gender (gender),
    ADD INDEX idx_is_converted (is_converted_to_user);

ALTER TABLE families 
    ADD INDEX idx_parish_id (parish_id),
    ADD INDEX idx_created_at (created_at);

ALTER TABLE audit_logs 
    ADD INDEX idx_user_id (user_id),
    ADD INDEX idx_action (action);
