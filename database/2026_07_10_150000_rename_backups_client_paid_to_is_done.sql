-- Backups don't track client payment; they track whether the backup cycle was completed.
-- Rename client_paid -> is_done on the backups table to reflect that.
ALTER TABLE `backups` CHANGE COLUMN `client_paid` `is_done` TINYINT(1) DEFAULT 0 AFTER `storage_location`;
