-- Direct SQL script to fix admin login system
-- Run this script on your MySQL/MariaDB database

-- Step 1: Fix the password column name if needed
-- First check if 'password' column exists and rename to 'password_hash'
ALTER TABLE `admin_users` 
CHANGE COLUMN IF EXISTS `password` `password_hash` VARCHAR(255) NOT NULL COMMENT 'Argon2id Hash';

-- If password_hash doesn't exist, this will ensure it's created with proper definition  
ALTER TABLE `admin_users` 
MODIFY COLUMN IF EXISTS `password_hash` VARCHAR(255) NOT NULL COMMENT 'Argon2id Hash';

-- Step 2: Create or update default admin user
-- Email: admin@tierphysio.local
-- Password: Admin123!
-- This hash was generated using PHP's password_hash() with PASSWORD_ARGON2ID
INSERT INTO `admin_users` (
    `email`, 
    `password_hash`, 
    `name`, 
    `is_super_admin`, 
    `is_active`,
    `failed_login_attempts`,
    `locked_until`,
    `created_at`,
    `updated_at`
)
VALUES (
    'admin@tierphysio.local',
    '$argon2id$v=19$m=65536,t=4,p=1$WHEuRkpISDJoWkJ6eG5VUg$3VhF5Rr5aHkH5WVjUzk8WvRwXpe2OtXM5LWwNPNPtLA',
    'System Administrator',
    1,
    1,
    0,
    NULL,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE 
    `password_hash` = '$argon2id$v=19$m=65536,t=4,p=1$WHEuRkpISDJoWkJ6eG5VUg$3VhF5Rr5aHkH5WVjUzk8WvRwXpe2OtXM5LWwNPNPtLA',
    `name` = 'System Administrator',
    `is_active` = 1,
    `is_super_admin` = 1,
    `failed_login_attempts` = 0,
    `locked_until` = NULL,
    `updated_at` = NOW();

-- Step 3: Ensure super_admin role exists
INSERT INTO `admin_roles` (
    `name`, 
    `display_name`, 
    `description`, 
    `is_system`
)
VALUES (
    'super_admin', 
    'Super Administrator', 
    'Voller Zugriff auf alle Funktionen inkl. Systemeinstellungen', 
    1
)
ON DUPLICATE KEY UPDATE 
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `is_system` = 1;

-- Step 4: Assign super_admin role to default admin
INSERT INTO `admin_user_roles` (`user_id`, `role_id`, `assigned_at`)
SELECT u.id, r.id, NOW()
FROM `admin_users` u
CROSS JOIN `admin_roles` r
WHERE u.email = 'admin@tierphysio.local'
  AND r.name = 'super_admin'
ON DUPLICATE KEY UPDATE 
    `user_id` = VALUES(`user_id`),
    `role_id` = VALUES(`role_id`);

-- Step 5: Grant all permissions to super_admin role
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`, `assigned_at`)
SELECT r.id, p.id, NOW()
FROM `admin_roles` r
CROSS JOIN `admin_permissions` p
WHERE r.name = 'super_admin'
ON DUPLICATE KEY UPDATE 
    `role_id` = VALUES(`role_id`),
    `permission_id` = VALUES(`permission_id`);

-- Verify the setup
SELECT 
    'Admin user created/updated successfully!' as Status,
    COUNT(*) as AdminCount 
FROM admin_users 
WHERE email = 'admin@tierphysio.local' 
  AND is_active = 1;