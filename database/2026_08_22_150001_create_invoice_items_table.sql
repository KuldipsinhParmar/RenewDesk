CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `invoice_id`   INT UNSIGNED  NOT NULL,
  `description`  VARCHAR(255)  NOT NULL,
  `qty`          DECIMAL(10,2) DEFAULT 1,
  `price`        DECIMAL(10,2) DEFAULT 0,
  `total`        DECIMAL(10,2) DEFAULT 0,
  `source_type`  ENUM('domain','hosting','maintenance','manual') DEFAULT 'manual',
  `source_id`    INT UNSIGNED  NULL,
  `sort_order`   INT UNSIGNED  DEFAULT 0,
  CONSTRAINT `fk_invoice_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
