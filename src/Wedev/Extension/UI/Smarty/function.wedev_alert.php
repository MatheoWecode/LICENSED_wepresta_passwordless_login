<?php

declare(strict_types=1);

/**
 * =============================================================================
 * WEDEV UI - Smarty Function: wedev_alert
 * =============================================================================
 * Affiche une alerte Bootstrap.
 *
 * Usage:
 *   {wedev_alert message="Success!" type="success"}
 *   {wedev_alert message="Warning!" type="warning" icon="warning" dismissible=true}
 *
 * Paramètres:
 *   - message (string, required): Message de l'alerte
 *   - type (string): Type Bootstrap - 'success', 'info', 'warning', 'danger', 'primary', 'secondary'
 *   - icon (string): Nom de l'icône Material Icons (optionnel)
 *   - dismissible (bool): Permettre la fermeture de l'alerte
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
function smarty_function_wedev_alert(array $params, Smarty_Internal_Template $template): string
{
    // Paramètres
    $message = $params['message'] ?? '';
    $type = $params['type'] ?? 'info';
    $icon = $params['icon'] ?? '';
    $dismissible = isset($params['dismissible']) && $params['dismissible'];
    $class = $params['class'] ?? '';

    if (empty($message)) {
        return '';
    }

    // Validation du type
    $validTypes = ['success', 'info', 'warning', 'danger', 'primary', 'secondary', 'light', 'dark'];
    if (!in_array($type, $validTypes, true)) {
        $type = 'info';
    }

    // Construction des classes
    $alertClass = 'alert alert-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8');

    if ($dismissible) {
        $alertClass .= ' alert-dismissible fade show';
    }

    if ($class) {
        $alertClass .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    }

    // Icône automatique si non spécifiée
    if (!$icon) {
        $iconMap = [
            'success' => 'check_circle',
            'info' => 'info',
            'warning' => 'warning',
            'danger' => 'error',
            'primary' => 'info',
            'secondary' => 'info',
        ];
        $icon = $iconMap[$type] ?? '';
    }

    // Génération de l'icône
    $iconHtml = '';
    if ($icon) {
        $iconHtml = sprintf(
            '<i class="material-icons mr-2" style="font-size: 20px; vertical-align: middle;">%s</i>',
            htmlspecialchars($icon, ENT_QUOTES, 'UTF-8')
        );
    }

    // Bouton de fermeture
    $dismissButton = '';
    if ($dismissible) {
        $dismissButton = '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>';
    }

    // Message
    $messageHtml = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    return sprintf(
        '<div class="%s" role="alert">%s%s%s</div>',
        $alertClass,
        $iconHtml,
        $messageHtml,
        $dismissButton
    );
}





