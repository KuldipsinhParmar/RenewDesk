-- Run AFTER create_countries_table and alter_projects_add_logo_url
ALTER TABLE `projects`
  ADD COLUMN `country_id` SMALLINT UNSIGNED DEFAULT NULL AFTER `logo_url`,
  ADD CONSTRAINT `fk_projects_country` FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE SET NULL;
