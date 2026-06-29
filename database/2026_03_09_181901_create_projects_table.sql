-- ============================================================
--  Table: projects
--  Desc : Core entity — one record per managed project
-- ============================================================

CREATE TABLE IF NOT EXISTS `projects` (
  `id`          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(200)  NOT NULL,
  `description` TEXT,
  `status`      ENUM('active','expired','cancelled') DEFAULT 'active',
  `notes`       TEXT,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
