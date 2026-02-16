<?php
/**
 * Passwordless Login - PrestaShop 8.x / 9.x
 *
 * Replaces traditional password-based authentication with email verification codes
 * and optional Google Login. Customers enter their email, receive a 6-digit code,
 * and are logged in or registered automatically.
 *
 * @author      WePresta
 * @copyright   2024 WePresta
 * @license     MIT
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

// Load module autoloader (handles PrestaShop vs CLI context)
require_once __DIR__ . '/autoload.php';

use Wepresta_Passwordless_Login\Application\Installer\ModuleInstaller;
use Wepresta_Passwordless_Login\Application\Installer\TabInstaller;
use Wepresta_Passwordless_Login\Wedev\Core\Adapter\ConfigurationAdapter;

class Wepresta_Passwordless_Login extends Module
{
    public const VERSION = '1.0.0';

    public const DEFAULT_CONFIG = [
        'WEPRESTA_PL_ACTIVE' => true,
        'WEPRESTA_PL_SHOW_LOGO' => true,
        'WEPRESTA_PL_GOOGLE_ENABLED' => false,
        'WEPRESTA_PL_GOOGLE_CLIENT_ID' => '',
        'WEPRESTA_PL_SHOW_CLASSIC_LOGIN' => true,
        'WEPRESTA_PL_DISABLE_GUEST_CHECKOUT' => false,
        'WEPRESTA_PL_CODE_EXPIRATION' => 10,
        'WEPRESTA_PL_CODE_MAX_ATTEMPTS' => 5,
        'WEPRESTA_PL_CODE_MAX_SENDS_PER_HOUR' => 5,
        'WEPRESTA_PL_RESEND_COUNTDOWN' => 60,
        'WEPRESTA_PL_DEBUG' => false,
        'WEPRESTA_PL_HIDE_NATIVE_LOGIN' => true,
        'WEPRESTA_PL_HOOK_POSITION' => 'displayNav1',
        'WEPRESTA_PL_CUSTOM_HOOK' => '',
    ];

    public const WEPRESTA_PARENT_TAB = 'AdminWePresta';

    public const TABS = [
        [
            'class_name' => 'AdminWepresta_Passwordless_LoginMain',
            'name' => 'Passwordless Login',
            'parent_class_name' => 'AdminWePresta',
            'icon' => 'vpn_key',
            'visible' => true,
            'route_name' => 'wepresta_passwordless_login_configure',
        ],
    ];

    public const HOOKS = [
        'displayNav1',
        'displayNav2',
        'displayTop',
        'displayNavFullWidth',
        'displayHeader',
        'actionFrontControllerSetMedia',
        'actionFrontControllerInitBefore',
    ];

    private const MIN_PHP_VERSION = '8.1.0';
    private const REQUIRED_EXTENSIONS = ['json', 'pdo', 'mbstring'];

    private ?ConfigurationAdapter $config = null;

    public function __construct()
    {
        $this->name = 'wepresta_passwordless_login';
        $this->tab = 'front_office_features';
        $this->version = self::VERSION;
        $this->author = 'WePresta';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Passwordless Login', [], 'Modules.Weprestapasswordlesslogin.Admin');
        $this->description = $this->trans(
            'Allow customers to sign in with email verification codes or Google Login instead of passwords.',
            [],
            'Modules.Weprestapasswordlesslogin.Admin'
        );
        $this->confirmUninstall = $this->trans(
            'Are you sure you want to uninstall? All verification codes and social login links will be deleted.',
            [],
            'Modules.Weprestapasswordlesslogin.Admin'
        );
    }

    // =========================================================================
    // INSTALLATION
    // =========================================================================

    public function install(): bool
    {
        if (!$this->checkRequirements()) {
            return false;
        }

        try {
            $installer = new ModuleInstaller($this, [
                'database' => [],
            ]);

            if (!parent::install()) {
                return false;
            }

            if (!$this->registerHook(self::HOOKS)) {
                return false;
            }

            $this->clearSymfonyCache();

            $tabInstaller = new TabInstaller($this);
            if (!$tabInstaller->install(self::TABS)) {
                return false;
            }

            $this->installConfiguration();

            return $installer->install();
        } catch (\Exception $e) {
            $this->_errors[] = $e->getMessage();
            return false;
        }
    }

    public function uninstall(): bool
    {
        try {
            $installer = new ModuleInstaller($this, [
                'database' => [],
            ]);

            $this->uninstallConfiguration();

            // Restore guest checkout if we disabled it
            if (Configuration::get('WEPRESTA_PL_DISABLE_GUEST_CHECKOUT')) {
                Configuration::updateValue('PS_GUEST_CHECKOUT_ENABLED', true);
            }

            $installer->uninstall();

            $tabInstaller = new TabInstaller($this);
            $tabClassNames = array_column(self::TABS, 'class_name');
            $tabInstaller->uninstall($tabClassNames);

            return parent::uninstall();
        } catch (\Exception $e) {
            $this->_errors[] = $e->getMessage();
            return false;
        }
    }

    public function enable($force_all = false): bool
    {
        return parent::enable($force_all) && $this->registerHook(self::HOOKS);
    }

    private function installConfiguration(): void
    {
        foreach (self::DEFAULT_CONFIG as $key => $value) {
            if (Configuration::get($key) === false) {
                Configuration::updateValue($key, $value);
            }
        }
    }

    private function uninstallConfiguration(): void
    {
        foreach (array_keys(self::DEFAULT_CONFIG) as $key) {
            Configuration::deleteByName($key);
        }
        Configuration::deleteByName('WEPRESTA_PL_HOOKS_V2');
        Configuration::deleteByName('WEPRESTA_PL_DB_V2');
    }

    private function clearSymfonyCache(): void
    {
        try {
            if (class_exists(\Tools::class) && method_exists(\Tools::class, 'clearSf2Cache')) {
                \Tools::clearSf2Cache();
            }
            if (class_exists(\Tab::class) && method_exists(\Tab::class, 'resetStaticCache')) {
                \Tab::resetStaticCache();
            }
        } catch (\Throwable $e) {
            // Ignore cache clear errors
        }
    }

    private function checkRequirements(): bool
    {
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            $this->_errors[] = sprintf('PHP %s minimum required (current: %s)', self::MIN_PHP_VERSION, PHP_VERSION);
            return false;
        }

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            if (!extension_loaded($ext)) {
                $this->_errors[] = sprintf('Required PHP extension: %s', $ext);
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // CONFIGURATION (BACK-OFFICE)
    // =========================================================================

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $output .= $this->processConfiguration();
        }

        return $output . $this->buildConfigurationForm();
    }

    private function processConfiguration(): string
    {
        $errors = [];

        // Validate Google config
        $googleEnabled = (bool) Tools::getValue('WEPRESTA_PL_GOOGLE_ENABLED');
        $googleClientId = trim(Tools::getValue('WEPRESTA_PL_GOOGLE_CLIENT_ID', ''));
        if ($googleEnabled && empty($googleClientId)) {
            $errors[] = $this->trans('Google Client ID is required when Google Login is enabled.', [], 'Modules.Weprestapasswordlesslogin.Admin');
        }

        // Validate numeric fields
        $codeExpiration = (int) Tools::getValue('WEPRESTA_PL_CODE_EXPIRATION');
        if ($codeExpiration < 1 || $codeExpiration > 60) {
            $errors[] = $this->trans('Code expiration must be between 1 and 60 minutes.', [], 'Modules.Weprestapasswordlesslogin.Admin');
        }

        $maxAttempts = (int) Tools::getValue('WEPRESTA_PL_CODE_MAX_ATTEMPTS');
        if ($maxAttempts < 1 || $maxAttempts > 10) {
            $errors[] = $this->trans('Max attempts must be between 1 and 10.', [], 'Modules.Weprestapasswordlesslogin.Admin');
        }

        $maxSendsPerHour = (int) Tools::getValue('WEPRESTA_PL_CODE_MAX_SENDS_PER_HOUR');
        if ($maxSendsPerHour < 1 || $maxSendsPerHour > 20) {
            $errors[] = $this->trans('Max codes per hour must be between 1 and 20.', [], 'Modules.Weprestapasswordlesslogin.Admin');
        }

        $resendCountdown = (int) Tools::getValue('WEPRESTA_PL_RESEND_COUNTDOWN');
        if ($resendCountdown < 10 || $resendCountdown > 300) {
            $errors[] = $this->trans('Resend countdown must be between 10 and 300 seconds.', [], 'Modules.Weprestapasswordlesslogin.Admin');
        }

        // Validate hook position
        $hookPosition = Tools::getValue('WEPRESTA_PL_HOOK_POSITION', 'displayNav1');
        $customHook = trim(Tools::getValue('WEPRESTA_PL_CUSTOM_HOOK', ''));

        if ($hookPosition === 'custom') {
            if (empty($customHook)) {
                $errors[] = $this->trans('Custom hook name is required when "Custom hook" is selected.', [], 'Modules.Weprestapasswordlesslogin.Admin');
            } elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $customHook)) {
                $errors[] = $this->trans('Custom hook name must contain only letters and numbers, and start with a letter.', [], 'Modules.Weprestapasswordlesslogin.Admin');
            }
        }

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors));
        }

        // Save all configuration values
        Configuration::updateValue('WEPRESTA_PL_ACTIVE', (int) Tools::getValue('WEPRESTA_PL_ACTIVE'));
        Configuration::updateValue('WEPRESTA_PL_SHOW_LOGO', (int) Tools::getValue('WEPRESTA_PL_SHOW_LOGO'));
        Configuration::updateValue('WEPRESTA_PL_GOOGLE_ENABLED', (int) $googleEnabled);
        Configuration::updateValue('WEPRESTA_PL_GOOGLE_CLIENT_ID', pSQL($googleClientId));
        Configuration::updateValue('WEPRESTA_PL_SHOW_CLASSIC_LOGIN', (int) Tools::getValue('WEPRESTA_PL_SHOW_CLASSIC_LOGIN'));
        Configuration::updateValue('WEPRESTA_PL_HIDE_NATIVE_LOGIN', (int) Tools::getValue('WEPRESTA_PL_HIDE_NATIVE_LOGIN'));
        Configuration::updateValue('WEPRESTA_PL_DEBUG', (int) Tools::getValue('WEPRESTA_PL_DEBUG'));
        Configuration::updateValue('WEPRESTA_PL_CODE_EXPIRATION', $codeExpiration);
        Configuration::updateValue('WEPRESTA_PL_CODE_MAX_ATTEMPTS', $maxAttempts);
        Configuration::updateValue('WEPRESTA_PL_CODE_MAX_SENDS_PER_HOUR', $maxSendsPerHour);
        Configuration::updateValue('WEPRESTA_PL_RESEND_COUNTDOWN', $resendCountdown);

        // Handle hook position change
        $previousPosition = Configuration::get('WEPRESTA_PL_HOOK_POSITION');
        $previousCustomHook = Configuration::get('WEPRESTA_PL_CUSTOM_HOOK');

        if ($previousPosition === 'custom' && !empty($previousCustomHook)) {
            $this->unregisterHook($previousCustomHook);
        }

        if ($hookPosition === 'custom' && !empty($customHook)) {
            $this->registerHook($customHook);
        }

        Configuration::updateValue('WEPRESTA_PL_HOOK_POSITION', $hookPosition);
        Configuration::updateValue('WEPRESTA_PL_CUSTOM_HOOK', pSQL($customHook));

        // Handle guest checkout toggle
        $disableGuest = (int) Tools::getValue('WEPRESTA_PL_DISABLE_GUEST_CHECKOUT');
        Configuration::updateValue('WEPRESTA_PL_DISABLE_GUEST_CHECKOUT', $disableGuest);
        if ($disableGuest) {
            Configuration::updateValue('PS_GUEST_CHECKOUT_ENABLED', false);
        }

        return $this->displayConfirmation(
            $this->trans('Settings saved.', [], 'Modules.Weprestapasswordlesslogin.Admin')
        );
    }

    private function buildConfigurationForm(): string
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminToken('AdminModules' . (int) \Tab::getIdFromClassName('AdminModules') . (int) $this->context->employee->id);
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->submit_action = 'submit' . $this->name;
        $helper->title = $this->displayName;

        $helper->fields_value = $this->getConfigurationValues();

        return $helper->generateForm($this->getConfigFormFields());
    }

    private function getConfigFormFields(): array
    {
        $switchValues = [
            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
        ];

        return [
            // Section 1: General
            [
                'form' => [
                    'legend' => [
                        'title' => $this->trans('General Settings', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                        'icon' => 'icon-cogs',
                    ],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->trans('Enable module', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_ACTIVE',
                            'is_bool' => true,
                            'values' => $switchValues,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->trans('Display shop logo', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_SHOW_LOGO',
                            'is_bool' => true,
                            'hint' => $this->trans('Show the shop logo on the login page.', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'values' => $switchValues,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->trans('Show classic login link', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_SHOW_CLASSIC_LOGIN',
                            'is_bool' => true,
                            'desc' => $this->trans('Display a "Sign in with a password" link on the passwordless login page, allowing customers to use the standard PrestaShop login form instead.', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'values' => $switchValues,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->trans('Hide native login link', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_HIDE_NATIVE_LOGIN',
                            'is_bool' => true,
                            'desc' => $this->trans('Hide the default PrestaShop "Sign in" link (from ps_customersignin) for guests. The account/logout links remain visible for logged-in customers.', [], 'Modules.Weprestapasswordlesslogin.Admin')
                                . '<br><strong>' . $this->trans('Note: If your theme uses a custom login template (not the default ps_customersignin), this option may not work as expected.', [], 'Modules.Weprestapasswordlesslogin.Admin') . '</strong>',
                            'values' => $switchValues,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->trans('Disable guest checkout', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_DISABLE_GUEST_CHECKOUT',
                            'is_bool' => true,
                            'desc' => $this->trans('Warning: Guest checkout will be disabled. Customers will need to sign in to place an order.', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'values' => $switchValues,
                        ],
                        [
                            'type' => 'select',
                            'label' => $this->trans('Login link position', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_HOOK_POSITION',
                            'desc' => $this->trans('Choose where to display the login link in your theme.', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'options' => [
                                'query' => [
                                    ['id' => 'displayNav1', 'name' => 'displayNav1 — ' . $this->trans('Navigation bar (left)', [], 'Modules.Weprestapasswordlesslogin.Admin')],
                                    ['id' => 'displayNav2', 'name' => 'displayNav2 — ' . $this->trans('Navigation bar (right)', [], 'Modules.Weprestapasswordlesslogin.Admin')],
                                    ['id' => 'displayTop', 'name' => 'displayTop — ' . $this->trans('Top of the page', [], 'Modules.Weprestapasswordlesslogin.Admin')],
                                    ['id' => 'displayNavFullWidth', 'name' => 'displayNavFullWidth — ' . $this->trans('Full-width navigation', [], 'Modules.Weprestapasswordlesslogin.Admin')],
                                    ['id' => 'custom', 'name' => $this->trans('Custom hook', [], 'Modules.Weprestapasswordlesslogin.Admin')],
                                ],
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->trans('Custom hook name', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_CUSTOM_HOOK',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->trans('Enter the hook name provided by your theme (e.g. displayCustomNav). Only used when "Custom hook" is selected above.', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->trans('Debug mode', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_DEBUG',
                            'is_bool' => true,
                            'desc' => $this->trans('Enable logging for troubleshooting. Logs are written to PrestaShop\'s log system.', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'values' => $switchValues,
                        ],
                    ],
                    'submit' => [
                        'title' => $this->trans('Save', [], 'Admin.Actions'),
                    ],
                ],
            ],
            // Section 2: Google Login
            [
                'form' => [
                    'legend' => [
                        'title' => $this->trans('Google Login', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                        'icon' => 'icon-google',
                    ],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->trans('Enable Google Login', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_GOOGLE_ENABLED',
                            'is_bool' => true,
                            'values' => $switchValues,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->trans('Google Client ID', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_GOOGLE_CLIENT_ID',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->trans('How to get a Google Client ID:', [], 'Modules.Weprestapasswordlesslogin.Admin')
                                . '<br>1. ' . $this->trans('Go to [url]console.cloud.google.com[/url] and create a project (or select an existing one)', [], 'Modules.Weprestapasswordlesslogin.Admin')
                                . '<br>2. ' . $this->trans('Go to "APIs & Services" > "Credentials"', [], 'Modules.Weprestapasswordlesslogin.Admin')
                                . '<br>3. ' . $this->trans('Click "Create Credentials" > "OAuth client ID"', [], 'Modules.Weprestapasswordlesslogin.Admin')
                                . '<br>4. ' . $this->trans('If prompted, configure the OAuth consent screen first (User type: External)', [], 'Modules.Weprestapasswordlesslogin.Admin')
                                . '<br>5. ' . $this->trans('Application type: "Web application"', [], 'Modules.Weprestapasswordlesslogin.Admin')
                                . '<br>6. ' . $this->trans('In "Authorized JavaScript origins", add your domain (e.g. https://yourshop.com)', [], 'Modules.Weprestapasswordlesslogin.Admin')
                                . '<br>7. ' . $this->trans('Copy the Client ID and paste it here', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                        ],
                    ],
                    'submit' => [
                        'title' => $this->trans('Save', [], 'Admin.Actions'),
                    ],
                ],
            ],
            // Section 3: Security
            [
                'form' => [
                    'legend' => [
                        'title' => $this->trans('Security Settings', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                        'icon' => 'icon-shield',
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->trans('Code expiration (minutes)', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_CODE_EXPIRATION',
                            'class' => 'fixed-width-sm',
                            'suffix' => 'min',
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->trans('Max attempts per code', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_CODE_MAX_ATTEMPTS',
                            'class' => 'fixed-width-sm',
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->trans('Max codes per email per hour', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_CODE_MAX_SENDS_PER_HOUR',
                            'class' => 'fixed-width-sm',
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->trans('Resend countdown (seconds)', [], 'Modules.Weprestapasswordlesslogin.Admin'),
                            'name' => 'WEPRESTA_PL_RESEND_COUNTDOWN',
                            'class' => 'fixed-width-sm',
                            'suffix' => 's',
                        ],
                    ],
                    'submit' => [
                        'title' => $this->trans('Save', [], 'Admin.Actions'),
                    ],
                ],
            ],
        ];
    }

    private function getConfigurationValues(): array
    {
        $values = [];
        foreach (self::DEFAULT_CONFIG as $key => $default) {
            $value = Configuration::get($key);
            $values[$key] = ($value !== false) ? $value : $default;
        }
        return $values;
    }

    // =========================================================================
    // HOOKS — DISPLAY LOGIN LINK
    // =========================================================================

    public function hookDisplayNav1(array $params): string
    {
        return $this->renderLoginLinkForHook('displayNav1');
    }

    public function hookDisplayNav2(array $params): string
    {
        return $this->renderLoginLinkForHook('displayNav2');
    }

    public function hookDisplayTop(array $params): string
    {
        return $this->renderLoginLinkForHook('displayTop');
    }

    public function hookDisplayNavFullWidth(array $params): string
    {
        return $this->renderLoginLinkForHook('displayNavFullWidth');
    }

    /**
     * Catch custom hook calls dynamically.
     * PrestaShop calls hook{HookName}() — for custom hooks we can't define a method in advance.
     */
    public function __call(string $method, array $args)
    {
        if (stripos($method, 'hook') === 0) {
            $hookPosition = Configuration::get('WEPRESTA_PL_HOOK_POSITION');
            $customHook = Configuration::get('WEPRESTA_PL_CUSTOM_HOOK');

            if ($hookPosition === 'custom' && !empty($customHook)) {
                $calledHook = substr($method, 4); // Remove 'hook' prefix
                if (strcasecmp($calledHook, $customHook) === 0) {
                    return $this->renderLoginLink();
                }
            }
        }

        return '';
    }

    private function renderLoginLinkForHook(string $hookName): string
    {
        $activeHook = Configuration::get('WEPRESTA_PL_HOOK_POSITION') ?: 'displayNav1';

        if ($activeHook !== $hookName) {
            return '';
        }

        return $this->renderLoginLink();
    }

    private function renderLoginLink(): string
    {
        if (!$this->isActive()) {
            return '';
        }

        // Only show for non-logged-in customers
        if ($this->context->customer && $this->context->customer->isLogged()) {
            return '';
        }

        $this->context->smarty->assign([
            'pl_auth_url' => $this->context->link->getModuleLink($this->name, 'auth'),
        ]);

        return $this->fetch('module:wepresta_passwordless_login/views/templates/hook/login-link.tpl');
    }

    // =========================================================================
    // HOOKS — ASSETS & ACTIONS
    // =========================================================================

    public function hookDisplayHeader(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        // One-time hook registration for existing installations (handles upgrades)
        if (!Configuration::get('WEPRESTA_PL_HOOKS_V2')) {
            $this->registerHook('actionFrontControllerInitBefore');
            Configuration::updateValue('WEPRESTA_PL_HOOKS_V2', true);
        }

        // Register minimal CSS for hook login link styling on all pages
        $this->context->controller->registerStylesheet(
            'wepresta-pl-hook',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );

        // Hide native ps_customersignin login link for guests
        if (Configuration::get('WEPRESTA_PL_HIDE_NATIVE_LOGIN')
            && (!$this->context->customer || !$this->context->customer->isLogged())
        ) {
            return '<style>#_desktop_user_info,#_mobile_user_info{display:none!important}</style>';
        }

        return '';
    }

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        // Assets for the auth page are registered by the auth controller itself
    }

    /**
     * Intercept checkout page for non-logged-in customers.
     * Redirects to the passwordless login page with a back URL to the order page.
     */
    public function hookActionFrontControllerInitBefore(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        $controller = $this->context->controller;

        // Only intercept the order/checkout controller
        if (!isset($controller->php_self) || $controller->php_self !== 'order') {
            return;
        }

        // Don't redirect if customer is already logged in
        if ($this->context->customer && $this->context->customer->isLogged()) {
            return;
        }

        // Build the back URL (full order page URL for correct redirect after login)
        $orderUrl = $this->context->link->getPageLink('order', true);

        // Redirect to passwordless login page
        $authUrl = $this->context->link->getModuleLink(
            $this->name,
            'auth',
            ['back' => $orderUrl]
        );

        Tools::redirect($authUrl);
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    public function isActive(): bool
    {
        return (bool) Configuration::get('WEPRESTA_PL_ACTIVE');
    }

    public function getConfig(): ConfigurationAdapter
    {
        if ($this->config === null) {
            $this->config = new ConfigurationAdapter();
        }
        return $this->config;
    }

    public function getService(string $serviceId): ?object
    {
        try {
            $container = $this->getContainer();
            if ($container && $container->has($serviceId)) {
                return $container->get($serviceId);
            }
        } catch (\Exception $e) {
            // Service not available
        }
        return null;
    }

    public function getModulePath(): string
    {
        return $this->getLocalPath();
    }

    protected function getCacheId($name = null)
    {
        return $this->name . '_' . (string) $name . '_' . $this->context->shop->id;
    }
}

// PrestaShop class alias for Module::getInstanceByName()
if (!class_exists('wepresta_passwordless_login', false)) {
    class_alias('Wepresta_Passwordless_Login', 'wepresta_passwordless_login');
}
