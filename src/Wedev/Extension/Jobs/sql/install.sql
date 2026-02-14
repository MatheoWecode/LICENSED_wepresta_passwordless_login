-- Extension Jobs - Table de file d'attente
-- Exécuté automatiquement lors de l'installation du module

CREATE TABLE IF NOT EXISTS `PREFIX_wedev_jobs` (
    `id_job` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_class` VARCHAR(255) NOT NULL,
    `payload` TEXT NOT NULL,
    `status` ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `retry_delay` INT UNSIGNED NOT NULL DEFAULT 60,
    `timeout` INT UNSIGNED NOT NULL DEFAULT 300,
    `last_error` TEXT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- 🔥 COLONNES OBLIGATOIRES PRESTASHOP
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_job`),
    INDEX `idx_status_scheduled` (`status`, `scheduled_at`),
    INDEX `idx_job_class` (`job_class`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

