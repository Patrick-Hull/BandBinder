-- Categories table
CREATE TABLE IF NOT EXISTS `chart__categories` (
    `idCategory` VARCHAR(36) NOT NULL PRIMARY KEY,
    `categoryName` VARCHAR(100) NOT NULL,
    `categoryColour` VARCHAR(7) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link table (many-to-many between charts and categories)
CREATE TABLE IF NOT EXISTS `link__chart_category` (
    `idChart` VARCHAR(36) NOT NULL,
    `idCategory` VARCHAR(36) NOT NULL,
    PRIMARY KEY (`idChart`, `idCategory`),
    FOREIGN KEY (`idChart`) REFERENCES `charts`(`idChart`) ON DELETE CASCADE,
    FOREIGN KEY (`idCategory`) REFERENCES `chart__categories`(`idCategory`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permission groups and permissions for categories
INSERT IGNORE INTO `site__permissionGroups` (`permissionGroupHtml`, `permissionGroupName`)
    VALUES ('categories', 'Categories');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('categories.view', 'View Categories', 'categories');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('categories.create', 'Create Category', 'categories');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('categories.edit', 'Edit Category', 'categories');
INSERT IGNORE INTO `site__permissions` (`permissionTypeHtml`, `permissionTypeName`, `permissionGroupHtml`)
    VALUES ('categories.delete', 'Delete Category', 'categories');