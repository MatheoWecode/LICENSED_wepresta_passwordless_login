<?php
/**
 * Passwordless Login - Auth Front Controller
 * Serves the fullscreen login page (no header/footer)
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

class Wepresta_Passwordless_LoginAuthModuleFrontController extends ModuleFrontController
{
    public $php_self = 'auth';

    public function init(): void
    {
        parent::init();

        // If already logged in, redirect
        if ($this->context->customer && $this->context->customer->isLogged()) {
            $backUrl = Tools::getValue('back');
            if ($backUrl) {
                Tools::redirect($backUrl);
            }
            Tools::redirect('index.php?controller=my-account');
        }

        // Check module is active
        if (!Configuration::get('WEPRESTA_PL_ACTIVE')) {
            Tools::redirect('index.php?controller=authentication');
        }
    }

    public function setMedia(): void
    {
        parent::setMedia();

        $modulePath = 'modules/' . $this->module->name . '/views/';

        $this->registerStylesheet(
            'wepresta-pl-auth',
            $modulePath . 'css/front.css',
            ['media' => 'all', 'priority' => 50]
        );

        // Load all JS chunks from entrypoints.json (runtime + vendors + front)
        $entrypointsFile = $this->module->getLocalPath() . 'views/js/entrypoints.json';
        if (file_exists($entrypointsFile)) {
            $entrypoints = json_decode(file_get_contents($entrypointsFile), true);
            $jsFiles = $entrypoints['entrypoints']['front']['js'] ?? [];
            foreach ($jsFiles as $index => $jsFile) {
                $relativePath = ltrim($jsFile, '/');
                $this->registerJavascript(
                    'wepresta-pl-front-' . $index,
                    $relativePath,
                    ['position' => 'bottom', 'priority' => 50 + $index]
                );
            }
        } else {
            // Fallback: load front.js directly
            $this->registerJavascript(
                'wepresta-pl-auth',
                $modulePath . 'js/front.js',
                ['position' => 'bottom', 'priority' => 50]
            );
        }
    }

    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->trans('Sign in', [], 'Modules.Weprestapasswordlesslogin.Front');
        $page['body_classes']['page-passwordless-login'] = true;
        return $page;
    }

    public function initContent(): void
    {
        parent::initContent();

        $backUrl = Tools::getValue('back', '');
        $orderPageLink = $this->context->link->getPageLink('order', true);
        $fromCheckout = !empty($backUrl) && (
            strpos($backUrl, 'controller=order') !== false
            || strpos($backUrl, rtrim($orderPageLink, '/')) !== false
        );

        // Shop logo
        $shopLogoUrl = '';
        $psLogo = Configuration::get('PS_LOGO');
        if ($psLogo) {
            $shopLogoUrl = $this->context->link->getMediaLink(_PS_IMG_ . $psLogo);
        }

        $this->context->smarty->assign([
            'pl_api_url' => $this->context->link->getModuleLink($this->module->name, 'api'),
            'pl_csrf_token' => Tools::getToken(false),
            'pl_google_enabled' => (bool) Configuration::get('WEPRESTA_PL_GOOGLE_ENABLED'),
            'pl_google_client_id' => Configuration::get('WEPRESTA_PL_GOOGLE_CLIENT_ID') ?: '',
            'pl_show_classic_login' => (bool) Configuration::get('WEPRESTA_PL_SHOW_CLASSIC_LOGIN'),
            'pl_show_logo' => (bool) Configuration::get('WEPRESTA_PL_SHOW_LOGO'),
            'pl_shop_name' => Configuration::get('PS_SHOP_NAME') ?: '',
            'pl_shop_logo_url' => $shopLogoUrl,
            'pl_resend_countdown' => (int) Configuration::get('WEPRESTA_PL_RESEND_COUNTDOWN') ?: 60,
            'pl_back_url' => $backUrl,
            'pl_from_checkout' => $fromCheckout,
            'pl_login_url' => $this->context->link->getPageLink('authentication'),
        ]);

        $this->setTemplate('module:wepresta_passwordless_login/views/templates/front/auth-fullscreen.tpl');
    }

    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->trans('Sign in', [], 'Modules.Weprestapasswordlesslogin.Front'),
            'url' => $this->context->link->getModuleLink($this->module->name, 'auth'),
        ];

        return $breadcrumb;
    }
}
