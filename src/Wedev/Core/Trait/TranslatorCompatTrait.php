<?php
/**
 * WEDEV Core - TranslatorCompatTrait
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 2.0.0
 * @deprecated Use TranslatorInterface injection instead
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Trait;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Trait for PrestaShop 8/9 translator compatibility.
 *
 * ⚠️ DEPRECATED: This trait is deprecated since version 2.0.0
 * 
 * In PrestaShop 8+, the "translator" service is not available in the controller's
 * service locator. Use dependency injection instead:
 *
 * @example Recommended approach (Dependency Injection)
 * ```php
 * use Symfony\Contracts\Translation\TranslatorInterface;
 * 
 * class MyController extends FrameworkBundleAdminController
 * {
 *     public function __construct(
 *         private readonly TranslatorInterface $translator
 *     ) {
 *     }
 *
 *     public function index(): Response
 *     {
 *         $title = $this->translator->trans('Dashboard', [], 'Modules.MyModule.Admin');
 *         return $this->render('...', ['title' => $title]);
 *     }
 * }
 * ```
 * 
 * @deprecated Use TranslatorInterface injection - this trait will cause errors in PS8+
 */
trait TranslatorCompatTrait
{
    /**
     * Translate a message with PrestaShop 8/9 compatibility.
     *
     * @deprecated Use $this->translator->trans() with injected TranslatorInterface instead
     * @param string $id The message ID to translate
     * @param string $domain The translation domain (default: 'Admin.Global')
     * @param array $parameters Parameters to replace in the message
     * @return string The translated message
     */
    protected function translate(string $id, string $domain = 'Admin.Global', array $parameters = []): string
    {
        // ⚠️ This will fail in PS8+ because "translator" service is not in the service locator
        // Use TranslatorInterface injection instead
        return $this->trans($id, $domain, $parameters);
    }

    /**
     * Alias for translate() method.
     *
     * @deprecated Use $this->translator->trans() with injected TranslatorInterface instead
     * @param string $id The message ID to translate
     * @param string $domain The translation domain
     * @param array $parameters Parameters to replace in the message
     * @return string The translated message
     */
    protected function t(string $id, string $domain = 'Admin.Global', array $parameters = []): string
    {
        return $this->translate($id, $domain, $parameters);
    }
}

