CREATE TABLE IF NOT EXISTS `invoices` (
  `id`              INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `invoice_number`  VARCHAR(50)   NOT NULL UNIQUE,
  `client_id`       INT UNSIGNED  NOT NULL,
  `project_id`      INT UNSIGNED  NULL,
  `invoice_date`    DATE          NOT NULL,
  `due_date`        DATE          NULL,
  `subtotal`        DECIMAL(10,2) DEFAULT 0.00,
  `discount`        DECIMAL(10,2) DEFAULT 0.00,
  `grand_total`     DECIMAL(10,2) DEFAULT 0.00,
  `currency`        VARCHAR(10)   DEFAULT 'INR',
  `status`          ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
  `notes`           TEXT,
  `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_invoice_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoice_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
