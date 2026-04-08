CREATE TABLE IF NOT EXISTS `users` (
    `id`          VARCHAR(36)  NOT NULL PRIMARY KEY,
    `username`    VARCHAR(100) NOT NULL UNIQUE,
    `password`    VARCHAR(64)  NOT NULL,
    `email`       VARCHAR(255) NOT NULL UNIQUE,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `totpEnabled` TINYINT      DEFAULT 0,
    `totpSecret`  VARCHAR(26)  DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `users` (`id`, `username`, `password`, `email`)
    VALUES ('d5423adf-2d62-46fa-87b7-fde63fc7cfca', 'admin', '$2a$12$gL5b4oQxU2cMpzYH2JGElurPb0JyfI8zL/yTWJEgAWhS2WsVwVLbi', 'admin@admin.com');

CREATE TABLE IF NOT EXISTS `log` (
    `id`       VARCHAR(36)  NOT NULL PRIMARY KEY,
    `idUser`   VARCHAR(36)  NOT NULL,
    `idLogType` VARCHAR(255) NOT NULL,
    `logTime`  INT          NOT NULL,
    `pageName` VARCHAR(128) NOT NULL,
    `ipv4`     VARCHAR(15)  NOT NULL
);

CREATE TABLE IF NOT EXISTS `instrument__types` (
    `idInstrument`       VARCHAR(36) NOT NULL,
    `idInstrumentFamily` VARCHAR(36) NULL,
    `instrumentName`     VARCHAR(64) NOT NULL,
    `sortOrder`          INT         NOT NULL DEFAULT '0',
    PRIMARY KEY (`idInstrument`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `instrument__families` (
    `idInstrumentFamily`     VARCHAR(36) NOT NULL,
    `instrumentFamilyName`   VARCHAR(64) NOT NULL,
    PRIMARY KEY (`idInstrumentFamily`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `site__permissions` (
    `permissionTypeHtml`  VARCHAR(64) NOT NULL,
    `permissionTypeName`  VARCHAR(64) NOT NULL,
    `permissionGroupHtml` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`permissionTypeHtml`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `site__permissionGroups` (
    `permissionGroupHtml` VARCHAR(64) NOT NULL,
    `permissionGroupName` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`permissionGroupHtml`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `users__permissions` (
    `idUserPermission`   VARCHAR(36)                            NOT NULL,
    `idUser`             VARCHAR(36)                            NOT NULL,
    `permissionType`     ENUM('group','individual','userType')  NOT NULL,
    `permissionValueHtml` VARCHAR(64)                           NOT NULL,
    PRIMARY KEY (`idUserPermission`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `link__user_instrument` (
    `idLink`      VARCHAR(36) NOT NULL,
    `idUser`      VARCHAR(36) NOT NULL,
    `idInstrument` VARCHAR(36) NOT NULL,
    PRIMARY KEY (`idLink`)
) ENGINE=InnoDB;

INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('users', 'Users');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.view', 'View Users', 'users');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.create', 'Create User', 'users');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.edit', 'Edit User', 'users');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.editPermissions', 'Edit User Permissions', 'users');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.delete', 'Delete User', 'users');

INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('instruments', 'Instruments');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instruments.view', 'View Instrument', 'instruments');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instruments.create', 'Create Instrument', 'instruments');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instruments.edit', 'Edit Instrument', 'instruments');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instruments.delete', 'Delete Instrument', 'instruments');

INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('instrumentFamilies', 'Instrument Families');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instrumentFamilies.view', 'View Instrument Family', 'instrumentFamilies');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instrumentFamilies.create', 'Create Instrument Family', 'instrumentFamilies');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instrumentFamilies.delete', 'Delete Instrument Family', 'instrumentFamilies');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instrumentFamilies.edit', 'Edit Instrument Family', 'instrumentFamilies');

INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('charts', 'Charts');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.view', 'View My Charts', 'charts');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.viewAll', 'View All Charts', 'charts');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.create', 'Create Chart', 'charts');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.edit', 'Edit Chart', 'charts');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.delete', 'Delete Chart', 'charts');

INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('setlists', 'Setlists');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('setlists.view', 'View Setlists', 'setlists');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('setlists.create', 'Create Setlist', 'setlists');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('setlists.edit', 'Edit Setlist', 'setlists');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('setlists.delete', 'Delete Setlist', 'setlists');
