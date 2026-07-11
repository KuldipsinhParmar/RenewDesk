-- ============================================================
--  Alter: maintenance_reports
--  Desc : Add website_url so the report can show a per-report
--         "Website URL" field (template field), independent of
--         the project's domain records.
-- ============================================================

ALTER TABLE `maintenance_reports`
  ADD COLUMN `website_url` VARCHAR(255) NULL AFTER `project_id`;
