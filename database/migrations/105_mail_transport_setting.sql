-- Turns on the SMTP transport for database/notifications-cron.php. Credentials
-- (SMTP_HOST/USER/PASS) live in .env only, never here — the Settings page renders
-- every row back in plain text to anyone with platform.setting.update.
INSERT IGNORE INTO settings (`key`, `value`, `group`, `is_public`) VALUES
    ('mail_transport', '""', 'mail', 0);
