-- ============================================================
--  Table: clients
--  Desc : One record per managed client/company
-- ============================================================

CREATE TABLE IF NOT EXISTS `clients` (
  `id`          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(200)  NOT NULL,
  `company`     VARCHAR(200),
  `email`       VARCHAR(200),
  `phone`       VARCHAR(50),
  `notes`       TEXT,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
