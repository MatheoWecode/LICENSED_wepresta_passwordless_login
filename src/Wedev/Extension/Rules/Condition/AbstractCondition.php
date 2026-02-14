<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Classe de base pour les conditions.
 *
 * Fournit des méthodes utilitaires pour la comparaison.
 */
abstract class AbstractCondition implements ConditionInterface
{
    /**
     * Opérateurs de comparaison supportés.
     */
    protected const OPERATORS = ['=', '!=', '>', '<', '>=', '<=', 'in', 'not_in', 'contains', 'starts_with', 'ends_with'];

    /**
     * Compare deux valeurs avec l'opérateur donné.
     */
    protected function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '=', '==' => $this->equals($actual, $expected),
            '!=' => !$this->equals($actual, $expected),
            '>' => $this->toFloat($actual) > $this->toFloat($expected),
            '<' => $this->toFloat($actual) < $this->toFloat($expected),
            '>=' => $this->toFloat($actual) >= $this->toFloat($expected),
            '<=' => $this->toFloat($actual) <= $this->toFloat($expected),
            'in' => $this->in($actual, $expected),
            'not_in' => !$this->in($actual, $expected),
            'contains' => $this->contains($actual, $expected),
            'starts_with' => $this->startsWith($actual, $expected),
            'ends_with' => $this->endsWith($actual, $expected),
            default => throw new \InvalidArgumentException("Unknown operator: {$operator}"),
        };
    }

    /**
     * Vérifie l'égalité (avec cast approprié).
     */
    private function equals(mixed $a, mixed $b): bool
    {
        // Si les deux sont numériques, comparer comme floats
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.0001;
        }

        // Si les deux sont des tableaux, comparer les éléments
        if (is_array($a) && is_array($b)) {
            return $a === $b;
        }

        // Comparaison stricte pour les booléens
        if (is_bool($a) || is_bool($b)) {
            return (bool) $a === (bool) $b;
        }

        // Comparaison comme chaînes pour le reste
        return (string) $a === (string) $b;
    }

    /**
     * Vérifie si la valeur est dans la liste.
     */
    private function in(mixed $value, mixed $list): bool
    {
        if (!is_array($list)) {
            return false;
        }

        // Si $value est un tableau, vérifier l'intersection
        if (is_array($value)) {
            return count(array_intersect($value, $list)) > 0;
        }

        return in_array($value, $list, false);
    }

    /**
     * Vérifie si la chaîne contient la sous-chaîne.
     */
    private function contains(mixed $haystack, mixed $needle): bool
    {
        if (!is_string($haystack) || !is_string($needle)) {
            return false;
        }

        return str_contains($haystack, $needle);
    }

    /**
     * Vérifie si la chaîne commence par le préfixe.
     */
    private function startsWith(mixed $haystack, mixed $needle): bool
    {
        if (!is_string($haystack) || !is_string($needle)) {
            return false;
        }

        return str_starts_with($haystack, $needle);
    }

    /**
     * Vérifie si la chaîne se termine par le suffixe.
     */
    private function endsWith(mixed $haystack, mixed $needle): bool
    {
        if (!is_string($haystack) || !is_string($needle)) {
            return false;
        }

        return str_ends_with($haystack, $needle);
    }

    /**
     * Convertit en float de manière sûre.
     */
    private function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }
}

