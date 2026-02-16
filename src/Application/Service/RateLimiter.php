<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Application\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Wepresta_Passwordless_Login\Infrastructure\Repository\CodeRepository;

class RateLimiter
{
    private CodeRepository $codeRepository;

    public function __construct(?CodeRepository $codeRepository = null)
    {
        $this->codeRepository = $codeRepository ?? new CodeRepository();
    }

    /**
     * Check if a code can be sent to this email (rate limit not exceeded).
     */
    public function canSendCode(string $email, int $shopId): bool
    {
        $maxPerHour = (int) Configuration::get('WEPRESTA_PL_CODE_MAX_SENDS_PER_HOUR') ?: 5;
        $email = strtolower(trim($email));

        $recentCount = $this->codeRepository->countRecentByEmail($email, $shopId, 60);

        return $recentCount < $maxPerHour;
    }
}
