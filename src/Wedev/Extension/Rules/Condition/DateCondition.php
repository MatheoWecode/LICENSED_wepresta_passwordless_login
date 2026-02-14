<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Condition basée sur la date/heure.
 *
 * @example
 * // Weekend (samedi=6, dimanche=0)
 * new DateCondition('day_of_week', 'in', [0, 6])
 *
 * // Heures de bureau
 * new DateCondition('hour', '>=', 9)
 *
 * // Période spécifique
 * new DateCondition('date_range', 'in', ['2024-12-01', '2024-12-31'])
 *
 * // Mois de décembre
 * new DateCondition('month', '=', 12)
 */
final class DateCondition extends AbstractCondition
{
    private const SUPPORTED_FIELDS = [
        'day_of_week',      // 0 (dimanche) à 6 (samedi)
        'hour',             // 0 à 23
        'minute',           // 0 à 59
        'day',              // 1 à 31
        'month',            // 1 à 12
        'year',             // Ex: 2024
        'date',             // Format Y-m-d
        'datetime',         // Format Y-m-d H:i:s
        'date_range',       // [start, end] en Y-m-d
        'time_range',       // [start, end] en H:i
        'is_weekend',       // true/false
        'is_business_hours', // true/false (9h-18h, lun-ven)
    ];

    private ?\DateTimeImmutable $referenceDate;

    public function __construct(
        private readonly string $field,
        private readonly string $operator,
        private readonly mixed $value,
        ?\DateTimeInterface $referenceDate = null
    ) {
        if (!in_array($this->field, self::SUPPORTED_FIELDS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported date field: "%s". Supported: %s',
                $this->field,
                implode(', ', self::SUPPORTED_FIELDS)
            ));
        }

        $this->referenceDate = $referenceDate !== null
            ? \DateTimeImmutable::createFromInterface($referenceDate)
            : null;
    }

    public function evaluate(RuleContext $context): bool
    {
        $now = $this->referenceDate ?? new \DateTimeImmutable();
        $dateValue = $this->getDateValue($now);

        return $this->compare($dateValue, $this->operator, $this->value);
    }

    /**
     * Extrait la valeur de date pour le champ demandé.
     */
    private function getDateValue(\DateTimeImmutable $now): mixed
    {
        return match ($this->field) {
            'day_of_week' => (int) $now->format('w'),
            'hour' => (int) $now->format('G'),
            'minute' => (int) $now->format('i'),
            'day' => (int) $now->format('j'),
            'month' => (int) $now->format('n'),
            'year' => (int) $now->format('Y'),
            'date' => $now->format('Y-m-d'),
            'datetime' => $now->format('Y-m-d H:i:s'),
            'date_range' => $this->isInDateRange($now),
            'time_range' => $this->isInTimeRange($now),
            'is_weekend' => in_array((int) $now->format('w'), [0, 6], true),
            'is_business_hours' => $this->isBusinessHours($now),
            default => null,
        };
    }

    /**
     * Vérifie si la date est dans une plage.
     */
    private function isInDateRange(\DateTimeImmutable $now): bool
    {
        if (!is_array($this->value) || count($this->value) !== 2) {
            return false;
        }

        [$start, $end] = $this->value;

        $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', $start);
        $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', $end);

        if ($startDate === false || $endDate === false) {
            return false;
        }

        $nowDate = $now->setTime(0, 0, 0);

        return $nowDate >= $startDate->setTime(0, 0, 0)
            && $nowDate <= $endDate->setTime(23, 59, 59);
    }

    /**
     * Vérifie si l'heure est dans une plage.
     */
    private function isInTimeRange(\DateTimeImmutable $now): bool
    {
        if (!is_array($this->value) || count($this->value) !== 2) {
            return false;
        }

        [$start, $end] = $this->value;
        $currentTime = $now->format('H:i');

        return $currentTime >= $start && $currentTime <= $end;
    }

    /**
     * Vérifie si c'est les heures de bureau (9h-18h, lun-ven).
     */
    private function isBusinessHours(\DateTimeImmutable $now): bool
    {
        $dayOfWeek = (int) $now->format('w');
        $hour = (int) $now->format('G');

        // Lundi (1) à Vendredi (5)
        if ($dayOfWeek < 1 || $dayOfWeek > 5) {
            return false;
        }

        // 9h à 18h
        return $hour >= 9 && $hour < 18;
    }
}

