-- Migration 001: Create System Tables for Version Tracking
-- This migration creates essential system tables for tracking app version and migration history

-- System Info Table - Stores application metadata
CREATE TABLE IF NOT EXISTS `system_info` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key_name` VARCHAR(100) NOT NULL UNIQUE,
    `key_value` TEXT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration Log Table - Tracks executed migrations
CREATE TABLE IF NOT EXISTS `migration_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `version` VARCHAR(20) NULL,
    `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `execution_time` DECIMAL(10,4) NULL COMMENT 'Execution time in seconds',
    `status` ENUM('success', 'failed', 'partial') NOT NULL DEFAULT 'success',
    `error_message` TEXT NULL,
    UNIQUE KEY `unique_filename` (`filename`),
    INDEX `idx_version` (`version`),
    INDEX `idx_executed_at` (`executed_at`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial system info
INSERT INTO `system_info` (`key_name`, `key_value`, `description`) VALUES
    ('db_version', '1.0.0', 'Current database schema version'),
    ('install_date', NOW(), 'Initial installation date'),
    ('last_update', NOW(), 'Last update timestamp')
ON DUPLICATE KEY UPDATE 
    `updated_at` = NOW();

-- Log this migration
INSERT INTO `migration_log` (`filename`, `version`, `status`) VALUES
    ('001_create_system_tables.sql', '1.0.0', 'success')
ON DUPLICATE KEY UPDATE 
    `executed_at` = NOW();