-- Procurement vendor field updates
-- Run this against your existing database AFTER the base schema has been imported.

START TRANSACTION;

ALTER TABLE `procurement_requests`
    ADD COLUMN `vendor_id` INT NULL AFTER `department`,
    ADD COLUMN `vendor_name` VARCHAR(255) NULL AFTER `vendor_id`,
    ADD COLUMN `vendor_email` VARCHAR(255) NULL AFTER `vendor_name`,
    ADD COLUMN `vendor_phone` VARCHAR(50) NULL AFTER `vendor_email`,
    ADD COLUMN `vendor_address` VARCHAR(255) NULL AFTER `vendor_phone`;

COMMIT;
