-- Migration 9: Add siteconfig permission group and permission

INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`) VALUES ('siteconfig', 'Site Config');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`) VALUES ('siteconfig', 'Manage Site Config', 'siteconfig');