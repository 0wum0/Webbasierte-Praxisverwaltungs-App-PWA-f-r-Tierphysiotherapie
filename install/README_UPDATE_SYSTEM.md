# 🚀 Tierphysio Manager - Intelligent Update System

## Overview

The Tierphysio Manager now features an intelligent, unified installer and update system that automatically detects the current installation status and handles both fresh installations and updates seamlessly.

## Key Features

### 🎯 Intelligent Detection
- **Automatic Version Detection**: Checks current database version against application version
- **Smart Routing**: Automatically determines whether to run installation or update
- **Migration Tracking**: Keeps detailed logs of all database migrations

### 📊 KPI Dashboard 2.0
- **Live Statistics**: Real-time metrics updated every 30 seconds
- **Revenue Tracking**: Daily, monthly, yearly, and total revenue analytics
- **Birthday Reminders**: Upcoming patient birthdays with age calculations
- **Chart.js Integration**: Beautiful visualizations for trends and data

### 🔄 Update Workflow
1. System checks for `install.lock` file
2. If exists, compares database version with APP_VERSION
3. Shows update notification if newer version available
4. Interactive update wizard with changelog display
5. Automatic migration execution with rollback support
6. Success confirmation and redirect to dashboard

### 📁 File Structure

```
/install/
├── install.php           # Unified installer/updater entry point
├── installer_old.php     # Legacy installer (backup)
├── migrations/          
│   ├── 001_create_system_tables.sql
│   ├── 002_create_core_tables.sql
│   ├── 003_add_kpi_features.sql
│   └── 004_add_treatment_tracking.sql
└── README_UPDATE_SYSTEM.md

/includes/
├── version.php          # Version management
├── footer.php           # Footer with changelog modal
├── bootstrap.php        # Updated with checkInstallStatus()
└── install.lock         # Installation lock file (created after install)

/api/
└── dashboard_metrics.php # KPI Dashboard API endpoint
```

## Migration System

### Creating New Migrations

1. Add new SQL file to `/install/migrations/` with naming pattern: `XXX_description.sql`
2. Include version update at end of migration:
```sql
UPDATE system_info SET key_value = 'X.X.X' WHERE key_name = 'db_version';
```
3. Log the migration:
```sql
INSERT INTO migration_log (filename, version, status) VALUES
    ('XXX_description.sql', 'X.X.X', 'success');
```

### Migration Features
- **Automatic Ordering**: Files are processed in alphabetical order
- **Skip Executed**: Already run migrations are automatically skipped
- **Transaction Support**: Each migration runs in a transaction for safety
- **Error Logging**: Failed migrations are logged with error details

## Version Management

Edit `/includes/version.php` to:
- Update `APP_VERSION` constant
- Add entry to `VERSION_HISTORY` array
- Version format: `MAJOR.MINOR.PATCH`

## Security Features

- **CSRF Protection**: All forms use CSRF tokens
- **Lock File**: Prevents re-installation without manual intervention
- **Session Security**: Secure session configuration
- **Input Validation**: All user inputs are validated and sanitized

## Update Notifications

The system automatically checks for updates and displays notifications:
- Dashboard banner for available updates
- Changelog modal with version history
- Direct link to update wizard

## Changelog Modal

Access the changelog from:
- Footer link "📝 Changelog"
- Update notification banner
- Modal includes full version history with icons

## Developer Credits

**Developer**: Florian Engelhardt  
**Email**: florian0engelhardt@gmail.com  
**Technologies**: PHP 8.2, MySQL, Bootstrap 5, Chart.js

## Maintenance Commands

```bash
# Check current version
php -r "require 'includes/version.php'; echo APP_VERSION;"

# Force update check (remove lock temporarily)
mv includes/install.lock includes/install.lock.bak

# Restore after update
mv includes/install.lock.bak includes/install.lock

# View migration log
mysql -u user -p database -e "SELECT * FROM migration_log ORDER BY executed_at DESC;"
```

## Troubleshooting

### Update Not Detected
1. Check `system_info` table has correct `db_version`
2. Verify `APP_VERSION` in `/includes/version.php`
3. Clear session to reset update flags

### Migration Failed
1. Check migration log table for error details
2. Verify SQL syntax in migration file
3. Ensure proper database permissions

### Dashboard Not Loading Data
1. Check `/api/dashboard_metrics.php` is accessible
2. Verify authentication is working
3. Check browser console for JavaScript errors

## Best Practices

1. **Always Backup**: Create database backup before updates
2. **Test Migrations**: Test on development environment first
3. **Version Incrementally**: Follow semantic versioning
4. **Document Changes**: Update changelog with clear descriptions
5. **Monitor Logs**: Check migration_log table after updates

---

## Commit Message Template

```
feat(installer): add intelligent installer and update system with version detection, migration runner, changelog modal and KPI dashboard integration
```

---

💡 **Note**: This update system is designed to be maintenance-free and user-friendly, requiring minimal technical knowledge for end users while providing powerful features for developers.