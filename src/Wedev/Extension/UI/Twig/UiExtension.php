<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\UI\Twig;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

/**
 * WEDEV UI Extension - Fonctions Twig pour le back-office PrestaShop 9
 *
 * Fournit des helpers pour générer des composants UI natifs PrestaShop.
 * Utilise exclusivement les classes Bootstrap 4 / UIKit PS9.
 */
class UiExtension extends AbstractExtension
{
    public function getName(): string
    {
        return 'wedev_ui';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wedev_icon', [$this, 'renderIcon'], ['is_safe' => ['html']]),
            new TwigFunction('wedev_alert', [$this, 'renderAlert'], ['is_safe' => ['html']]),
            new TwigFunction('wedev_badge', [$this, 'renderBadge'], ['is_safe' => ['html']]),
            new TwigFunction('wedev_button', [$this, 'renderButton'], ['is_safe' => ['html']]),
            new TwigFunction('wedev_spinner', [$this, 'renderSpinner'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wedev_status', [$this, 'formatStatus'], ['is_safe' => ['html']]),
            new TwigFilter('wedev_truncate', [$this, 'truncate']),
        ];
    }

    /**
     * Render Material Icon
     *
     * @param string $name Icon name (e.g., 'settings', 'check_circle')
     * @param string $size Size: 'sm' (16px), 'md' (20px), 'lg' (24px), 'xl' (32px)
     * @param string $class Additional CSS classes
     */
    public function renderIcon(string $name, string $size = 'md', string $class = ''): string
    {
        $sizes = [
            'sm' => '16',
            'md' => '20',
            'lg' => '24',
            'xl' => '32',
        ];

        $fontSize = $sizes[$size] ?? '20';
        $classAttr = $class ? ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') : '';

        return sprintf(
            '<i class="material-icons%s" style="font-size: %spx; vertical-align: middle;">%s</i>',
            $classAttr,
            $fontSize,
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Render Bootstrap Alert
     *
     * @param string $message Alert message
     * @param string $type Alert type: 'success', 'info', 'warning', 'danger'
     * @param bool $dismissible Whether alert can be dismissed
     * @param string|null $icon Optional icon name
     */
    public function renderAlert(
        string $message,
        string $type = 'info',
        bool $dismissible = false,
        ?string $icon = null
    ): string {
        $validTypes = ['success', 'info', 'warning', 'danger', 'primary', 'secondary'];
        $type = in_array($type, $validTypes, true) ? $type : 'info';

        $dismissClass = $dismissible ? ' alert-dismissible fade show' : '';
        $dismissButton = $dismissible
            ? '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
            : '';

        $iconHtml = $icon
            ? $this->renderIcon($icon, 'md', 'mr-2') . ' '
            : '';

        return sprintf(
            '<div class="alert alert-%s%s" role="alert">%s%s%s</div>',
            htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            $dismissClass,
            $iconHtml,
            htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            $dismissButton
        );
    }

    /**
     * Render Bootstrap Badge
     *
     * @param string $text Badge text
     * @param string $variant Badge variant: 'primary', 'secondary', 'success', 'danger', 'warning', 'info'
     * @param bool $pill Use pill style (rounded)
     */
    public function renderBadge(string $text, string $variant = 'primary', bool $pill = false): string
    {
        $validVariants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        $variant = in_array($variant, $validVariants, true) ? $variant : 'primary';

        $pillClass = $pill ? ' badge-pill' : '';

        return sprintf(
            '<span class="badge badge-%s%s">%s</span>',
            htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'),
            $pillClass,
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Render Bootstrap Button
     *
     * @param string $label Button label
     * @param string $type Button type: 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'outline-*'
     * @param string|null $icon Optional icon name
     * @param string $size Size: 'sm', 'md', 'lg'
     * @param array<string, string> $attrs Additional HTML attributes
     */
    public function renderButton(
        string $label,
        string $type = 'primary',
        ?string $icon = null,
        string $size = 'md',
        array $attrs = []
    ): string {
        $sizeClass = match ($size) {
            'sm' => ' btn-sm',
            'lg' => ' btn-lg',
            default => '',
        };

        $iconHtml = $icon
            ? $this->renderIcon($icon, 'sm') . ' '
            : '';

        $attrsHtml = '';
        foreach ($attrs as $key => $value) {
            $attrsHtml .= sprintf(
                ' %s="%s"',
                htmlspecialchars($key, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }

        return sprintf(
            '<button type="button" class="btn btn-%s%s"%s>%s%s</button>',
            htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            $sizeClass,
            $attrsHtml,
            $iconHtml,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Render Bootstrap Spinner
     *
     * @param string $type Spinner type: 'border', 'grow'
     * @param string $size Size: 'sm', 'md'
     * @param string $variant Color variant
     */
    public function renderSpinner(string $type = 'border', string $size = 'md', string $variant = 'primary'): string
    {
        $type = $type === 'grow' ? 'grow' : 'border';
        $sizeClass = $size === 'sm' ? ' spinner-' . $type . '-sm' : '';

        return sprintf(
            '<div class="spinner-%s text-%s%s" role="status"><span class="sr-only">Loading...</span></div>',
            $type,
            htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'),
            $sizeClass
        );
    }

    /**
     * Format boolean status as badge
     *
     * @param bool $status Status value
     * @param array{true?: string, false?: string} $labels Custom labels
     */
    public function formatStatus(bool $status, array $labels = []): string
    {
        $defaultLabels = [true => 'Active', false => 'Inactive'];
        $labels = array_merge($defaultLabels, $labels);

        return $status
            ? $this->renderBadge($labels[true], 'success')
            : $this->renderBadge($labels[false], 'danger');
    }

    /**
     * Truncate text with ellipsis
     */
    public function truncate(string $text, int $length = 50, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
    }
}





