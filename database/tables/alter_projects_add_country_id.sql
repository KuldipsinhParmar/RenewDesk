-- Run AFTER 11_countries.sql
ALTER TABLE `projects`
  ADD COLUMN `country_id` SMALLINT UNSIGNED DEFAULT NULL AFTER `logo_url`,
  ADD CONSTRAINT `fk_projects_country` FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE SET NULL;
