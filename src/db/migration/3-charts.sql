-- Create artists table
CREATE TABLE `artists` (
    `idArtist` VARCHAR(36) NOT NULL PRIMARY KEY,
    `artistName` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create arrangers table
CREATE TABLE `arrangers` (
    `idArranger` VARCHAR(36) NOT NULL PRIMARY KEY,
    `arrangerName` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create charts table
CREATE TABLE `charts` (
    `idChart` VARCHAR(36) NOT NULL PRIMARY KEY,
    `chartName` VARCHAR(255) NOT NULL,
    `idArtist` VARCHAR(36) NULL,
    `idArranger` VARCHAR(36) NULL,
    `bpm` INT NULL,
    `chartKey` VARCHAR(20) NULL,
    `notes` TEXT NULL,
    `pdfPath` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`idArtist`) REFERENCES `artists`(`idArtist`) ON DELETE SET NULL,
    FOREIGN KEY (`idArranger`) REFERENCES `arrangers`(`idArranger`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chart PDF parts (instrument-specific PDFs, either uploaded or split from master)
CREATE TABLE `chart__pdf_parts` (
    `idChartPdfPart` VARCHAR(36) NOT NULL PRIMARY KEY,
    `idChart` VARCHAR(36) NOT NULL,
    `idInstrument` VARCHAR(36) NULL,
    `pdfPath` VARCHAR(500) NOT NULL,
    FOREIGN KEY (`idChart`) REFERENCES `charts`(`idChart`) ON DELETE CASCADE,
    FOREIGN KEY (`idInstrument`) REFERENCES `instrument__types`(`idInstrument`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-user chart fields (personal annotations per chart)
CREATE TABLE `chart__user_fields` (
    `idChartUserField` VARCHAR(36) NOT NULL PRIMARY KEY,
    `idChart` VARCHAR(36) NOT NULL,
    `idUser` VARCHAR(36) NOT NULL,
    `starRating` TINYINT NULL,
    `privateNotes` TEXT NULL,
    `instrumentNotes` TEXT NULL,
    `familyNotes` TEXT NULL,
    FOREIGN KEY (`idChart`) REFERENCES `charts`(`idChart`) ON DELETE CASCADE,
    FOREIGN KEY (`idUser`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_chart_user` (`idChart`, `idUser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permission groups and permissions for artists
INSERT INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`)
    VALUES ('artists', 'Artists');
INSERT INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('artists.view', 'View Artists', 'artists');
INSERT INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('artists.create', 'Create Artist', 'artists');
INSERT INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('artists.edit', 'Edit Artist', 'artists');
INSERT INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('artists.delete', 'Delete Artist', 'artists');

-- Permission groups and permissions for arrangers
INSERT INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`)
    VALUES ('arrangers', 'Arrangers');
INSERT INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('arrangers.view', 'View Arrangers', 'arrangers');
INSERT INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('arrangers.create', 'Create Arranger', 'arrangers');
INSERT INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('arrangers.edit', 'Edit Arranger', 'arrangers');
INSERT INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('arrangers.delete', 'Delete Arranger', 'arrangers');
