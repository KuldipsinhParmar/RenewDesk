-- ============================================================
--  Table: maintenance
--  Desc : Yearly AMC / maintenance contract per project
-- ============================================================

CREATE TABLE IF NOT EXISTS `maintenance` (
  `id`          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT UNSIGNED  NOT NULL,
  `start_date`  DATE          NOT NULL,
  `end_date`    DATE          NOT NULL,
  `price`       DECIMAL(10,2) DEFAULT 0.00,
  `currency`    VARCHAR(10)   DEFAULT 'INR',
  `status`      ENUM('active','expired','renewed') DEFAULT 'active',
  `notes`       TEXT,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_maintenance_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
