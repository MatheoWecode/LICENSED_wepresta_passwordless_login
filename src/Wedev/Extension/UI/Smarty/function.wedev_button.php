<?php

declare(strict_types=1);

/**
 * =============================================================================
 * WEDEV UI - Smarty Function: wedev_button
 * =============================================================================
 * Affiche un bouton stylisé conforme au design Hummingbird.
 *
 * Usage:
 *   {wedev_button label="Add to cart" type="primary" icon="shopping_cart"}
 *   {wedev_button label="Learn more" href="/page" type="outline-primary"}
 *   {wedev_button label="Delete" type="danger" icon="delete" size="sm"}
 *
 * Paramètres:
 *   - label (string, required): Texte du bouton
 *   - type (string): Type Bootstrap - 'primary', 'secondary', 'success', 'danger', 'outline-*'
 *   - icon (string): Nom de l'icône Material Icons (optionnel)
 *   - href (string): URL - si fourni, génère un <a> au lieu d'un <button>
 *   - size (string): Taille - 'sm', 'md', 'lg'
 *   - class (string): Classes CSS additionnelles
 *   - disabled (bool): Désactiver le bouton
 *   - attrs (string): Attributs HTML additionnels (ex: 'data-action="submit"')
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
function smarty_function_wedev_button(array $params, Smarty_Internal_Template $template): string
{
    // Paramètres
    $label = $params['label'] ?? '';
    $type = $params['type'] ?? 'primary';
    $icon = $params['icon'] ?? '';
    $href = $params['href'] ?? '';
    $size = $params['size'] ?? 'md';
    $class = $params['class'] ?? '';
    $disabled = isset($params['disabled']) && $params['disabled'];
    $attrs = $params['attrs'] ?? '';

    // Validation du type
    $validTypes = [
        'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'link',
        'outline-primary', 'outline-secondary', 'outline-success', 'outline-danger',
        'outline-warning', 'outline-info', 'outline-light', 'outline-dark',
    ];

    if (!in_array($type, $validTypes, true)) {
        $type = 'primary';
    }

    // Construction des classes
    $btnClass = 'btn btn-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8');

    // Classe de taille
    if ($size === 'sm') {
        $btnClass .= ' btn-sm';
    } elseif ($size === 'lg') {
        $btnClass .= ' btn-lg';
    }

    // Classes additionnelles
    if ($class) {
        $btnClass .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    }

    // Icône
    $iconHtml = '';
    if ($icon) {
        $iconSize = $size === 'sm' ? '16px' : ($size === 'lg' ? '22px' : '18px');
        $iconHtml = sprintf(
            '<i class="material-icons" style="font-size: %s; vertical-align: middle; margin-right: 4px;">%s</i>',
            $iconSize,
            htmlspecialchars($icon, ENT_QUOTES, 'UTF-8')
        );
    }

    // Label
    $labelHtml = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    // Attributs additionnels
    $attrsHtml = $attrs ? ' ' . $attrs : '';

    // Génération du bouton ou du lien
    if ($href) {
        // Lien <a>
        $disabledClass = $disabled ? ' disabled' : '';
        $disabledAttr = $disabled ? ' tabindex="-1" aria-disabled="true"' : '';

        return sprintf(
            '<a href="%s" class="%s%s"%s%s>%s%s</a>',
            htmlspecialchars($href, ENT_QUOTES, 'UTF-8'),
            $btnClass,
            $disabledClass,
            $disabledAttr,
            $attrsHtml,
            $iconHtml,
            $labelHtml
        );
    }

    // Bouton <button>
    $disabledAttr = $disabled ? ' disabled' : '';

    return sprintf(
        '<button type="button" class="%s"%s%s>%s%s</button>',
        $btnClass,
        $disabledAttr,
        $attrsHtml,
        $iconHtml,
        $labelHtml
    );
}





