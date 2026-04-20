-- ============================================================
--  Table: tasks
--  Desc : Daily work-log entries per project — date, title, hours
-- ============================================================

CREATE TABLE IF NOT EXISTS `tasks` (
  `id`          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT UNSIGNED  NOT NULL,
  `task_date`   DATE          NOT NULL,
  `task_title`  VARCHAR(300)  NOT NULL,
  `hours`       DECIMAL(5,2)  NOT NULL DEFAULT 0,
  `notes`       TEXT,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
