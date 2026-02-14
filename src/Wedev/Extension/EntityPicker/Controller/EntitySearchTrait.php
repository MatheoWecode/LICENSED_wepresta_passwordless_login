<?php
/**
 * WEDEV Extension - EntityPicker
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\EntityPicker\Controller;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\EntityPicker\Provider\EntityProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Trait pour les contrôleurs admin qui implémentent des endpoints de recherche.
 *
 * Utilisation:
 * ```php
 * class MyController extends FrameworkBundleAdminController
 * {
 *     use EntitySearchTrait;
 *
 *     private ProductProvider $productProvider;
 *
 *     public function searchProductsAction(Request $request): JsonResponse
 *     {
 *         return $this->handleEntitySearch($request, $this->productProvider);
 *     }
 *
 *     public function fetchProductsAction(Request $request): JsonResponse
 *     {
 *         return $this->handleEntityFetch($request, $this->productProvider);
 *     }
 * }
 * ```
 */
trait EntitySearchTrait
{
    /**
     * Gère une requête de recherche d'entités.
     *
     * @param Request $request La requête HTTP
     * @param EntityProviderInterface $provider Le provider de recherche
     * @param int $minChars Nombre minimum de caractères pour la recherche
     * @param int $limit Nombre maximum de résultats
     */
    protected function handleEntitySearch(
        Request $request,
        EntityProviderInterface $provider,
        int $minChars = 2,
        int $limit = 20
    ): JsonResponse {
        $query = trim((string) $request->query->get('q', ''));

        if (strlen($query) < $minChars) {
            return new JsonResponse([]);
        }

        try {
            $results = $provider->search($query, $limit);

            return new JsonResponse($results);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gère une requête de recherche via path parameter.
     *
     * Pour les routes type: /search/{query}
     */
    protected function handleEntitySearchByPath(
        string $query,
        EntityProviderInterface $provider,
        int $minChars = 2,
        int $limit = 20
    ): JsonResponse {
        $query = trim($query);

        if (strlen($query) < $minChars) {
            return new JsonResponse([]);
        }

        try {
            $results = $provider->search($query, $limit);

            return new JsonResponse($results);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupère des entités par leurs IDs.
     *
     * Attend un body JSON avec la structure: {"ids": [1, 2, 3]}
     */
    protected function handleEntityFetch(
        Request $request,
        EntityProviderInterface $provider
    ): JsonResponse {
        try {
            $content = $request->getContent();
            $data = json_decode($content, true);

            $ids = $data['ids'] ?? [];

            if (!is_array($ids) || empty($ids)) {
                return new JsonResponse([]);
            }

            $ids = array_map('intval', $ids);
            $results = $provider->getByIds($ids);

            return new JsonResponse($results);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupère des entités via query string.
     *
     * Pour les routes type: /fetch?ids=1,2,3
     */
    protected function handleEntityFetchByQuery(
        Request $request,
        EntityProviderInterface $provider
    ): JsonResponse {
        try {
            $idsParam = $request->query->get('ids', '');

            if (empty($idsParam)) {
                return new JsonResponse([]);
            }

            $ids = array_map('intval', explode(',', $idsParam));
            $ids = array_filter($ids, fn($id) => $id > 0);

            if (empty($ids)) {
                return new JsonResponse([]);
            }

            $results = $provider->getByIds($ids);

            return new JsonResponse($results);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

