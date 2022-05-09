<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\ApplePay;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\MediaService;
use CheckoutCom\Shopware6\Struct\SystemConfig\ApplePaySettingStruct;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
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
        $applePayKeyMedia = $this->getAppleKeyMedia($context);
        $applePayPemMedia = $this->getApplePemMedia($context);

        try {
            $shopName = $this->systemConfigService->getString('core.basicInformation.shopName', $context->getSalesChannelId());
            $applePaySettings = $this->getApplePaySettings($context);

            $response = $this->guzzleClient->post(
                $appleValidateUrl,
                [
                    RequestOptions::SSL_KEY => $this->mediaService->getPathVideoMedia($applePayKeyMedia),
                    RequestOptions::CERT => $this->mediaService->getPathVideoMedia($applePayPemMedia),
                    RequestOptions::BODY => json_encode([
                        'displayName' => $shopName,
                        'merchantIdentifier' => $applePaySettings->getMerchantId(),
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
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'response' => $exception->getResponse()->getBody()->getContents(),
                ]
            );

            throw new CheckoutComException(
                sprintf('Apple Pay merchant validation failed, code: %s', $exception->getCode())
            );
        } catch (Exception $exception) {
            $this->logger->error(
                'Unknown Error when validating merchant',
                [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                ]
            );

            throw new CheckoutComException('Unknown Error when validating merchant');
        }
    }

    public function getAppleKeyMedia(SalesChannelContext $context): MediaEntity
    {
        $applePaySettings = $this->getApplePaySettings($context);
        if ($applePaySettings->getKeyMediaId() === null) {
            $message = 'Apple Pay Key Certificate Media ID not found';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        return $this->mediaService->getMedia(
            $applePaySettings->getKeyMediaId(),
            new Criteria(),
            $context->getContext()
        );
    }

    public function getApplePemMedia(SalesChannelContext $context): MediaEntity
    {
        $applePaySettings = $this->getApplePaySettings($context);
        if ($applePaySettings->getPemMediaId() === null) {
            $message = 'Apple Pay Pem Certificate Media ID not found';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        return $this->mediaService->getMedia(
            $applePaySettings->getPemMediaId(),
            new Criteria(),
            $context->getContext()
        );
    }

    public function getAppleDomainMedia(SalesChannelDomainEntity $salesChannelDomain, SalesChannelContext $context): MediaEntity
    {
        $applePaySettings = $this->getApplePaySettings($context);
        $domainMedias = $applePaySettings->getDomainMedias();
        if (empty($domainMedias)) {
            $message = 'Apple Pay Settings Domain empty';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        $mediaIdKey = array_search(
            $salesChannelDomain->getId(),
            array_column($domainMedias, 'domainId'),
            true
        );
        if ($mediaIdKey === false) {
            $message = 'Apple Pay Domain Media ID not found';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        $mediaId = $domainMedias[$mediaIdKey]['mediaId'] ?? null;
        if ($mediaId === null) {
            $message = 'Apple Pay Domain Media ID is empty';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        return $this->mediaService->getMedia(
            $mediaId,
            new Criteria(),
            $context->getContext()
        );
    }

    private function getApplePaySettings(SalesChannelContext $context): ApplePaySettingStruct
    {
        $settings = $this->settingsFactory->getPaymentMethodSettings(
            ApplePaySettingStruct::class,
            $context->getSalesChannelId()
        );

        if (!$settings instanceof ApplePaySettingStruct) {
            $message = 'Apple Pay settings not found';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        return $settings;
    }
}
