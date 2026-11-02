CREATE TABLE IF NOT EXISTS admin_notification_reads (
    admin_id INT UNSIGNED NOT NULL,
    notification_type ENUM('stories', 'corrections', 'volunteers') NOT NULL,
    last_read_id INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id, notification_type),
    KEY idx_admin_notification_reads_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
