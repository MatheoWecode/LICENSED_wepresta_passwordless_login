<?php

declare(strict_types=1);

/**
 * =============================================================================
 * WEDEV UI - Smarty Function: wedev_icon
 * =============================================================================
 * Affiche une icône Material Icons.
 *
 * Usage:
 *   {wedev_icon name="check" size="md"}
 *   {wedev_icon name="shopping_cart" size="lg" class="text-primary"}
 *
 * Paramètres:
 *   - name (string, required): Nom de l'icône Material Icons
 *   - size (string): Taille - 'sm' (16px), 'md' (20px), 'lg' (24px), 'xl' (32px)
 *   - class (string): Classes CSS additionnelles
 *   - style (string): Styles CSS inline additionnels
 * =============================================================================
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param array<string, mixed> $params
 * @param Smarty_Internal_Template $template
 * @return string
 */
function smarty_function_wedev_icon(array $params, Smarty_Internal_Template $template): string
{
    // Paramètres
    $name = $params['name'] ?? 'help';
    $size = $params['size'] ?? 'md';
    $class = $params['class'] ?? '';
    $style = $params['style'] ?? '';

    // Mapping des tailles
    $sizes = [
        'xs' => '14px',
        'sm' => '16px',
        'md' => '20px',
        'lg' => '24px',
        'xl' => '32px',
        'xxl' => '48px',
    ];

    $fontSize = $sizes[$size] ?? '20px';

    // Construction du style
    $styleAttr = "font-size: {$fontSize}; vertical-align: middle;";
    if ($style) {
        $styleAttr .= ' ' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8');
    }

    // Construction de la classe
    $classAttr = 'material-icons';
    if ($class) {
        $classAttr .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    }

    return sprintf(
        '<i class="%s" style="%s">%s</i>',
        $classAttr,
        $styleAttr,
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
    );
}





