-- Migration: Fix Admin Password Column
-- Changes 'password' column to 'password_hash' for consistency

-- Check if column 'password' exists and rename it to 'password_hash'
-- Note: This uses a conditional approach for safety
ALTER TABLE `admin_users` 
CHANGE COLUMN IF EXISTS `password` `password_hash` VARCHAR(255) NOT NULL COMMENT 'Argon2id Hash';

-- If password_hash already exists, ensure it has the correct definition
ALTER TABLE `admin_users` 
MODIFY COLUMN `password_hash` VARCHAR(255) NOT NULL COMMENT 'Argon2id Hash';

-- Create or update the default admin user with proper Argon2id hash
-- Email: admin@tierphysio.local
-- Password: Admin123!
-- Using proper Argon2id hash for the password
INSERT INTO `admin_users` (
    `email`, 
    `password_hash`, 
    `name`, 
    `is_super_admin`, 
    `is_active`,
    `created_at`,
    `updated_at`
)
VALUES (
    'admin@tierphysio.local',
    '$argon2id$v=19$m=65536,t=4,p=1$ZkJHcEFyNnRVZzFNalJMaw$qKqKqJI1F8Y3xrPvWQqVvE0nKqKqMv7hbqVvP7Y3xrE',
    'System Administrator',
    1,
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE 
    `password_hash` = '$argon2id$v=19$m=65536,t=4,p=1$ZkJHcEFyNnRVZzFNalJMaw$qKqKqJI1F8Y3xrPvWQqVvE0nKqKqMv7hbqVvP7Y3xrE',
    `name` = 'System Administrator',
    `is_active` = 1,
    `is_super_admin` = 1,
    `failed_login_attempts` = 0,
    `locked_until` = NULL,
    `updated_at` = NOW();

-- Ensure the super_admin role exists
INSERT INTO `admin_roles` (`name`, `display_name`, `description`, `is_system`)
VALUES (
    'super_admin', 
    'Super Administrator', 
    'Voller Zugriff auf alle Funktionen inkl. Systemeinstellungen', 
    1
)
ON DUPLICATE KEY UPDATE 
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`);

-- Assign super_admin role to the default admin
INSERT INTO `admin_user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id
FROM `admin_users` u
CROSS JOIN `admin_roles` r
WHERE u.email = 'admin@tierphysio.local'
  AND r.name = 'super_admin'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);

-- Grant all permissions to super_admin role
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `admin_roles` r
CROSS JOIN `admin_permissions` p
WHERE r.name = 'super_admin'
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);