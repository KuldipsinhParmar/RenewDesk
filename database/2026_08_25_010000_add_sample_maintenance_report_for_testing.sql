-- ============================================================
--  Seed: sample maintenance_reports row
--  Desc : One-off test-data insert so the "Last Backup Date"
--         (date+time) and "Middle Company" logo features can be
--         verified in the UI without going through the report
--         builder by hand. Safe to delete after testing.
-- ============================================================

INSERT INTO `maintenance_reports`
  (`project_id`, `website_url`, `report_date`, `period_start`, `period_end`,
   `prepared_by`, `reviewed_by`, `partner_name`, `partner_logo_url`,
   `overall_health`, `status`, `sections`)
VALUES
  (7, 'https://aaryasalon.example.com', '2026-08-25', '2026-08-01', '2026-08-25',
   'Kuldipsinh Parmar', 'Test Reviewer', 'Renew Partners LLP',
   'assets/uploads/logos/logo_b37ae3860642f195.jpg',
   'good', 'draft',
   '[
     {
       "instance_id": "cms_version-smpl01",
       "key": "cms_version",
       "title": "WordPress Version Used",
       "enabled": true,
       "fields": {
         "current_version": "WP 6.8.1",
         "theme_name": "Astra",
         "theme_version": "4.6.2",
         "php_mysql_compat_checked": "Yes"
       }
     },
     {
       "instance_id": "backup_status-smpl02",
       "key": "backup_status",
       "title": "Backup Status",
       "enabled": true,
       "fields": {
         "rows": [
           { "backup_type": "Full Site (Files + DB)", "frequency": "Daily", "storage_location": "Google Drive", "last_backup_date": "2026-08-24T23:30", "status": "OK" },
           { "backup_type": "Database Only", "frequency": "Weekly", "storage_location": "Local Server", "last_backup_date": "2026-08-20T02:15", "status": "OK" }
         ]
       }
     }
   ]');
