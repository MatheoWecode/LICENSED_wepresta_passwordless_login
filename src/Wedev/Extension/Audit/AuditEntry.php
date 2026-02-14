<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Audit;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Représente une entrée de journal d'audit.
 */
final class AuditEntry
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_VIEW = 'view';
    public const ACTION_EXPORT = 'export';
    public const ACTION_IMPORT = 'import';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_CUSTOM = 'custom';

    private ?int $id = null;
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $oldValues Valeurs avant modification
     * @param array<string, mixed> $newValues Valeurs après modification
     * @param array<string, mixed> $context   Contexte additionnel
     */
    public function __construct(
        private readonly string $action,
        private readonly string $entityType,
        private readonly ?int $entityId,
        private readonly ?int $userId,
        private readonly ?string $userName,
        private readonly string $ipAddress,
        private readonly array $oldValues = [],
        private readonly array $newValues = [],
        private readonly array $context = [],
        private readonly int $shopId = 0
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    // -------------------------------------------------------------------------
    // Factory methods
    // -------------------------------------------------------------------------

    /**
     * Crée une entrée pour une création.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $context
     */
    public static function create(
        string $entityType,
        int $entityId,
        array $values,
        array $context = []
    ): self {
        return self::fromContext(self::ACTION_CREATE, $entityType, $entityId, [], $values, $context);
    }

    /**
     * Crée une entrée pour une modification.
     *
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $context
     */
    public static function update(
        string $entityType,
        int $entityId,
        array $oldValues,
        array $newValues,
        array $context = []
    ): self {
        return self::fromContext(self::ACTION_UPDATE, $entityType, $entityId, $oldValues, $newValues, $context);
    }

    /**
     * Crée une entrée pour une suppression.
     *
     * @param array<string, mixed> $values  Valeurs avant suppression
     * @param array<string, mixed> $context
     */
    public static function delete(
        string $entityType,
        int $entityId,
        array $values = [],
        array $context = []
    ): self {
        return self::fromContext(self::ACTION_DELETE, $entityType, $entityId, $values, [], $context);
    }

    /**
     * Crée une entrée depuis le contexte PrestaShop actuel.
     *
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $context
     */
    private static function fromContext(
        string $action,
        string $entityType,
        ?int $entityId,
        array $oldValues,
        array $newValues,
        array $context
    ): self {
        $psContext = \Context::getContext();

        $userId = null;
        $userName = null;

        // Essayer d'obtenir l'employé (back-office)
        if (isset($psContext->employee) && $psContext->employee->id) {
            $userId = (int) $psContext->employee->id;
            $userName = sprintf(
                '%s %s (employee)',
                $psContext->employee->firstname,
                $psContext->employee->lastname
            );
        }
        // Sinon le client (front-office)
        elseif (isset($psContext->customer) && $psContext->customer->id) {
            $userId = (int) $psContext->customer->id;
            $userName = sprintf(
                '%s %s (customer)',
                $psContext->customer->firstname,
                $psContext->customer->lastname
            );
        }

        $ipAddress = \Tools::getRemoteAddr() ?: 'unknown';
        $shopId = isset($psContext->shop) ? (int) $psContext->shop->id : 0;

        return new self(
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            userId: $userId,
            userName: $userName,
            ipAddress: $ipAddress,
            oldValues: $oldValues,
            newValues: $newValues,
            context: $context,
            shopId: $shopId
        );
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOldValues(): array
    {
        return $this->oldValues;
    }

    /**
     * @return array<string, mixed>
     */
    public function getNewValues(): array
    {
        return $this->newValues;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // -------------------------------------------------------------------------
    // Setters (pour hydratation)
    // -------------------------------------------------------------------------

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $date): self
    {
        $this->createdAt = $date;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Retourne les changements (diff entre old et new).
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getChanges(): array
    {
        $changes = [];

        // Champs modifiés
        foreach ($this->newValues as $key => $newValue) {
            $oldValue = $this->oldValues[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        // Champs supprimés
        foreach ($this->oldValues as $key => $oldValue) {
            if (!array_key_exists($key, $this->newValues)) {
                $changes[$key] = ['old' => $oldValue, 'new' => null];
            }
        }

        return $changes;
    }

    /**
     * Vérifie si un champ spécifique a été modifié.
     */
    public function hasChanged(string $field): bool
    {
        return array_key_exists($field, $this->getChanges());
    }
}

