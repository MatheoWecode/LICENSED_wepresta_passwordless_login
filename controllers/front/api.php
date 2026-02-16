<?php
/**
 * Passwordless Login - API Front Controller
 * Handles AJAX requests: sendCode, verifyCode, completeProfile, googleAuth
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Wepresta_Passwordless_Login\Application\Service\CodeManager;
use Wepresta_Passwordless_Login\Application\Service\CustomerCreator;
use Wepresta_Passwordless_Login\Application\Service\EmailSender;
use Wepresta_Passwordless_Login\Application\Service\GoogleAuthHandler;
use Wepresta_Passwordless_Login\Application\Service\RateLimiter;
use Wepresta_Passwordless_Login\Domain\Exception\AuthenticationException;
use Wepresta_Passwordless_Login\Infrastructure\Repository\SocialRepository;

class Wepresta_Passwordless_LoginApiModuleFrontController extends ModuleFrontController
{
    public $ajax = true;
    public $auth = false;
    public $guestAllowed = true;
    public $ssl = true;

    private ?CodeManager $codeManager = null;
    private ?RateLimiter $rateLimiter = null;
    private ?CustomerCreator $customerCreator = null;
    private ?EmailSender $emailSender = null;
    private ?GoogleAuthHandler $googleAuthHandler = null;
    private ?SocialRepository $socialRepository = null;

    public function init(): void
    {
        parent::init();

        // Check module is active
        if (!Configuration::get('WEPRESTA_PL_ACTIVE')) {
            $this->jsonError('Module is not active.', 'GENERIC_ERROR', 403);
        }
    }

    public function postProcess(): void
    {
        // Validate CSRF token
        $token = Tools::getValue('token');
        if (empty($token) || $token !== Tools::getToken(false)) {
            $this->jsonError('Invalid security token. Please refresh the page.', 'CSRF_INVALID', 403);
            return;
        }

        $action = Tools::getValue('action');

        switch ($action) {
            case 'sendCode':
                $this->handleSendCode();
                break;
            case 'verifyCode':
                $this->handleVerifyCode();
                break;
            case 'completeProfile':
                $this->handleCompleteProfile();
                break;
            case 'googleAuth':
                $this->handleGoogleAuth();
                break;
            default:
                $this->jsonError('Unknown action.', 'GENERIC_ERROR', 400);
        }
    }

    // =========================================================================
    // Action handlers
    // =========================================================================

    private function handleSendCode(): void
    {
        $email = strtolower(trim(Tools::getValue('email', '')));

        if (!Validate::isEmail($email)) {
            $this->jsonError(
                $this->trans('Please enter a valid email address.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'INVALID_EMAIL'
            );
            return;
        }

        $shopId = (int) $this->context->shop->id;

        // Check rate limit
        if (!$this->getRateLimiter()->canSendCode($email, $shopId)) {
            $this->jsonError(
                $this->trans('Too many requests. Please wait a moment.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'RATE_LIMITED'
            );
            return;
        }

        // Check if customer exists (to set id_customer on the code)
        $customer = $this->getCustomerCreator()->findByEmail($email);
        $customerId = $customer ? (int) $customer->id : null;

        // Generate and store code
        $code = $this->getCodeManager()->generateAndStore($email, $shopId, $customerId);

        // Log code in debug mode (for local development without email)
        if (Configuration::get('WEPRESTA_PL_DEBUG')) {
            PrestaShopLogger::addLog(
                'PasswordlessLogin: code ' . $code . ' for ' . $email,
                1,
                null,
                'Module',
                0,
                true
            );
        }

        // Send email
        $langId = (int) $this->context->language->id;
        $this->getEmailSender()->sendVerificationCode($email, $code, $langId, $shopId);

        // Always return success — never reveal if account exists
        $this->jsonSuccess();
    }

    private function handleVerifyCode(): void
    {
        $email = strtolower(trim(Tools::getValue('email', '')));
        $code = trim(Tools::getValue('code', ''));
        $shopId = (int) $this->context->shop->id;

        if (!Validate::isEmail($email)) {
            $this->jsonError(
                $this->trans('Please enter a valid email address.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'INVALID_EMAIL'
            );
            return;
        }

        if (empty($code) || strlen($code) !== 6) {
            $this->jsonError(
                $this->trans('Please enter the 6-digit code.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'CODE_INVALID'
            );
            return;
        }

        try {
            $this->getCodeManager()->verify($email, $code, $shopId);
        } catch (AuthenticationException $e) {
            $this->jsonError(
                $this->translateErrorCode($e->getErrorCode()),
                $e->getErrorCode()
            );
            return;
        }

        // Code is valid. Check if customer exists
        $customer = $this->getCustomerCreator()->findByEmail($email);

        if ($customer) {
            // Existing customer: log them in
            $this->getCustomerCreator()->loginCustomer($customer, $this->context);
            $this->context->cookie->write();

            $this->jsonSuccess([
                'needsProfile' => false,
                'redirectUrl' => Tools::getValue('back_url', ''),
            ]);
        } else {
            // New customer: needs profile completion
            // Store email in session for profile completion validation
            $this->context->cookie->__set('pl_verified_email', $email);
            $this->context->cookie->write();

            $this->jsonSuccess([
                'needsProfile' => true,
            ]);
        }
    }

    private function handleCompleteProfile(): void
    {
        $email = strtolower(trim(Tools::getValue('email', '')));
        $firstName = trim(Tools::getValue('firstname', ''));
        $lastName = trim(Tools::getValue('lastname', ''));
        $shopId = (int) $this->context->shop->id;

        // Validate inputs
        if (!Validate::isEmail($email)) {
            $this->jsonError(
                $this->trans('Invalid email address.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'INVALID_EMAIL'
            );
            return;
        }

        if (empty($firstName) || !Validate::isName($firstName)) {
            $this->jsonError(
                $this->trans('Please enter a valid first name.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'PROFILE_INCOMPLETE'
            );
            return;
        }

        if (empty($lastName) || !Validate::isName($lastName)) {
            $this->jsonError(
                $this->trans('Please enter a valid last name.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'PROFILE_INCOMPLETE'
            );
            return;
        }

        // Verify that a code was recently validated for this email
        $verifiedEmail = $this->context->cookie->__get('pl_verified_email');
        if ($verifiedEmail !== $email) {
            // Also check via the database
            if (!$this->getCodeManager()->hasRecentlyVerifiedCode($email, $shopId)) {
                $this->jsonError(
                    $this->trans('Please verify your email first.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                    'CODE_INVALID'
                );
                return;
            }
        }

        // Check if customer already exists (race condition: another tab created it)
        $existingCustomer = $this->getCustomerCreator()->findByEmail($email);
        if ($existingCustomer) {
            // Log them in instead
            $this->getCustomerCreator()->loginCustomer($existingCustomer, $this->context);
            $this->context->cookie->__unset('pl_verified_email');
            $this->context->cookie->write();

            $this->jsonSuccess([
                'redirectUrl' => Tools::getValue('back_url', ''),
            ]);
            return;
        }

        // Create customer
        $customer = $this->getCustomerCreator()->createCustomer($email, $firstName, $lastName, $shopId);

        // Log them in
        $this->getCustomerCreator()->loginCustomer($customer, $this->context);

        // Clean up session
        $this->context->cookie->__unset('pl_verified_email');
        $this->context->cookie->write();

        $this->jsonSuccess([
            'redirectUrl' => Tools::getValue('back_url', ''),
        ]);
    }

    private function handleGoogleAuth(): void
    {
        $credential = Tools::getValue('credential', '');
        $shopId = (int) $this->context->shop->id;

        if (empty($credential)) {
            $this->jsonError(
                $this->trans('Google authentication failed. Please try again.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'GOOGLE_TOKEN_INVALID'
            );
            return;
        }

        $handler = $this->getGoogleAuthHandler();

        if (!$handler->isEnabled()) {
            $this->jsonError(
                $this->trans('Google login is not enabled.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'GENERIC_ERROR'
            );
            return;
        }

        $googleUser = $handler->verifyToken($credential);

        if ($googleUser === null) {
            $this->jsonError(
                $this->trans('Google authentication failed. Please try again.', [], 'Modules.Weprestapasswordlesslogin.Front'),
                'GOOGLE_TOKEN_INVALID'
            );
            return;
        }

        $email = $googleUser['email'];
        $googleId = $googleUser['sub'];
        $firstName = $googleUser['given_name'];
        $lastName = $googleUser['family_name'];

        // Check if we have a social link
        $socialLink = $this->getSocialRepository()->findByProviderAndId('google', $googleId, $shopId);

        if ($socialLink) {
            // Known Google user: load customer and log in
            $customer = new Customer((int) $socialLink['id_customer']);
            if (Validate::isLoadedObject($customer)) {
                $this->getCustomerCreator()->loginCustomer($customer, $this->context);
                $this->context->cookie->write();
                $this->jsonSuccess([
                    'needsProfile' => false,
                    'redirectUrl' => Tools::getValue('back_url', ''),
                ]);
                return;
            }
        }

        // Check if customer exists by email
        $customer = $this->getCustomerCreator()->findByEmail($email);

        if ($customer) {
            // Link Google account and log in
            $this->getSocialRepository()->linkAccount((int) $customer->id, 'google', $googleId, $shopId);
            $this->getCustomerCreator()->loginCustomer($customer, $this->context);
            $this->context->cookie->write();

            $this->jsonSuccess([
                'needsProfile' => false,
                'redirectUrl' => Tools::getValue('back_url', ''),
            ]);
            return;
        }

        // New user from Google
        if (!empty($firstName) && !empty($lastName)) {
            // Google provides name: create customer directly
            $customer = $this->getCustomerCreator()->createCustomer($email, $firstName, $lastName, $shopId);
            $this->getSocialRepository()->linkAccount((int) $customer->id, 'google', $googleId, $shopId);
            $this->getCustomerCreator()->loginCustomer($customer, $this->context);
            $this->context->cookie->write();

            $this->jsonSuccess([
                'needsProfile' => false,
                'redirectUrl' => Tools::getValue('back_url', ''),
            ]);
        } else {
            // No name from Google: need profile completion
            $this->context->cookie->__set('pl_verified_email', $email);
            $this->context->cookie->__set('pl_google_id', $googleId);
            $this->context->cookie->write();

            $this->jsonSuccess([
                'needsProfile' => true,
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
            ]);
        }
    }

    // =========================================================================
    // JSON response helpers
    // =========================================================================

    /**
     * @param array<string, mixed> $data
     */
    private function jsonSuccess(array $data = []): void
    {
        $response = array_merge(['success' => true], $data);
        $this->sendJson($response);
    }

    private function jsonError(string $message, string $errorCode = 'GENERIC_ERROR', int $httpCode = 200): void
    {
        if ($httpCode !== 200) {
            http_response_code($httpCode);
        }
        $this->sendJson([
            'success' => false,
            'error' => $message,
            'error_code' => $errorCode,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function sendJson(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================================================================
    // Error code translation
    // =========================================================================

    private function translateErrorCode(string $errorCode): string
    {
        switch ($errorCode) {
            case 'CODE_EXPIRED':
                return $this->trans('This code has expired. Please request a new one.', [], 'Modules.Weprestapasswordlesslogin.Front');
            case 'CODE_INVALID':
                return $this->trans('Invalid code. Please try again.', [], 'Modules.Weprestapasswordlesslogin.Front');
            case 'CODE_MAX_ATTEMPTS':
                return $this->trans('Too many attempts. Please request a new code.', [], 'Modules.Weprestapasswordlesslogin.Front');
            case 'RATE_LIMITED':
                return $this->trans('Too many requests. Please wait a moment.', [], 'Modules.Weprestapasswordlesslogin.Front');
            default:
                return $this->trans('An error occurred. Please try again.', [], 'Modules.Weprestapasswordlesslogin.Front');
        }
    }

    // =========================================================================
    // Service accessors (lazy instantiation)
    // =========================================================================

    private function getCodeManager(): CodeManager
    {
        if ($this->codeManager === null) {
            $this->codeManager = new CodeManager();
        }
        return $this->codeManager;
    }

    private function getRateLimiter(): RateLimiter
    {
        if ($this->rateLimiter === null) {
            $this->rateLimiter = new RateLimiter();
        }
        return $this->rateLimiter;
    }

    private function getCustomerCreator(): CustomerCreator
    {
        if ($this->customerCreator === null) {
            $this->customerCreator = new CustomerCreator();
        }
        return $this->customerCreator;
    }

    private function getEmailSender(): EmailSender
    {
        if ($this->emailSender === null) {
            $this->emailSender = new EmailSender();
        }
        return $this->emailSender;
    }

    private function getGoogleAuthHandler(): GoogleAuthHandler
    {
        if ($this->googleAuthHandler === null) {
            $this->googleAuthHandler = new GoogleAuthHandler();
        }
        return $this->googleAuthHandler;
    }

    private function getSocialRepository(): SocialRepository
    {
        if ($this->socialRepository === null) {
            $this->socialRepository = new SocialRepository();
        }
        return $this->socialRepository;
    }
}
