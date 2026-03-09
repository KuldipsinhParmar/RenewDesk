-- ============================================================
--  Table: reminder_logs
--  Desc : Log every reminder email sent to admin
-- ============================================================

CREATE TABLE IF NOT EXISTS `reminder_logs` (
  `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED,
  `type`       ENUM('domain','hosting','maintenance','backup') NOT NULL,
  `sent_to`    VARCHAR(150),
  `subject`    VARCHAR(255),
  `message`    TEXT,
  `success`    TINYINT(1)    DEFAULT 1,
  `sent_at`    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
