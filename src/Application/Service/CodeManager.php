<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Application\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Wepresta_Passwordless_Login\Domain\Exception\AuthenticationException;
use Wepresta_Passwordless_Login\Infrastructure\Repository\CodeRepository;

class CodeManager
{
    private CodeRepository $codeRepository;

    public function __construct(?CodeRepository $codeRepository = null)
    {
        $this->codeRepository = $codeRepository ?? new CodeRepository();
    }

    /**
     * Generate a 6-digit code, store its hash, and return the plain code.
     */
    public function generateAndStore(string $email, int $shopId, ?int $customerId = null, ?string $ipAddress = null): string
    {
        $email = strtolower(trim($email));

        // Invalidate any previous unused codes for this email
        $this->codeRepository->invalidatePreviousCodes($email, $shopId);

        // Generate a random 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Hash the code
        $hash = password_hash($code, PASSWORD_DEFAULT);

        // Store in database
        $this->codeRepository->storeCode($email, $hash, $shopId, $customerId, $ipAddress);

        return $code;
    }

    /**
     * Verify a code against the stored hash.
     *
     * @return array<string, mixed> The code record on success
     * @throws AuthenticationException On failure
     */
    public function verify(string $email, string $plainCode, int $shopId): array
    {
        $email = strtolower(trim($email));
        $maxAttempts = (int) Configuration::get('WEPRESTA_PL_CODE_MAX_ATTEMPTS') ?: 5;

        // Find the latest valid code
        $codeRecord = $this->codeRepository->findValidCode($email, $shopId);

        if ($codeRecord === null) {
            throw AuthenticationException::noValidCode();
        }

        // Check if max attempts exceeded
        if ((int) $codeRecord['attempts'] >= $maxAttempts) {
            $this->codeRepository->markAsUsed((int) $codeRecord['id_code']);
            throw AuthenticationException::maxAttemptsReached();
        }

        // Increment attempts before verifying
        $this->codeRepository->incrementAttempts((int) $codeRecord['id_code']);

        // Verify the code
        if (!password_verify($plainCode, $codeRecord['code_hash'])) {
            // Check if this was the last attempt
            if ((int) $codeRecord['attempts'] + 1 >= $maxAttempts) {
                $this->codeRepository->markAsUsed((int) $codeRecord['id_code']);
                throw AuthenticationException::maxAttemptsReached();
            }
            throw AuthenticationException::invalidCode();
        }

        // Mark as used AND verified (distinguishes from max-attempts exhaustion)
        $this->codeRepository->markAsVerified((int) $codeRecord['id_code']);

        return $codeRecord;
    }

    /**
     * Check if a recently verified code exists for this email.
     */
    public function hasRecentlyVerifiedCode(string $email, int $shopId): bool
    {
        return $this->codeRepository->hasRecentlyVerifiedCode(strtolower(trim($email)), $shopId);
    }
}
