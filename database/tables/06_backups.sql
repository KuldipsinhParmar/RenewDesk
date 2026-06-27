-- ============================================================
--  Table: backups
--  Desc : Backup schedule and tracking per project
-- ============================================================

CREATE TABLE IF NOT EXISTS `backups` (
  `id`               INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `project_id`       INT UNSIGNED  NOT NULL,
  `frequency`        ENUM('daily','weekly','monthly','manual') DEFAULT 'monthly',
  `last_backup`      DATE,
  `next_backup`      DATE,
  `storage_location` VARCHAR(255),
  `client_paid`      TINYINT(1)    DEFAULT 0,
  `notes`            TEXT,
  `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_backup_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
