-- Migration 004: Add Treatment Tracking Features
-- This migration adds tables for detailed treatment tracking and history

-- Treatment Plans Table
CREATE TABLE IF NOT EXISTS `treatment_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `status` ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    INDEX `idx_patient` (`patient_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Treatment Sessions Table
CREATE TABLE IF NOT EXISTS `treatment_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `treatment_plan_id` INT NULL,
    `patient_id` INT NOT NULL,
    `appointment_id` INT NULL,
    `date` DATE NOT NULL,
    `duration_minutes` INT DEFAULT 30,
    `therapist_notes` TEXT,
    `exercises_performed` JSON NULL,
    `patient_feedback` TEXT,
    `pain_level_before` INT NULL COMMENT 'Pain scale 0-10',
    `pain_level_after` INT NULL COMMENT 'Pain scale 0-10',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`treatment_plan_id`) REFERENCES `treatment_plans`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
    INDEX `idx_patient` (`patient_id`),
    INDEX `idx_plan` (`treatment_plan_id`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exercise Library Table
CREATE TABLE IF NOT EXISTS `exercise_library` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `category` VARCHAR(100),
    `animal_types` JSON NULL COMMENT 'Array of suitable animal types',
    `description` TEXT,
    `instructions` TEXT,
    `duration_minutes` INT,
    `difficulty` ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    `equipment_needed` TEXT,
    `video_url` VARCHAR(500),
    `image_url` VARCHAR(500),
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_name` (`name`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Patient Progress Tracking
CREATE TABLE IF NOT EXISTS `patient_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `metric_type` VARCHAR(100) NOT NULL COMMENT 'e.g., weight, flexibility, strength',
    `metric_value` DECIMAL(10,2),
    `metric_unit` VARCHAR(20),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    INDEX `idx_patient` (`patient_id`),
    INDEX `idx_date` (`date`),
    INDEX `idx_metric` (`metric_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample exercises
INSERT INTO `exercise_library` (`name`, `category`, `animal_types`, `description`, `instructions`, `duration_minutes`, `difficulty`) VALUES
('Passive Gelenkmobilisation', 'Mobilisation', '["Hund", "Katze"]', 'Sanfte passive Bewegung der Gelenke', 'Langsame, kontrollierte Bewegungen in alle physiologischen Richtungen', 10, 'easy'),
('Balanceboard Training', 'Balance', '["Hund"]', 'Training auf instabiler Unterlage', 'Patient steht auf Balanceboard, langsam Gewichtsverlagerung üben', 5, 'medium'),
('Cavaletti Training', 'Koordination', '["Hund", "Pferd"]', 'Schritt über niedrige Hindernisse', 'Langsames Übersteigen von Cavaletti-Stangen in angepasster Höhe', 15, 'medium'),
('Massage Therapie', 'Entspannung', '["Hund", "Katze", "Pferd"]', 'Therapeutische Massage zur Entspannung', 'Sanfte kreisende Bewegungen entlang der Muskulatur', 20, 'easy'),
('Unterwasserlaufband', 'Hydrotherapie', '["Hund"]', 'Gelenkschonendes Gangtraining im Wasser', 'Angepasste Geschwindigkeit und Wasserhöhe je nach Therapieziel', 20, 'hard')
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Update system version
UPDATE `system_info` SET `key_value` = '1.3.0' WHERE `key_name` = 'db_version';

-- Log this migration
INSERT INTO `migration_log` (`filename`, `version`, `status`) VALUES
    ('004_add_treatment_tracking.sql', '1.3.0', 'success')
ON DUPLICATE KEY UPDATE 
    `executed_at` = NOW();