<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Domain\Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AuthenticationException extends \RuntimeException
{
    private string $errorCode;

    public function __construct(string $message, string $errorCode, int $code = 0, ?\Throwable $previous = null)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public static function invalidEmail(): self
    {
        return new self('Invalid email address.', 'INVALID_EMAIL');
    }

    public static function codeExpired(): self
    {
        return new self('This code has expired. Please request a new one.', 'CODE_EXPIRED');
    }

    public static function invalidCode(): self
    {
        return new self('Invalid code. Please try again.', 'CODE_INVALID');
    }

    public static function maxAttemptsReached(): self
    {
        return new self('Too many attempts. Please request a new code.', 'CODE_MAX_ATTEMPTS');
    }

    public static function rateLimited(): self
    {
        return new self('Too many requests. Please wait a moment.', 'RATE_LIMITED');
    }

    public static function profileIncomplete(): self
    {
        return new self('Please complete your profile.', 'PROFILE_INCOMPLETE');
    }

    public static function googleTokenInvalid(): self
    {
        return new self('Google authentication failed. Please try again.', 'GOOGLE_TOKEN_INVALID');
    }

    public static function csrfInvalid(): self
    {
        return new self('Invalid security token. Please refresh the page.', 'CSRF_INVALID');
    }

    public static function noValidCode(): self
    {
        return new self('No valid code found. Please request a new one.', 'CODE_INVALID');
    }

    public static function genericError(): self
    {
        return new self('An error occurred. Please try again.', 'GENERIC_ERROR');
    }
}
