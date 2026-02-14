<?php
/**
 * WEDEV Extension - UI
 *
 * @author      WePresta
 * @copyright   2024 Votre Société
 * @license     MIT
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\UI\Trait;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Symfony\Component\HttpFoundation\Request;

/**
 * Trait to handle Grid Filters according to PrestaShop 9 rules.
 */
trait GridFilterTrait
{
    /**
     * Extracts grid filters from Request.
     * 
     * Rules:
     * 1. Check POST first
     * 2. Then GET
     * 3. Filters are direct children, NOT nested under filters key
     *
     * @param Request $request
     * @param string $gridId
     * @return array
     */
    protected function getGridFilters(Request $request, string $gridId): array
    {
        // Check POST first ($request->request->all($gridId))
        if ($request->request->has($gridId)) {
            $filters = $request->request->all($gridId);
            if (is_array($filters)) {
                return $filters;
            }
        }

        // Then GET ($request->query->all($gridId))
        if ($request->query->has($gridId)) {
            $filters = $request->query->all($gridId);
            if (is_array($filters)) {
                return $filters;
            }
        }

        return [];
    }
}
