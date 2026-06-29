-- ============================================================
--  Table: settings
--  Desc : App-wide config (SMTP, reminder days, admin email)
-- ============================================================

CREATE TABLE IF NOT EXISTS `settings` (
  `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `key`        VARCHAR(100)  NOT NULL UNIQUE,
  `value`      TEXT,
  `label`      VARCHAR(200),
  `updated_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Default settings seed ──
INSERT INTO `settings` (`key`, `value`, `label`) VALUES
('remind_days',        '30,15,7,1',      'Remind X days before expiry (comma separated)'),
('app_name',           'RenewDesk',      'Application Name'),
('timezone',           'Asia/Kolkata',   'Timezone');
