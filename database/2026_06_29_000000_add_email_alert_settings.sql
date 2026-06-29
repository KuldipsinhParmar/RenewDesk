-- Add email configuration and alert toggle settings
INSERT IGNORE INTO `settings` (`key`, `value`, `label`) VALUES
('email_from',    '',  'From Email Address for outgoing alerts'),
('email_cc',      '',  'CC Email Addresses (comma separated)'),
('alert_enabled', '1', 'Email Alerts Enabled (1 = on, 0 = off)');
