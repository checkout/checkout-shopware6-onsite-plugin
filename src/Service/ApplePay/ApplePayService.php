<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\ApplePay;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\MediaService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

class ApplePayService extends AbstractApplePayService
{
    public const MERCHANT_INITIATIVE = 'web';

    protected LoggerInterface $logger;

    protected Client $guzzleClient;

    protected SystemConfigService $systemConfigService;

    protected SettingsFactory $settingsFactory;

    protected MediaService $mediaService;

    public function __construct(
        LoggerInterface $logger,
        Client $guzzleClient,
        SystemConfigService $systemConfigService,
        SettingsFactory $settingsFactory,
        MediaService $mediaService
    ) {
        $this->logger = $logger;
        $this->guzzleClient = $guzzleClient;
        $this->systemConfigService = $systemConfigService;
        $this->settingsFactory = $settingsFactory;
        $this->mediaService = $mediaService;
    }

    public function getDecorated(): AbstractApplePayService
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Validate merchant for Apple Pay
     *
     * @throws Exception|GuzzleException
     */
    public function validateMerchant(string $appleValidateUrl, Request $request, SalesChannelContext $context): ?array
    {
        try {
            $applePayKeyMedia = $this->getAppleKeyMedia($context);
            $applePayPemMedia = $this->getApplePemMedia($context);
            $shopName = $this->systemConfigService->getString('core.basicInformation.shopName', $context->getSalesChannelId());
            $settings = $this->settingsFactory->getSettings($context->getSalesChannelId());

            $response = $this->guzzleClient->post(
                $appleValidateUrl,
                [
                    RequestOptions::SSL_KEY => $this->mediaService->getPathVideoMedia($applePayKeyMedia),
                    RequestOptions::CERT => $this->mediaService->getPathVideoMedia($applePayPemMedia),
                    RequestOptions::BODY => json_encode([
                        'displayName' => $shopName,
                        'merchantIdentifier' => $settings->getApplePayMerchantId(),
                        'initiative' => self::MERCHANT_INITIATIVE,
                        'initiativeContext' => $request->getHost(),
                    ]),
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $exception) {
            $this->logger->error(
                'Apple Pay merchant validation failed',
                [
                    'exception' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'response' => $exception->getResponse()->getBody()->getContents(),
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error(
                'Unknown Error when validating merchant',
                [
                    'exception' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ]
            );
        }

        return null;
    }

    public function getAppleKeyMedia(SalesChannelContext $context): MediaEntity
    {
        $settings = $this->settingsFactory->getSettings($context->getSalesChannelId());
        if ($settings->getApplePayKeyMediaId() === null) {
            $message = 'Apple Pay Key Certificate Media ID not found';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        return $this->mediaService->getMedia(
            $settings->getApplePayKeyMediaId(),
            new Criteria(),
            $context->getContext()
        );
    }

    public function getApplePemMedia(SalesChannelContext $context): MediaEntity
    {
        $settings = $this->settingsFactory->getSettings($context->getSalesChannelId());
        if ($settings->getApplePayPemMediaId() === null) {
            $message = 'Apple Pay Pem Certificate Media ID not found';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        return $this->mediaService->getMedia(
            $settings->getApplePayPemMediaId(),
            new Criteria(),
            $context->getContext()
        );
    }

    public function getAppleDomainMedia(SalesChannelContext $context): MediaEntity
    {
        $settings = $this->settingsFactory->getSettings($context->getSalesChannelId());
        if ($settings->getApplePayDomainMediaId() === null) {
            $message = 'Apple Pay Domain Media ID not found';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        return $this->mediaService->getMedia(
            $settings->getApplePayDomainMediaId(),
            new Criteria(),
            $context->getContext()
        );
    }
}
