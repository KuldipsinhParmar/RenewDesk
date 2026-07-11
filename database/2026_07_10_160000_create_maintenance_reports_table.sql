-- ============================================================
--  Table: maintenance_reports
--  Desc : Per-project website maintenance report. Sections are
--         user-picked/ordered (drag-and-drop builder) so not every
--         project's report has the same sections.
-- ============================================================

CREATE TABLE IF NOT EXISTS `maintenance_reports` (
  `id`             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `project_id`     INT UNSIGNED  NOT NULL,
  `report_date`    DATE          NOT NULL,
  `period_start`   DATE          NULL,
  `period_end`     DATE          NULL,
  `prepared_by`    VARCHAR(150),
  `reviewed_by`    VARCHAR(150),
  `overall_health` ENUM('good','fair','needs_attention') DEFAULT 'good',
  `status`         ENUM('draft','final') DEFAULT 'draft',
  `sections`       LONGTEXT      NOT NULL,
  `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_maintenance_reports_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
