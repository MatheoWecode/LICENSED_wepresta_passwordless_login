-- Extension Audit - Table de journal d'audit
-- Exécuté automatiquement lors de l'installation du module

CREATE TABLE IF NOT EXISTS `PREFIX_wedev_audit` (
    `id_audit` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NULL,
    `user_name` VARCHAR(150) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `old_values` LONGTEXT NULL,
    `new_values` LONGTEXT NULL,
    `context` TEXT NULL,
    `id_shop` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- 🔥 COLONNES OBLIGATOIRES PRESTASHOP
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_audit`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_shop` (`id_shop`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

