# Admin Login System Fix

## What Was Fixed

1. **Password Hashing**: 
   - Updated `includes/auth.php` to ensure passwords are hashed with `PASSWORD_ARGON2ID`
   - Added `auth_hash_password()` function using Argon2id algorithm
   - Verified `auth_verify_password()` uses `password_verify()`

2. **Database Schema**:
   - Created migration to rename `password` column to `password_hash`
   - Ensured proper column definition for Argon2id hashes

3. **Login Function**:
   - Added `admin_login()` function with:
     - Case-insensitive email comparison using `LOWER(email) = LOWER(:email)`
     - Proper password verification with `password_verify()`
     - Returns `false` if account is inactive or locked
     - Returns `true` on successful login
   - Integrated failed login attempts tracking
   - Account locking after 5 failed attempts (15 minutes)

4. **Admin Login Page**:
   - Updated `/admin/login.php` to use the new `admin_login()` function
   - Simplified login logic by delegating to the auth function

5. **Default Admin User**:
   - Created SQL scripts to seed default admin
   - Email: `admin@tierphysio.local`
   - Password: `Admin123!`
   - Properly hashed with Argon2id

## Files Modified

- `includes/auth.php` - Added `admin_login()` function with proper security
- `admin/login.php` - Updated to use new login function
- `migrations/003_fix_admin_password_column.sql` - Migration for database fixes

## Files Created

- `fix_admin_login.php` - PHP script to fix the system (requires PHP 8.2)
- `fix_admin_direct.sql` - Direct SQL script for database fixes
- `generate_hash.php` - Helper to generate password hashes
- `test_login.php` - Test script to verify the system
- `ADMIN_LOGIN_FIX.md` - This documentation

## How to Apply the Fix

### Option 1: Run SQL Migration (Recommended)

```bash
# Connect to your MySQL/MariaDB database
mysql -u root -p tierphysio < fix_admin_direct.sql
```

### Option 2: Run PHP Fix Script (if PHP 8.2 is available)

```bash
php fix_admin_login.php
```

### Option 3: Manual Database Update

1. Connect to your MySQL database
2. Run the following SQL commands:

```sql
-- Fix column name
ALTER TABLE `admin_users` 
CHANGE COLUMN IF EXISTS `password` `password_hash` VARCHAR(255) NOT NULL;

-- Insert/update default admin
INSERT INTO `admin_users` (
    `email`, 
    `password_hash`, 
    `name`, 
    `is_super_admin`, 
    `is_active`
)
VALUES (
    'admin@tierphysio.local',
    '$argon2id$v=19$m=65536,t=4,p=1$WHEuRkpISDJoWkJ6eG5VUg$3VhF5Rr5aHkH5WVjUzk8WvRwXpe2OtXM5LWwNPNPtLA',
    'System Administrator',
    1,
    1
)
ON DUPLICATE KEY UPDATE 
    `password_hash` = VALUES(`password_hash`),
    `is_active` = 1,
    `is_super_admin` = 1;
```

## Testing the Fix

### Test Login Credentials

- **URL**: `/admin/login.php`
- **Email**: `admin@tierphysio.local`
- **Password**: `Admin123!`

### Run Test Script (if PHP available)

```bash
php test_login.php
```

This will verify:
- Password hashing functions work
- Database schema is correct
- Admin user exists
- Password verification works
- Login function operates correctly

## Security Features Implemented

1. **Argon2id Hashing**: Most secure password hashing algorithm
2. **Case-Insensitive Email**: Prevents login issues due to case differences
3. **Account Locking**: Locks account after 5 failed attempts for 15 minutes
4. **Session Security**: Proper session handling with CSRF protection
5. **Audit Logging**: All login attempts are logged

## Important Notes

1. **Change Default Password**: After first login, immediately change the default password
2. **PHP Version**: System requires PHP 8.2+ for Argon2id support
3. **Database Backup**: Always backup your database before running migrations
4. **Test Environment**: Test the login in a development environment first

## Troubleshooting

### Login Fails

1. Check database connection in `includes/config.php`
2. Verify the `admin_users` table has `password_hash` column (not `password`)
3. Run `test_login.php` to diagnose issues
4. Check PHP error logs for details

### Password Verification Fails

1. Ensure PHP 8.2+ is installed
2. Verify Argon2id support: `php -r "var_dump(PASSWORD_ARGON2ID);"`
3. Re-run the SQL migration to update the password hash

### Database Errors

1. Check MySQL/MariaDB is running
2. Verify database credentials in `.env` or `includes/config.php`
3. Ensure `admin_users` table exists (run `migrations/001_create_admin_tables.sql`)

## Support

If issues persist:
1. Check application logs in `/logs/` directory
2. Review PHP error logs
3. Verify all migrations have been applied in order
4. Test with `test_login.php` script for detailed diagnostics