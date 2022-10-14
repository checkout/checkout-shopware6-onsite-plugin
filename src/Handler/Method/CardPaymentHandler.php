<?php declare(strict_types=1);

namespace Cko\Shopware6\Handler\Method;

use Checkout\Common\ChallengeIndicatorType;
use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\AbstractRequestSource;
use Checkout\Payments\Previous\Source\RequestIdSource;
use Checkout\Payments\Previous\Source\RequestTokenSource;
use Checkout\Payments\ThreeDsRequest;
use Cko\Shopware6\Exception\CheckoutComException;
use Cko\Shopware6\Exception\CheckoutInvalidTokenException;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Helper\CheckoutComUtil;
use Cko\Shopware6\Helper\RequestUtil;
use Cko\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Cko\Shopware6\Struct\SystemConfig\CardPaymentSettingStruct;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CardPaymentHandler extends PaymentHandler
{
    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'Card Payments');

        return $displayNames;
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$card;
    }

    public function canManualCapture(SalesChannelContext $context): bool
    {
        $settings = $this->getPaymentMethodSettings($context);

        return $settings->isManualCapture();
    }

    /**
     * @throws Exception
     */
    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
        $this->enableThreeDsRequest($dataBag, $paymentRequest, $context->getSalesChannelId());

        $paymentRequest->source = $this->buildSource($dataBag, $order, $context);

        return $paymentRequest;
    }

    public function enableThreeDsRequest(RequestDataBag $dataBag, PaymentRequest $paymentRequest, string $salesChannelId): PaymentRequest
    {
        $shouldSaveSource = RequestUtil::getShouldSaveSource($dataBag);
        if (!$shouldSaveSource) {
            return parent::enableThreeDsRequest($dataBag, $paymentRequest, $salesChannelId);
        }

        // If the customer want to save their card details, we have to enable 3ds by default
        $threeDs = new ThreeDsRequest();
        $threeDs->enabled = true;
        $threeDs->challenge_indicator = ChallengeIndicatorType::$challenge_requested_mandate;
        $paymentRequest->three_ds = $threeDs;

        return $paymentRequest;
    }

    /**
     * Build source to call the Checkout.com API
     *
     * @throws Exception
     */
    private function buildSource(RequestDataBag $dataBag, OrderEntity $order, SalesChannelContext $context): AbstractRequestSource
    {
        $requestIdSource = $this->getRequestIdSource($dataBag);

        // If the data bag has a SourceId, we use it to build source
        if ($requestIdSource instanceof RequestIdSource) {
            return $requestIdSource;
        }

        $token = RequestUtil::getTokenPayment($dataBag);
        if (!\is_string($token)) {
            throw new CheckoutInvalidTokenException(static::getPaymentMethodType());
        }

        $billingAddress = $this->orderExtractor->extractBillingAddress($order, $context);

        $requestTokenSource = new RequestTokenSource();
        $requestTokenSource->token = $token;
        $requestTokenSource->billing_address = CheckoutComUtil::buildAddress($billingAddress);

        return $requestTokenSource;
    }

    private function getPaymentMethodSettings(SalesChannelContext $context): CardPaymentSettingStruct
    {
        $settings = $this->settingsFactory->getPaymentMethodSettings(
            CardPaymentSettingStruct::class,
            $context->getSalesChannelId()
        );

        if (!$settings instanceof CardPaymentSettingStruct) {
            $message = 'Card Payment settings not found';
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        return $settings;
    }
}
