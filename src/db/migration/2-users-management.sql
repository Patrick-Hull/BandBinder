-- Add name columns to users table (IF NOT EXISTS prevents failure on re-run)
ALTER TABLE `users` ADD COLUMN `nameShort` VARCHAR(50)  NULL AFTER `email`;
ALTER TABLE `users` ADD COLUMN `nameFirst` VARCHAR(100) NULL AFTER `nameShort`;
ALTER TABLE `users` ADD COLUMN `nameLast`  VARCHAR(100) NULL AFTER `nameFirst`;

-- Backfill existing users: set nameShort = username where null
UPDATE `users` SET `nameShort` = `username` WHERE `nameShort` IS NULL;

-- Create user_types table
CREATE TABLE IF NOT EXISTS `user_types` (
    `idUserType`   VARCHAR(36) NOT NULL PRIMARY KEY,
    `userTypeName` VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create user_types__permissions link table
CREATE TABLE IF NOT EXISTS `user_types__permissions` (
    `idUserTypePermission` VARCHAR(36) NOT NULL PRIMARY KEY,
    `idUserType`           VARCHAR(36) NOT NULL,
    `permissionTypeHtml`   VARCHAR(64) NOT NULL,
    FOREIGN KEY (`idUserType`)         REFERENCES `user_types`(`idUserType`)             ON DELETE CASCADE,
    FOREIGN KEY (`permissionTypeHtml`) REFERENCES `site__permissions`(`permissionTypeHtml`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add 'userType' to the permissionType enum (safe to run multiple times — MODIFY is idempotent)
ALTER TABLE `users__permissions`
    MODIFY COLUMN `permissionType` ENUM('group','individual','userType') NOT NULL;

-- Add userTypes permission group and permissions
INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`)
    VALUES ('userTypes', 'User Types');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('userTypes.view', 'View User Types', 'userTypes');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('userTypes.create', 'Create User Type', 'userTypes');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('userTypes.edit', 'Edit User Type', 'userTypes');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('userTypes.delete', 'Delete User Type', 'userTypes');
