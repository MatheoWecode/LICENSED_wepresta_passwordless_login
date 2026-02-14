<?php
/**
 * Module Starter - Template PRO pour PrestaShop 8.x / 9.x
 *
 * Architecture moderne avec:
 * - Clean Architecture (Application/Domain/Infrastructure)
 * - CQRS léger
 * - Symfony Forms & Grid
 * - Event Subscribers
 * - Doctrine Entities
 *
 * @author      WePresta
 * @copyright   2024 Votre Société
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
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class Wepresta_Passwordless_Login extends Module
{
    /**
     * Version du module (utilisée par les contrôleurs)
     */
    public const VERSION = '1.0.0';

    /**
     * Configuration par défaut du module
     */
    public const DEFAULT_CONFIG = [
        'WEPRESTA_PASSWORDLESS_LOGIN_ACTIVE' => true,
        'WEPRESTA_PASSWORDLESS_LOGIN_TITLE' => 'Module Starter',
        'WEPRESTA_PASSWORDLESS_LOGIN_DESCRIPTION' => '',
        'WEPRESTA_PASSWORDLESS_LOGIN_DEBUG' => false,
        'WEPRESTA_PASSWORDLESS_LOGIN_CACHE_TTL' => 3600,
        'WEPRESTA_PASSWORDLESS_LOGIN_API_ENABLED' => false,
    ];

    /**
     * Shared parent tab class name for all WePresta modules
     */
    public const WEPRESTA_PARENT_TAB = 'AdminWePresta';

    /**
     * Tabs admin du module
     * 
     * Structure:
     * 📁 WePresta (AdminWePresta) - auto-created if missing
     *   └── 📁 Module Starter (AdminWepresta_Passwordless_LoginMain) → opens Dashboard
     *         └── Dashboard (AdminWepresta_Passwordless_LoginDashboard)
     * 
     * IMPORTANT: The main tab MUST have a route_name (same as dashboard)
     * to avoid "Controller not found" error in PrestaShop.
     */
    public const TABS = [
        // Main module tab - under WePresta, with route pointing to dashboard
        [
            'class_name' => 'AdminWepresta_Passwordless_LoginMain',
            'name' => 'Module Starter',
            'parent_class_name' => 'AdminWePresta',
            'icon' => 'dashboard',
            'visible' => true,
            'route_name' => 'wepresta_passwordless_login_dashboard', // REQUIRED: same as dashboard route
        ],
        // Dashboard sub-tab
        [
            'class_name' => 'AdminWepresta_Passwordless_LoginDashboard',
            'name' => 'Dashboard',
            'parent_class_name' => 'AdminWepresta_Passwordless_LoginMain',
            'icon' => 'dashboard',
            'visible' => true,
            'route_name' => 'wepresta_passwordless_login_dashboard',
        ],
    ];

    /**
     * Version minimum de PHP requise
     */
    private const MIN_PHP_VERSION = '8.1.0';

    public const CONFIG_PREFIX = 'WEPRESTA_PASSWORDLESS_LOGIN_';

    /**
     * Extensions PHP requises
     */
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

        $this->displayName = $this->trans('Module Starter', [], 'Modules.Wepresta\Passwordless\Login.Admin');
        $this->description = $this->trans(
            'Template PRO de démarrage pour module PrestaShop 8.x/9.x avec architecture moderne',
            [],
            'Modules.Wepresta\Passwordless\Login.Admin'
        );
        $this->confirmUninstall = $this->trans(
            'Êtes-vous sûr de vouloir désinstaller ce module ? Toutes les données seront perdues.',
            [],
            'Modules.Wepresta\Passwordless\Login.Admin'
        );
    }

    // =========================================================================
    // INSTALLATION / DÉSINSTALLATION
    // =========================================================================
    // =========================================================================
    // INSTALLATION / DÉSINSTALLATION
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

            if (
                !$this->registerHook(
                    'displayHeader',
                    'displayHome',
                    'displayFooter',
                    'displayProductAdditionalInfo',
                    'displayShoppingCart',
                    'displayOrderConfirmation',

                    // Front Office - Actions
                    'actionFrontControllerSetMedia',
                    'actionCartSave',
                    'actionValidateOrder',
                    'actionCustomerAccountAdd',

                    // Back Office
                    'actionAdminControllerSetMedia',
                    'actionObjectProductAddAfter',
                    'actionObjectProductUpdateAfter',
                )
            ) {
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
            $this->log('Installation failed: ' . $e->getMessage(), 3);
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

    private function uninstallConfiguration(): void
    {
        foreach (array_keys(self::DEFAULT_CONFIG) as $key) {
            Configuration::deleteByName(self::CONFIG_PREFIX . $key);
        }
    }

    private function installConfiguration(): void
    {
        foreach (self::DEFAULT_CONFIG as $key => $value) {
            $fullKey = self::CONFIG_PREFIX . $key;
            if (Configuration::get($fullKey) === false) {
                Configuration::updateValue($fullKey, $value);
            }
        }
    }
    /**
     * Clear Symfony cache to register module routes
     */
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
            // Ignore errors
        }
    }

    public function enable($force_all = false): bool
    {
        return parent::enable($force_all) && $this->registerHook(self::HOOKS);
    }

    /**
     * Vérifie les prérequis avant installation
     */
    private function checkRequirements(): bool
    {
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            $this->_errors[] = sprintf(
                $this->trans('PHP %s minimum requis (actuel: %s)', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                self::MIN_PHP_VERSION,
                PHP_VERSION
            );
            return false;
        }

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            if (!extension_loaded($ext)) {
                $this->_errors[] = sprintf(
                    $this->trans('Extension PHP requise: %s', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                    $ext
                );
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // CONFIGURATION (BACK-OFFICE)
    // =========================================================================

    /**
     * Page de configuration du module.
     * Affiche un formulaire de configuration simple.
     * Le dashboard complet est accessible via le menu admin.
     */
    public function getContent(): string
    {
        return $this->renderLegacyConfigurationForm();
    }

    /**
     * Formulaire de configuration legacy (fallback)
     */
    private function renderLegacyConfigurationForm(): string
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $output .= $this->processLegacyConfiguration();
        }

        return $output . $this->buildLegacyForm();
    }

    private function processLegacyConfiguration(): string
    {
        $errors = [];

        $title = Tools::getValue('WEPRESTA_PASSWORDLESS_LOGIN_TITLE');
        if (empty($title)) {
            $errors[] = $this->trans('Le titre est obligatoire.', [], 'Modules.Wepresta\Passwordless\Login.Admin');
        }

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors));
        }

        Configuration::updateValue('WEPRESTA_PASSWORDLESS_LOGIN_ACTIVE', (bool) Tools::getValue('WEPRESTA_PASSWORDLESS_LOGIN_ACTIVE'));
        Configuration::updateValue('WEPRESTA_PASSWORDLESS_LOGIN_TITLE', pSQL($title));
        Configuration::updateValue('WEPRESTA_PASSWORDLESS_LOGIN_DESCRIPTION', pSQL(Tools::getValue('WEPRESTA_PASSWORDLESS_LOGIN_DESCRIPTION')));
        Configuration::updateValue('WEPRESTA_PASSWORDLESS_LOGIN_DEBUG', (bool) Tools::getValue('WEPRESTA_PASSWORDLESS_LOGIN_DEBUG'));
        Configuration::updateValue('WEPRESTA_PASSWORDLESS_LOGIN_CACHE_TTL', (int) Tools::getValue('WEPRESTA_PASSWORDLESS_LOGIN_CACHE_TTL'));
        Configuration::updateValue('WEPRESTA_PASSWORDLESS_LOGIN_API_ENABLED', (bool) Tools::getValue('WEPRESTA_PASSWORDLESS_LOGIN_API_ENABLED'));

        $this->clearModuleCache();

        return $this->displayConfirmation($this->trans('Configuration sauvegardée.', [], 'Modules.Wepresta\Passwordless\Login.Admin'));
    }

    private function buildLegacyForm(): string
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->submit_action = 'submit' . $this->name;
        $helper->title = $this->displayName;

        $helper->fields_value = $this->getConfigurationValues();

        return $helper->generateForm([$this->getConfigFormFields()]);
    }

    private function getConfigFormFields(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Configuration', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Activer', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                        'name' => 'WEPRESTA_PASSWORDLESS_LOGIN_ACTIVE',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Oui', [], 'Admin.Global')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('Non', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Titre', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                        'name' => 'WEPRESTA_PASSWORDLESS_LOGIN_TITLE',
                        'class' => 'fixed-width-xxl',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Description', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                        'name' => 'WEPRESTA_PASSWORDLESS_LOGIN_DESCRIPTION',
                        'autoload_rte' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Mode debug', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                        'name' => 'WEPRESTA_PASSWORDLESS_LOGIN_DEBUG',
                        'is_bool' => true,
                        'hint' => $this->trans('Active les logs détaillés', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                        'values' => [
                            ['id' => 'debug_on', 'value' => 1, 'label' => $this->trans('Oui', [], 'Admin.Global')],
                            ['id' => 'debug_off', 'value' => 0, 'label' => $this->trans('Non', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('TTL Cache (secondes)', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                        'name' => 'WEPRESTA_PASSWORDLESS_LOGIN_CACHE_TTL',
                        'class' => 'fixed-width-sm',
                        'suffix' => 's',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('API REST', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                        'name' => 'WEPRESTA_PASSWORDLESS_LOGIN_API_ENABLED',
                        'is_bool' => true,
                        'hint' => $this->trans('Active les endpoints API', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
                        'values' => [
                            ['id' => 'api_on', 'value' => 1, 'label' => $this->trans('Oui', [], 'Admin.Global')],
                            ['id' => 'api_off', 'value' => 0, 'label' => $this->trans('Non', [], 'Admin.Global')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getConfigurationValues(): array
    {
        $values = [];
        foreach (array_keys(self::DEFAULT_CONFIG) as $key) {
            $values[$key] = Configuration::get($key);
        }
        return $values;
    }

    // =========================================================================
    // HOOKS FRONT-OFFICE - DISPLAY
    // =========================================================================

    public function hookDisplayHeader(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        $this->context->controller->registerStylesheet(
            'wepresta_passwordless_login-front',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );

        return '';
    }

    public function hookDisplayHome(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        $cacheId = $this->getCacheId('home');

        if (!$this->isCached('module:wepresta_passwordless_login/views/templates/hook/home.tpl', $cacheId)) {
            $this->context->smarty->assign([
                'wepresta_passwordless_login' => [
                    'title' => $this->getConfig()->get('WEPRESTA_PASSWORDLESS_LOGIN_TITLE'),
                    'description' => $this->getConfig()->get('WEPRESTA_PASSWORDLESS_LOGIN_DESCRIPTION'),
                    'link' => $this->context->link->getModuleLink($this->name, 'display'),
                ],
            ]);
        }

        return $this->fetch('module:wepresta_passwordless_login/views/templates/hook/home.tpl', $cacheId);
    }

    public function hookDisplayFooter(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        return $this->fetch('module:wepresta_passwordless_login/views/templates/hook/footer.tpl');
    }

    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        /** @var Product $product */
        $product = $params['product'] ?? null;

        if (!$product) {
            return '';
        }

        $this->context->smarty->assign([
            'product_id' => $product->id ?? ($product['id_product'] ?? 0),
        ]);

        return $this->fetch('module:wepresta_passwordless_login/views/templates/hook/product-info.tpl');
    }

    public function hookDisplayShoppingCart(array $params): string
    {
        return ''; // Implémentez si nécessaire
    }

    public function hookDisplayOrderConfirmation(array $params): string
    {
        return ''; // Implémentez si nécessaire
    }

    // =========================================================================
    // HOOKS FRONT-OFFICE - ACTIONS
    // =========================================================================

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        $controller = Tools::getValue('controller');

        // JS conditionnel
        $jsPages = ['product', 'category', 'index', 'cart'];
        if (in_array($controller, $jsPages, true)) {
            $this->context->controller->registerJavascript(
                'wepresta_passwordless_login-front',
                'modules/' . $this->name . '/views/js/front.js',
                ['position' => 'bottom', 'priority' => 150, 'attributes' => 'defer']
            );
        }
    }

    public function hookActionCartSave(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->debug('Cart saved', ['cart_id' => $this->context->cart->id]);
    }

    public function hookActionValidateOrder(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        /** @var Order $order */
        $order = $params['order'] ?? null;

        if ($order) {
            $this->debug('Order validated', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'total' => $order->total_paid,
            ]);
        }
    }

    public function hookActionCustomerAccountAdd(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        /** @var Customer $customer */
        $customer = $params['newCustomer'] ?? null;

        if ($customer) {
            $this->debug('Customer registered', ['customer_id' => $customer->id]);
        }
    }

    // =========================================================================
    // HOOKS BACK-OFFICE
    // =========================================================================

    public function hookActionAdminControllerSetMedia(array $params): void
    {
        if ($this->isConfigurationPage()) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    public function hookActionObjectProductAddAfter(array $params): void
    {
        /** @var Product $product */
        $product = $params['object'] ?? null;

        if ($product) {
            $this->debug('Product added', ['product_id' => $product->id]);
        }
    }

    public function hookActionObjectProductUpdateAfter(array $params): void
    {
        /** @var Product $product */
        $product = $params['object'] ?? null;

        if ($product) {
            $this->debug('Product updated', ['product_id' => $product->id]);
            $this->clearModuleCache();
        }
    }

    // =========================================================================
    // MÉTHODES UTILITAIRES
    // =========================================================================

    /**
     * Vérifie si le module est activé
     */
    public function isActive(): bool
    {
        return (bool) Configuration::get('WEPRESTA_PASSWORDLESS_LOGIN_ACTIVE');
    }

    /**
     * Vérifie si on est sur la page de configuration du module
     */
    private function isConfigurationPage(): bool
    {
        return $this->context->controller instanceof AdminModulesController
            && Tools::getValue('configure') === $this->name;
    }

    /**
     * Accès à l'adaptateur de configuration (avec cache)
     */
    public function getConfig(): ConfigurationAdapter
    {
        if ($this->config === null) {
            $this->config = new ConfigurationAdapter();
        }
        return $this->config;
    }

    /**
     * Récupère un service du conteneur Symfony
     */
    public function getService(string $serviceId): ?object
    {
        try {
            $container = $this->getContainer();
            if ($container && $container->has($serviceId)) {
                return $container->get($serviceId);
            }
        } catch (\Exception $e) {
            $this->log('Service not found: ' . $serviceId, 2);
        }
        return null;
    }

    /**
     * Vide le cache du module
     */
    public function clearModuleCache(): void
    {
        $this->_clearCache('*');

        // Clear Symfony cache if available
        $cacheDir = _PS_CACHE_DIR_ . 'smarty/compile/';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob($cacheDir . $this->name . '_*') ?: []);
        }
    }

    /**
     * Log avec contexte
     */
    public function log(string $message, int $severity = 1, array $context = []): void
    {
        $formattedMessage = '[' . $this->name . '] ' . $message;

        if (!empty($context)) {
            $formattedMessage .= ' | ' . json_encode($context);
        }

        PrestaShopLogger::addLog(
            $formattedMessage,
            $severity,
            null,
            'Module',
            $this->id
        );
    }

    /**
     * Log de debug (seulement si mode debug actif)
     */
    private function debug(string $message, array $context = []): void
    {
        if ((bool) Configuration::get('WEPRESTA_PASSWORDLESS_LOGIN_DEBUG')) {
            $this->log('[DEBUG] ' . $message, 1, $context);
        }
    }

    /**
     * Retourne le chemin du module
     */
    public function getModulePath(): string
    {
        return $this->getLocalPath();
    }

    /**
     * Check si le module est correctement configuré
     */
    public function isConfigured(): bool
    {
        return !empty(Configuration::get('WEPRESTA_PASSWORDLESS_LOGIN_TITLE'));
    }

    /**
     * Génère une clé de cache unique
     */
    protected function getCacheId($name = null)
    {
        return $this->name . '_' . (string) $name . '_' . $this->context->shop->id;
    }
}

// Alias pour PrestaShop: Module::getInstanceByName() cherche la classe avec le nom exact du module (snake_case)
// Cette ligne crée un alias 'wepresta_passwordless_login' -> 'Wepresta_Passwordless_Login' pour que class_exists() fonctionne
if (!class_exists('wepresta_passwordless_login', false)) {
    class_alias('Wepresta_Passwordless_Login', 'wepresta_passwordless_login');
}
