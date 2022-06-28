<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Struct\Response\ConfigResponse;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 */
class ConfigController extends AbstractConfigController
{
    private SettingsFactory $settingsFactory;

    public function __construct(SettingsFactory $settingsFactory)
    {
        $this->settingsFactory = $settingsFactory;
    }

    public function getDecorated(): AbstractConfigController
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Returns the configs of checkout.com plugin
     *
     * @OA\Get(
     *      path="/checkout-com/config",
     *      summary="Returns the configs of checkout.com plugin",
     *      description="Returns the configs of checkout.com plugin",
     *      operationId="checkoutComGetPublicConfig",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\Response(
     *          response="200",
     *          description="Returns the configs of checkout.com plugin",
     *         @OA\JsonContent(ref="#/components/schemas/checkout_com_config_response")
     *     )
     * )
     * @Route("/store-api/checkout-com/config", name="store-api.checkout-com.config", methods={"GET"})
     */
    public function getPublicConfig(SalesChannelContext $context): ConfigResponse
    {
        return new ConfigResponse(
            $this->settingsFactory->getPublicConfig($context->getSalesChannelId())
        );
    }
}
