-- ============================================================
--  Alter: maintenance_reports
--  Desc : Add the "middle company" (reseller/partner/agency)
--         name + logo shown on a report's header. Set per report
--         at generate time, independent of the project's own logo.
-- ============================================================

ALTER TABLE `maintenance_reports`
  ADD COLUMN `partner_name` VARCHAR(150) DEFAULT NULL AFTER `reviewed_by`,
  ADD COLUMN `partner_logo_url` VARCHAR(500) DEFAULT NULL AFTER `partner_name`;
