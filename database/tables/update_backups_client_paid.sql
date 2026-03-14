ALTER TABLE `backups` ADD COLUMN `client_paid` TINYINT(1) DEFAULT 0 AFTER `storage_location`;
