<?php
/**
 * Dashboard Controller - Module Admin Entry Point
 *
 * @author      WePresta
 * @copyright   2024 Votre Société
 * @license     MIT
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Presentation\Controller\Admin;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Dashboard controller for module administration.
 * 
 * This is the main entry point for the module's back-office interface.
 * Compatible with PrestaShop 8.x and 9.x.
 */
class DashboardController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Display the main dashboard page.
     */
    #[AdminSecurity("is_granted('read', 'AdminModules')", redirectRoute: 'admin_module_manage')]
    public function index(): Response
    {
        return $this->render('@Modules/wepresta_passwordless_login/views/templates/admin/dashboard.html.twig', [
            'moduleVersion' => '1.0.0',
            'moduleName' => 'wepresta_passwordless_login',
            'layoutTitle' => $this->translator->trans('Dashboard', [], 'Modules.Wepresta\Passwordless\Login.Admin'),
        ]);
    }
}

