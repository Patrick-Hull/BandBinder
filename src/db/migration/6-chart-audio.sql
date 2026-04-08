-- Migration 6: Add audio file support to charts

ALTER TABLE `charts`
    ADD COLUMN `audioPath` VARCHAR(500) NULL AFTER `pdfPath`;
