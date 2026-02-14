<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Security;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Exception\ValidationException;

/**
 * Centralized input validation utilities.
 *
 * Provides secure validation and sanitization methods for common input types.
 * All methods are static for easy use throughout the module.
 *
 * @example
 * // Validate and sanitize a slug
 * $slug = InputValidator::slug($userInput);
 *
 * // Validate email
 * if (InputValidator::isEmail($email)) {
 *     // valid email
 * }
 *
 * // Sanitize HTML (strip dangerous tags)
 * $safeHtml = InputValidator::html($userHtml);
 *
 * // Validate integer in range
 * $page = InputValidator::integer($_GET['page'], 1, 100);
 */
final class InputValidator
{
    /**
     * Characters allowed in slugs.
     */
    private const SLUG_PATTERN = '/[^a-z0-9_-]/';

    /**
     * Maximum slug length.
     */
    private const SLUG_MAX_LENGTH = 255;

    /**
     * Dangerous HTML tags to strip.
     */
    private const DANGEROUS_TAGS = [
        'script',
        'iframe',
        'object',
        'embed',
        'applet',
        'form',
        'input',
        'button',
        'select',
        'textarea',
        'link',
        'meta',
        'base',
    ];

    /**
     * Dangerous HTML attributes to strip.
     */
    private const DANGEROUS_ATTRIBUTES = [
        'onclick',
        'ondblclick',
        'onmousedown',
        'onmouseup',
        'onmouseover',
        'onmousemove',
        'onmouseout',
        'onkeydown',
        'onkeypress',
        'onkeyup',
        'onload',
        'onerror',
        'onunload',
        'onsubmit',
        'onreset',
        'onfocus',
        'onblur',
        'onchange',
        'javascript:',
        'vbscript:',
        'data:',
    ];

    /**
     * Validates and sanitizes a slug.
     *
     * Converts to lowercase, replaces spaces with hyphens, removes invalid characters.
     *
     * @param string $value The input value
     * @param bool $allowEmpty Whether to allow empty slugs
     * @return string The sanitized slug
     * @throws ValidationException If slug is empty and not allowed
     */
    public static function slug(string $value, bool $allowEmpty = false): string
    {
        // Trim whitespace
        $slug = trim($value);

        // Convert to lowercase
        $slug = mb_strtolower($slug, 'UTF-8');

        // Replace accented characters
        $slug = self::removeAccents($slug);

        // Replace spaces and underscores with hyphens
        $slug = str_replace([' ', '_'], '-', $slug);

        // Remove invalid characters
        $slug = preg_replace(self::SLUG_PATTERN, '', $slug);

        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');

        // Limit length
        if (strlen($slug) > self::SLUG_MAX_LENGTH) {
            $slug = substr($slug, 0, self::SLUG_MAX_LENGTH);
            $slug = rtrim($slug, '-');
        }

        // Check if empty
        if (!$allowEmpty && $slug === '') {
            throw ValidationException::forField('slug', 'Slug cannot be empty');
        }

        return $slug;
    }

    /**
     * Sanitizes HTML content.
     *
     * Removes dangerous tags and attributes while preserving safe formatting.
     *
     * @param string $value The HTML content
     * @param array<string> $allowedTags Additional tags to allow (e.g., ['div', 'span'])
     * @return string The sanitized HTML
     */
    public static function html(string $value, array $allowedTags = []): string
    {
        // Remove null bytes
        $html = str_replace("\0", '', $value);

        // Remove dangerous tags
        foreach (self::DANGEROUS_TAGS as $tag) {
            if (!in_array($tag, $allowedTags, true)) {
                $html = preg_replace(
                    '/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is',
                    '',
                    $html
                );
                $html = preg_replace('/<' . $tag . '[^>]*\/?>/i', '', $html);
            }
        }

        // Remove dangerous attributes
        foreach (self::DANGEROUS_ATTRIBUTES as $attr) {
            $html = preg_replace(
                '/\s*' . preg_quote($attr, '/') . '\s*=\s*["\'][^"\']*["\']/i',
                '',
                $html
            );
            // Also handle unquoted values
            $html = preg_replace(
                '/\s*' . preg_quote($attr, '/') . '\s*=\s*[^\s>]*/i',
                '',
                $html
            );
        }

        return $html;
    }

    /**
     * Validates an email address.
     *
     * @param string $value The email to validate
     * @return bool True if valid email
     */
    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validates an email address and throws if invalid.
     *
     * @param string $value The email to validate
     * @return string The validated email
     * @throws ValidationException If email is invalid
     */
    public static function email(string $value): string
    {
        $email = trim($value);

        if (!self::isEmail($email)) {
            throw ValidationException::forField('email', 'Invalid email format');
        }

        return $email;
    }

    /**
     * Validates a URL.
     *
     * @param string $value The URL to validate
     * @param array<string> $allowedSchemes Allowed URL schemes (default: http, https)
     * @return bool True if valid URL
     */
    public static function isUrl(string $value, array $allowedSchemes = ['http', 'https']): bool
    {
        $url = filter_var($value, FILTER_VALIDATE_URL);

        if ($url === false) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme !== null && in_array(strtolower($scheme), $allowedSchemes, true);
    }

