CREATE TABLE users (
                       id VARCHAR(36) NOT NULL PRIMARY KEY,
                       username VARCHAR(100) NOT NULL UNIQUE,
                       password VARCHAR(64) NOT NULL,
                       email VARCHAR(255) NOT NULL UNIQUE,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       totpEnabled TINYINT DEFAULT 0,
                       totpSecret VARCHAR(26) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (id, username, password, email) VALUES ('d5423adf-2d62-46fa-87b7-fde63fc7cfca', 'admin', '$2a$12$gL5b4oQxU2cMpzYH2JGElurPb0JyfI8zL/yTWJEgAWhS2WsVwVLbi', 'admin@admin.com');

CREATE TABLE `log`(
    id VARCHAR (36) NOT NULL PRIMARY KEY,
    idUser VARCHAR(36) NOT NULL,
    idLogType VARCHAR(255) NOT NULL,
    logTime INT NOT NULL,
    pageName VARCHAR(128) NOT NULL,
    ipv4 VARCHAR(15) NOT NULL
);

CREATE TABLE `bandbinder`.`instrument__types` (`idInstrument` VARCHAR(36) NOT NULL , `idInstrumentFamily` VARCHAR(36) NULL , `instrumentName` VARCHAR(64) NOT NULL, `sortOrder` INT NOT NULL DEFAULT '0' , PRIMARY KEY (`idInstrument`)) ENGINE = InnoDB;
CREATE TABLE `bandbinder`.`instrument__families` (`idInstrumentFamily` VARCHAR(36) NOT NULL , `instrumentFamilyName` VARCHAR(64) NOT NULL , PRIMARY KEY (`idInstrumentFamily`)) ENGINE = InnoDB;
CREATE TABLE `bandbinder`.`site__permissions` (`permissionTypeHtml` varchar(64) NOT NULL, `permissionTypeName` varchar(64) NOT NULL, `permissionGroupHtml` varchar(64) NOT NULL, PRIMARY KEY (`permissionTypeHtml`)) ENGINE=InnoDB;
CREATE TABLE `bandbinder`.`site__permissionGroups` (`permissionGroupHtml` VARCHAR(64) NOT NULL , `permissionGroupName` VARCHAR(64) NOT NULL , PRIMARY KEY (`permissionGroupHtml`)) ENGINE = InnoDB;
CREATE TABLE `bandbinder`.`users__permissions` (`idUserPermission` varchar(36) NOT NULL, `idUser` varchar(36) NOT NULL, `permissionType` enum('group','individual', 'userType') NOT NULL, `permissionValueHtml` varchar(64) NOT NULL, PRIMARY KEY (`idUserPermission`)) ENGINE=InnoDB;
CREATE TABLE `bandbinder`.`link__user_instrument` (`idLink` varchar(36) NOT NULL,`idUser` varchar(36) NOT NULL, `idInstrument` varchar(36) NOT NULL, PRIMARY KEY (`idLink`)) ENGINE=InnoDB





INSERT INTO `bandbinder`.`site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('users', 'Users');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.view', 'View Users', 'users');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.create', 'Create User', 'users');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.edit', 'Edit User', 'users');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.editPermissions', 'Edit User Permissions', 'users');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('users.delete', 'Delete User', 'users');

INSERT INTO `bandbinder`.`site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('instruments', 'Instruments');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instruments.view', 'View Instrument', 'instruments');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instruments.create', 'Create Instrument', 'instruments');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instruments.edit', 'Delete Instrument', 'instruments');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instruments.delete', 'Edit Instrument', 'instruments');

INSERT INTO `bandbinder`.`site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('instrumentFamilies', 'Instrument Families');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instrumentFamilies.view', 'View Instrument Family', 'instrumentFamilies');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instrumentFamilies.create', 'Create Instrument Family', 'instrumentFamilies');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instrumentFamilies.delete', 'Delete Instrument Family', 'instrumentFamilies');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('instrumentFamilies.edit', 'Edit Instrument Family', 'instrumentFamilies');

INSERT INTO `bandbinder`.`site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('charts', 'Charts');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.view', 'View Chart', 'charts');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.viewAll', 'View Chart', 'charts');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.create', 'Create Chart', 'charts');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.edit', 'Edit Chart', 'charts');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('charts.delete', 'Delete Chart', 'charts');

INSERT INTO `bandbinder`.`site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('setlists', 'Setlists');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('setlists.view', 'View Setlists', 'setlists');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('setlists.create', 'Create Setlist', 'setlists');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('setlists.edit', 'Edit Setlist', 'setlists');
INSERT INTO `bandbinder`.`site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('setlists.delete', 'Delete Setlist', 'setlists');