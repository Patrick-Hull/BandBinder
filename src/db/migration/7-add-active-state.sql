-- Migration 7: Add Active/Deactive state

ALTER TABLE `charts`
    ADD COLUMN `isActive` TINYINT NULL DEFAULT 1 AFTER `pdfPath`;