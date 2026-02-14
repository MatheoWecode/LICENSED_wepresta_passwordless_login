<?php

declare(strict_types=1);

/**
 * =============================================================================
 * WEDEV UI - Smarty Modifier: wedev_truncate
 * =============================================================================
 * Tronque un texte à une longueur donnée avec ellipsis.
 *
 * Usage:
 *   {$text|wedev_truncate:50}
 *   {$description|wedev_truncate:100:'...'}
 *   {$title|wedev_truncate:30:'…':true}
 *
 * Paramètres:
 *   - length (int): Longueur maximale (défaut: 50)
 *   - suffix (string): Suffixe à ajouter (défaut: '...')
 *   - breakWord (bool): Couper au milieu d'un mot (défaut: false)
 * =============================================================================
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param string $string
 * @param int $length
 * @param string $suffix
 * @param bool $breakWord
 * @return string
 */
function smarty_modifier_wedev_truncate(
    string $string,
    int $length = 50,
    string $suffix = '...',
    bool $breakWord = false
): string {
    if (mb_strlen($string, 'UTF-8') <= $length) {
        return $string;
    }

    $length -= mb_strlen($suffix, 'UTF-8');

    if ($length <= 0) {
        return $suffix;
    }

    $truncated = mb_substr($string, 0, $length, 'UTF-8');

    // Si on ne coupe pas les mots, on cherche le dernier espace
    if (!$breakWord) {
        $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
        if ($lastSpace !== false && $lastSpace > $length * 0.5) {
            $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
        }
    }

    return rtrim($truncated) . $suffix;
}





