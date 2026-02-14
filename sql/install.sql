-- Module Starter - Installation SQL
-- Creates the module's database tables

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_passwordless_login` (
    `id_wepresta_passwordless_login` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `position` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_wepresta_passwordless_login`),
    KEY `idx_active` (`active`),
    KEY `idx_position` (`position`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multi-language support (if needed)
-- CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_passwordless_login_lang` (
--     `id_wepresta_passwordless_login` INT(11) UNSIGNED NOT NULL,
--     `id_lang` INT(11) UNSIGNED NOT NULL,
--     `name` VARCHAR(255) NOT NULL,
--     `description` TEXT,
--     PRIMARY KEY (`id_wepresta_passwordless_login`, `id_lang`),
--     KEY `idx_lang` (`id_lang`)
-- ) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multi-shop support (if needed)
-- CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_passwordless_login_shop` (
--     `id_wepresta_passwordless_login` INT(11) UNSIGNED NOT NULL,
--     `id_shop` INT(11) UNSIGNED NOT NULL,
--     `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
--     PRIMARY KEY (`id_wepresta_passwordless_login`, `id_shop`),
--     KEY `idx_shop` (`id_shop`)
-- ) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
