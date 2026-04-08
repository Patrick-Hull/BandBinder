-- Migration 4: Store page assignments on chart PDF parts
-- Adds a JSON column to remember which pages were assigned to each instrument
-- so the split modal can restore selections when re-opened.

ALTER TABLE `chart__pdf_parts`
    ADD COLUMN `pages` JSON NULL AFTER `pdfPath`;
