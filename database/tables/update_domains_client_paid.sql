-- Migration to add client_paid column to domains table
ALTER TABLE `domains` ADD COLUMN `client_paid` TINYINT(1) DEFAULT 0 AFTER `price`;
