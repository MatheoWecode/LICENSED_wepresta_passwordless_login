-- Passwordless Login - Installation SQL

CREATE TABLE IF NOT EXISTS `PREFIX_passwordless_code` (
    `id_code` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT(11) UNSIGNED DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL,
    `code_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `attempts` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    `used` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_code`),
    KEY `idx_email_shop` (`email`, `id_shop`),
    KEY `idx_expires` (`expires_at`),
    KEY `idx_customer` (`id_customer`),
    KEY `idx_ip_date` (`ip_address`, `date_add`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_passwordless_social` (
    `id_social` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT(11) UNSIGNED NOT NULL,
    `provider` VARCHAR(50) NOT NULL,
    `provider_id` VARCHAR(255) NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_social`),
    UNIQUE KEY `idx_provider_id_shop` (`provider`, `provider_id`, `id_shop`),
    KEY `idx_customer_provider` (`id_customer`, `provider`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
