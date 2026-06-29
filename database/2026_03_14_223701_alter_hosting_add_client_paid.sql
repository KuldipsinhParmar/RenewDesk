-- Migration to add client_paid column to hosting table
ALTER TABLE `hosting` ADD COLUMN `client_paid` TINYINT(1) DEFAULT 0 AFTER `price`;
