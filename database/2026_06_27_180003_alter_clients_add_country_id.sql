-- Run AFTER create_countries_table
-- If you previously ran the old version (which added country VARCHAR), first run:
--   ALTER TABLE `clients` DROP COLUMN `country`;

ALTER TABLE `clients`
  ADD COLUMN `country_id` SMALLINT UNSIGNED DEFAULT NULL AFTER `phone`,
  ADD CONSTRAINT `fk_clients_country` FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE SET NULL;
