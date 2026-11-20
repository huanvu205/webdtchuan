CREATE TABLE IF NOT EXISTS login_devices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    account_type ENUM('customer','admin') NOT NULL,
    token_hash CHAR(64) NOT NULL,
    device_name VARCHAR(160) NOT NULL,
    operating_system VARCHAR(80) NOT NULL,
    browser VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    session_id VARCHAR(128) DEFAULT NULL,
    last_login_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    revoked_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_login_devices_token (token_hash),
    KEY idx_login_devices_user (user_id, account_type, revoked_at),
    KEY idx_login_devices_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_device_otps (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    account_type ENUM('customer','admin') NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    device_token_hash CHAR(64) NOT NULL,
    device_name VARCHAR(160) NOT NULL,
    operating_system VARCHAR(80) NOT NULL,
    browser VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    resend_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_sent_at DATETIME NOT NULL,
    consumed_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_device_otps_user (user_id, account_type, created_at),
    KEY idx_login_device_otps_expiry (expires_at, consumed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    account_type ENUM('customer','admin') DEFAULT NULL,
    login_identifier VARCHAR(190) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    event_type VARCHAR(32) NOT NULL,
    device_name VARCHAR(160) NOT NULL,
    operating_system VARCHAR(80) NOT NULL,
    browser VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_events_user (user_id, created_at),
    KEY idx_login_events_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
