<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Controller;

use CheckoutCom\Shopware6\Service\ApplePay\AbstractApplePayService;
use CheckoutCom\Shopware6\Service\ContextService;
use CheckoutCom\Shopware6\Service\MediaService;
use League\Flysystem\FileNotFoundException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This controller handles all relative to media storefront
 *
 * @RouteScope(scopes={"storefront"})
 */
class MediaController extends StorefrontController
{
    private ContextService $contextService;

    private MediaService $mediaService;

    private AbstractApplePayService $applePayService;

    public function __construct(ContextService $contextService, MediaService $mediaService, AbstractApplePayService $applePayService)
    {
        $this->contextService = $contextService;
        $this->mediaService = $mediaService;
        $this->applePayService = $applePayService;
    }

    /**
     * Create merchant domain URL to let Apple validate the domain
     *
     * @throws FileNotFoundException
     * @Route("/.well-known/apple-developer-merchantid-domain-association.txt", name="frontend.checkout-com.merchant_domain", methods={"GET"}, defaults={"auth_required"=false})
     */
    public function publicMerchantDomainFile(SalesChannelContext $context): Response
    {
        $domain = $this->contextService->getSalesChannelDomain($context->getDomainId(), $context);

        $media = $this->applePayService->getAppleDomainMedia($domain, $context);
        $path = $this->mediaService->getPathVideoMedia($media);
        $response = $this->mediaService->getStreamResponse($path);

        $response->headers->set('Content-Type', $media->getMimeType());

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            sprintf('%s.%s', $media->getFileName(), $media->getFileExtension())
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
