-- Migration 003: Add KPI Dashboard Features
-- This migration adds fields and tables needed for KPI Dashboard 2.0

-- Add birthdate to patients if not exists
ALTER TABLE `patients` 
    MODIFY COLUMN `birthdate` DATE NULL COMMENT 'Patient birthdate for birthday reminders';

-- Add financial tracking fields to invoices
ALTER TABLE `invoices`
    ADD COLUMN IF NOT EXISTS `currency` VARCHAR(3) DEFAULT 'EUR' AFTER `total`,
    ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `tax_amount`,
    ADD COLUMN IF NOT EXISTS `paid_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `payment_date`;

-- Create KPI metrics cache table for performance
CREATE TABLE IF NOT EXISTS `kpi_metrics_cache` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `metric_key` VARCHAR(100) NOT NULL UNIQUE,
    `metric_value` TEXT,
    `metric_data` JSON NULL,
    `calculated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL,
    INDEX `idx_metric_key` (`metric_key`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create revenue tracking view (for faster queries)
CREATE OR REPLACE VIEW `revenue_summary` AS
SELECT 
    DATE_FORMAT(`date`, '%Y-%m') as month,
    COUNT(*) as invoice_count,
    SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as paid_revenue,
    SUM(CASE WHEN status IN ('sent', 'overdue') THEN total ELSE 0 END) as pending_revenue,
    SUM(total) as total_revenue
FROM invoices
WHERE status != 'cancelled'
GROUP BY DATE_FORMAT(`date`, '%Y-%m');

-- Create appointment statistics view
CREATE OR REPLACE VIEW `appointment_stats` AS
SELECT 
    DATE(`date`) as appointment_date,
    COUNT(*) as total_appointments,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_show
FROM appointments
GROUP BY DATE(`date`);

-- Add indices for better performance
ALTER TABLE `appointments` 
    ADD INDEX IF NOT EXISTS `idx_date_status` (`date`, `status`);

ALTER TABLE `invoices`
    ADD INDEX IF NOT EXISTS `idx_date_status` (`date`, `status`),
    ADD INDEX IF NOT EXISTS `idx_payment_date` (`payment_date`);

-- Insert default KPI settings
INSERT INTO `app_settings` (`skey`, `svalue`, `description`) VALUES
    ('kpi_refresh_interval', '30', 'KPI Dashboard refresh interval in seconds'),
    ('show_birthday_reminders', '1', 'Show birthday reminders on dashboard'),
    ('revenue_chart_months', '12', 'Number of months to show in revenue chart'),
    ('dashboard_layout', 'modern', 'Dashboard layout style')
ON DUPLICATE KEY UPDATE 
    `updated_at` = NOW();

-- Update system version
UPDATE `system_info` SET `key_value` = '1.2.0' WHERE `key_name` = 'db_version';

-- Log this migration
INSERT INTO `migration_log` (`filename`, `version`, `status`) VALUES
    ('003_add_kpi_features.sql', '1.2.0', 'success')
ON DUPLICATE KEY UPDATE 
    `executed_at` = NOW();