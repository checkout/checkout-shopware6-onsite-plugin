<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\ApplePay;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\MediaService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ApplePayService
{
    protected LoggerInterface $logger;

    protected SettingsFactory $settingsFactory;

    protected MediaService $mediaService;

    public function __construct(
        LoggerInterface $logger,
        SettingsFactory $settingsFactory,
        MediaService $mediaService
    ) {
        $this->logger = $logger;
        $this->settingsFactory = $settingsFactory;
        $this->mediaService = $mediaService;
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
