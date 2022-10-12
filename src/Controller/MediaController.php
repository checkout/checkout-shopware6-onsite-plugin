<?php declare(strict_types=1);

namespace Cko\Shopware6\Controller;

use Cko\Shopware6\Service\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This controller handles all relative to media administration
 * Need it for the private media storage
 * Some functions will require edit/upload/remove private media that
 * only the system or authorized users can access to edit/upload/remove
 * For Example:
 * - Apple Pay will need domain/key/pem files for the merchant
 *   so only the system can edit/upload/remove
 *
 * @RouteScope(scopes={"api"})
 */
class MediaController extends AbstractController
{
    private MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Get media entity
     *
     * @Route("/api/_action/checkout-com/media/{mediaId}", name="api.action.checkout-com.media.read", methods={"POST"})
     */
    public function getSystemMedia(string $mediaId, Context $context): JsonResponse
    {
        $media = $this->mediaService->getAdminSystemMedia($mediaId, $context);

        return new JsonResponse($media);
    }

    /**
     * Delete media entity
     *
     * @Route("/api/_action/checkout-com/media/{mediaId}", name="api.action.checkout-com.media.delete", methods={"DELETE"})
     */
    public function deleteSystemMedia(string $mediaId, Context $context): JsonResponse
    {
        $result = $this->mediaService->deleteSystemMedia($mediaId, $context);

        return new JsonResponse([
            'success' => $result,
        ]);
    }
}
