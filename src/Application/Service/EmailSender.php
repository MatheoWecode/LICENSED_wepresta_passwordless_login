<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Application\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Mail;

class EmailSender
{
    /**
     * Send a verification code email.
     */
    public function sendVerificationCode(string $email, string $code, int $langId, int $shopId): bool
    {
        $shopName = Configuration::get('PS_SHOP_NAME', null, null, $shopId) ?: 'Shop';
        $expirationMinutes = (int) Configuration::get('WEPRESTA_PL_CODE_EXPIRATION') ?: 10;

        // Format code with space for readability: "847 293"
        $formattedCode = substr($code, 0, 3) . ' ' . substr($code, 3);

        $templateVars = [
            '{code}' => $formattedCode,
            '{code_raw}' => $code,
            '{expiration}' => (string) $expirationMinutes,
            '{shop_name}' => $shopName,
        ];

        return (bool) Mail::send(
            $langId,
            'passwordless_code',
            $shopName . ' - ' . Mail::l('Your verification code', $langId),
            $templateVars,
            $email,
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'wepresta_passwordless_login/mails/',
            false,
            $shopId
        );
    }
}
