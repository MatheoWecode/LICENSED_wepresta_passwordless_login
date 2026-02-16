<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Presentation\Controller\Admin;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ConfigureRedirectController extends FrameworkBundleAdminController
{
    #[AdminSecurity("is_granted('read', 'AdminModules')", redirectRoute: 'admin_module_manage')]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('admin_module_configure_action', [
            'module_name' => 'wepresta_passwordless_login',
        ]);
    }
}
