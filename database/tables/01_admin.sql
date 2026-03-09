-- ============================================================
--  Table: admin
--  Desc : Single admin login (no staff / client roles)
-- ============================================================

CREATE TABLE IF NOT EXISTS `admin` (
  `id`         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100)    NOT NULL,
  `email`      VARCHAR(150)    NOT NULL UNIQUE,
  `password`   VARCHAR(255)    NOT NULL,   -- bcrypt hashed
  `created_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seed: default admin (password: Admin@123 — change on first login) ──
INSERT INTO `admin` (`name`, `email`, `password`) VALUES
(
  'Admin',
  'admin@renewdesk.local',
  '$2y$12$mEumWTQsQhmxI8/tm7o9sODIHfCDfvIMUuBS8.8jznwRIESoRsLHm'
);
