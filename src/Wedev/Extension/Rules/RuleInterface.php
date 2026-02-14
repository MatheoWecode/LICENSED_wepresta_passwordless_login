<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\Action\ActionInterface;
use Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition\ConditionInterface;

/**
 * Interface pour les règles métier.
 *
 * Une règle combine une condition (quand) et une action (alors).
 */
interface RuleInterface
{
    /**
     * Retourne le nom unique de la règle.
     */
    public function getName(): string;

    /**
     * Retourne la condition de la règle.
     */
    public function getCondition(): ConditionInterface;

    /**
     * Retourne l'action à exécuter si la condition est vraie.
     */
    public function getAction(): ?ActionInterface;

    /**
     * Vérifie si la règle est active.
     */
    public function isEnabled(): bool;

    /**
     * Retourne la priorité (plus haut = évalué en premier).
     */
    public function getPriority(): int;
}

