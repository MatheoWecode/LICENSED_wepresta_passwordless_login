<?php

declare(strict_types=1);

/**
 * =============================================================================
 * WEDEV UI - Smarty Function: wedev_badge
 * =============================================================================
 * Affiche un badge Bootstrap.
 *
 * Usage:
 *   {wedev_badge text="New" variant="success"}
 *   {wedev_badge text="Sale" variant="danger" pill=true}
 *   {wedev_badge text="5" variant="primary" pill=true class="ml-2"}
 *
 * Paramètres:
 *   - text (string, required): Texte du badge
 *   - variant (string): Variante Bootstrap - 'primary', 'secondary', 'success', 'danger', etc.
 *   - pill (bool): Utiliser le style pill (arrondi)
 *   - class (string): Classes CSS additionnelles
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
function smarty_function_wedev_badge(array $params, Smarty_Internal_Template $template): string
{
    // Paramètres
    $text = $params['text'] ?? '';
    $variant = $params['variant'] ?? 'primary';
    $pill = isset($params['pill']) && $params['pill'];
    $class = $params['class'] ?? '';

    if (empty($text)) {
        return '';
    }

    // Validation de la variante
    $validVariants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
    if (!in_array($variant, $validVariants, true)) {
        $variant = 'primary';
    }

    // Construction des classes
    $badgeClass = 'badge badge-' . htmlspecialchars($variant, ENT_QUOTES, 'UTF-8');

    if ($pill) {
        $badgeClass .= ' badge-pill';
    }

    if ($class) {
        $badgeClass .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    }

    return sprintf(
        '<span class="%s">%s</span>',
        $badgeClass,
        htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
    );
}





