<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\Apm\RequestIdealSource;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class IdealHandler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.idealLabel';
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$ideal;
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildIDealSource($dataBag, $order);

        return $paymentRequest;
    }

    /**
     * Build request source to call the Checkout.com API
     */
    private function buildIDealSource(RequestDataBag $dataBag, OrderEntity $order): RequestIdealSource
    {
        $bic = RequestUtil::getBic($dataBag);
        $definition = $this->getValidationDefinition();
        $this->dataValidator->validate([RequestUtil::DATA_BIC => $bic], $definition);

        $orderNumber = $this->orderExtractor->extractOrderNumber($order);

        $request = new RequestIdealSource();
        $request->bic = (string) $bic;
        $request->description = \sprintf('ORD%s', $orderNumber);

        return $request;
    }

    private function getValidationDefinition(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.payment_handler.ideal');
        $definition->add(RequestUtil::DATA_BIC, new Type('string'), new NotBlank());

        return $definition;
    }
}
