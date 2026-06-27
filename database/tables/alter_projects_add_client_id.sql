-- Run AFTER 10_clients.sql
-- Adds client_id FK to projects table

ALTER TABLE `projects`
  ADD COLUMN `client_id` INT UNSIGNED NULL AFTER `id`,
  ADD CONSTRAINT `fk_projects_client`
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;
