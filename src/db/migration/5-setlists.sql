-- Migration 5: Setlists
-- Adds duration field to charts, setlist tables, and permissions.

-- Duration in seconds (e.g. 210 = 3:30)
ALTER TABLE `charts`
    ADD COLUMN IF NOT EXISTS `duration` SMALLINT UNSIGNED NULL AFTER `bpm`;

-- Setlists
CREATE TABLE IF NOT EXISTS `setlists` (
    `idSetlist`   VARCHAR(36)  NOT NULL PRIMARY KEY,
    `setlistName` VARCHAR(255) NOT NULL,
    `performedAt` DATE         NULL,
    `notes`       TEXT         NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sets within a setlist (e.g. "Set 1", "Set 2", "Encore")
CREATE TABLE IF NOT EXISTS `setlist__sets` (
    `idSet`     VARCHAR(36)  NOT NULL PRIMARY KEY,
    `idSetlist` VARCHAR(36)  NOT NULL,
    `setName`   VARCHAR(100) NOT NULL DEFAULT 'Set 1',
    `sortOrder` INT          NULL,
    FOREIGN KEY (`idSetlist`) REFERENCES `setlists`(`idSetlist`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Charts assigned to a set
CREATE TABLE IF NOT EXISTS `setlist__set_charts` (
    `idSetChart` VARCHAR(36) NOT NULL PRIMARY KEY,
    `idSet`      VARCHAR(36) NOT NULL,
    `idChart`    VARCHAR(36) NOT NULL,
    `sortOrder`  INT         NULL,
    FOREIGN KEY (`idSet`)   REFERENCES `setlist__sets`(`idSet`)  ON DELETE CASCADE,
    FOREIGN KEY (`idChart`) REFERENCES `charts`(`idChart`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissions (using the correct table names: site__permissionGroups / site__permissions)
INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`)
    VALUES ('setlists', 'Setlists');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('setlists.view', 'View Setlists', 'setlists');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('setlists.create', 'Create Setlist', 'setlists');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('setlists.edit', 'Edit Setlist', 'setlists');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('setlists.delete', 'Delete Setlist', 'setlists');