    /**
     * Validates a URL and throws if invalid.
     *
     * @param string $value The URL to validate
     * @param array<string> $allowedSchemes Allowed URL schemes
     * @return string The validated URL
     * @throws ValidationException If URL is invalid
     */
    public static function url(string $value, array $allowedSchemes = ['http', 'https']): string
    {
        $url = trim($value);

        if (!self::isUrl($url, $allowedSchemes)) {
            throw ValidationException::forField('url', 'Invalid URL format');
        }

        return $url;
    }

    /**
     * Sanitizes a filename.
     *
     * Removes path traversal attempts and dangerous characters.
     *
     * @param string $value The filename
     * @return string The sanitized filename
     */
    public static function filename(string $value): string
    {
        // Get only the basename (remove any path)
        $filename = basename($value);

        // Remove null bytes
        $filename = str_replace("\0", '', $filename);

        // Remove path traversal attempts
        $filename = str_replace(['..', '/', '\\'], '', $filename);

        // Remove control characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename);

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);

        return $filename;
    }

    /**
     * Validates and clamps an integer to a range.
     *
     * @param mixed $value The value to validate
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @param int|null $default Default value if invalid (null throws exception)
     * @return int The validated integer
     * @throws ValidationException If value is invalid and no default
     */
    public static function integer(
        mixed $value,
        int $min = PHP_INT_MIN,
        int $max = PHP_INT_MAX,
        ?int $default = null
    ): int {
        if (!is_numeric($value)) {
            if ($default !== null) {
                return max($min, min($max, $default));
            }
            throw ValidationException::forField('integer', 'Value must be numeric');
        }

        $int = (int) $value;

        // Clamp to range
        return max($min, min($max, $int));
    }

    /**
     * Validates a positive integer.
     *
     * @param mixed $value The value to validate
     * @param int $max Maximum allowed value
     * @return int The validated positive integer
     * @throws ValidationException If value is not a positive integer
     */
    public static function positiveInteger(mixed $value, int $max = PHP_INT_MAX): int
    {
        return self::integer($value, 1, $max);
    }

    /**
     * Validates a non-negative integer (0 or greater).
     *
     * @param mixed $value The value to validate
     * @param int $max Maximum allowed value
     * @return int The validated non-negative integer
     * @throws ValidationException If value is negative
     */
    public static function nonNegativeInteger(mixed $value, int $max = PHP_INT_MAX): int
    {
        return self::integer($value, 0, $max);
    }

    /**
     * Validates a float value.
     *
     * @param mixed $value The value to validate
     * @param float $min Minimum allowed value
     * @param float $max Maximum allowed value
     * @return float The validated float
     * @throws ValidationException If value is not numeric
     */
    public static function float(
        mixed $value,
        float $min = -PHP_FLOAT_MAX,
        float $max = PHP_FLOAT_MAX
    ): float {
        if (!is_numeric($value)) {
            throw ValidationException::forField('float', 'Value must be numeric');
        }

        $float = (float) $value;

        return max($min, min($max, $float));
    }

    /**
     * Validates a boolean value.
     *
     * Accepts: true, false, 1, 0, "1", "0", "true", "false", "yes", "no", "on", "off"
     *
     * @param mixed $value The value to validate
     * @param bool $default Default value if not a valid boolean
     * @return bool The validated boolean
     */
    public static function boolean(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));

            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return $default;
    }

    /**
     * Validates that a value is in an allowed list.
     *
     * @param mixed $value The value to check
     * @param array<mixed> $allowed List of allowed values
     * @param mixed $default Default value if not in list (null throws exception)
     * @return mixed The validated value
     * @throws ValidationException If value not in list and no default
     */
    public static function inArray(mixed $value, array $allowed, mixed $default = null): mixed
    {
        if (in_array($value, $allowed, true)) {
            return $value;
        }

        if ($default !== null) {
            return $default;
        }

        throw ValidationException::forField(
            'value',
            sprintf('Value must be one of: %s', implode(', ', array_map('strval', $allowed)))
        );
    }

    /**
     * Validates a string length.
     *
     * @param string $value The string to validate
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @param bool $trim Whether to trim the string first
     * @return string The validated string
     * @throws ValidationException If length is out of bounds
     */
    public static function stringLength(
        string $value,
        int $min = 0,
        int $max = PHP_INT_MAX,
        bool $trim = true
    ): string {
        $string = $trim ? trim($value) : $value;
        $length = mb_strlen($string, 'UTF-8');

        if ($length < $min) {
            throw ValidationException::forField(
                'string',
                sprintf('String must be at least %d characters', $min)
            );
        }

        if ($length > $max) {
            throw ValidationException::forField(
                'string',
                sprintf('String must not exceed %d characters', $max)
            );
        }

        return $string;
    }

    /**
     * Validates a JSON string.
     *
     * @param string $value The JSON string
     * @param bool $assoc Return associative array instead of object
     * @return array<mixed>|object The decoded JSON
     * @throws ValidationException If JSON is invalid
     */
    public static function json(string $value, bool $assoc = true): array|object
    {
        $decoded = json_decode($value, $assoc);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::forField('json', 'Invalid JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Removes accents from a string.
     */
    private static function removeAccents(string $string): string
    {
        $accents = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ñ' => 'n', 'ç' => 'c', 'ß' => 'ss',
            'æ' => 'ae', 'œ' => 'oe',
            'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'Å' => 'a',
            'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u',
            'Ñ' => 'n', 'Ç' => 'c',
        ];

        return strtr($string, $accents);
    }
}

