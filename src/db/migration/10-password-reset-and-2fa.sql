-- Migration 10: Password Reset tokens and 2FA settings

-- Make password column nullable to support no-password accounts
ALTER TABLE `users` MODIFY COLUMN `password` VARCHAR(64) NULL;

-- Create password reset tokens table
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `user_id` VARCHAR(36) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add password_reset_enabled config (default 0 - disabled)
INSERT IGNORE INTO `site_config` (`config_key`, `config_value`) VALUES ('password_reset_enabled', '0');

-- Add 2fa_mandatory config (default 0 - not mandatory)
INSERT IGNORE INTO `site_config` (`config_key`, `config_value`) VALUES ('2fa_mandatory', '0');
