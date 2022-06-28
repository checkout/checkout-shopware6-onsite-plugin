<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\CheckoutApi\Apm;

use Checkout\Apm\Klarna\CreditSessionRequest;
use Checkout\Apm\Klarna\Klarna;
use Checkout\Apm\Klarna\OrderCaptureRequest;
use Checkout\CheckoutApiException;
use Checkout\Common\Country;
use Checkout\Common\Currency;
use Checkout\Payments\VoidRequest;
use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Factory\CheckoutApiFactory;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Service\CheckoutApi\AbstractCheckoutService;
use CheckoutCom\Shopware6\Service\Extractor\AbstractOrderExtractor;
use CheckoutCom\Shopware6\Service\Klarna\KlarnaService;
use CheckoutCom\Shopware6\Struct\LineItemTotalPrice;
use CheckoutCom\Shopware6\Struct\PaymentMethod\Klarna\CreditSessionStruct;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CheckoutKlarnaService extends AbstractCheckoutService
{
    private KlarnaService $klarnaService;

    private AbstractOrderExtractor $orderExtractor;

    public function __construct(
        LoggerInterface $logger,
        CheckoutApiFactory $checkoutApiFactory,
        KlarnaService $klarnaService,
        AbstractOrderExtractor $orderExtractor
    ) {
        parent::__construct($logger, $checkoutApiFactory);

        $this->klarnaService = $klarnaService;
        $this->orderExtractor = $orderExtractor;
    }

    /**
     * Call Checkout Klarna API to create credit session
     *
     * @throws CheckoutApiException
     */
    public function createCreditSession(LineItemTotalPrice $lineItemTotalPrice, SalesChannelContext $context): CreditSessionStruct
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($context->getSalesChannelId());

        try {
            $response = $checkoutApi->getKlarnaClient()->createCreditSession(
                $this->buildCreditSessionRequest($lineItemTotalPrice, $context)
            );

            return (new CreditSessionStruct())->assign($response);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Call Checkout Klarna API to capture the payment
     *
     * @throws CheckoutApiException
     */
    public function capturePayment(string $paymentId, string $salesChannelId, OrderEntity $order): CreditSessionStruct
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $response = $checkoutApi->getKlarnaClient()->capturePayment(
                $paymentId,
                $this->buildCapturePaymentRequest($order)
            );

            return (new CreditSessionStruct())->assign($response);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Call Checkout Klarna API to void the payment
     *
     * @throws CheckoutApiException
     */
    public function voidPayment(string $paymentId, string $salesChannelId, OrderEntity $order): CreditSessionStruct
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $response = $checkoutApi->getKlarnaClient()->voidPayment(
                $paymentId,
                $this->buildVoidPaymentRequest($order)
            );

            return (new CreditSessionStruct())->assign($response);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Build request data to create the credit session
     */
    private function buildCreditSessionRequest(LineItemTotalPrice $lineItemTotalPrice, SalesChannelContext $context): CreditSessionRequest
    {
        $lineItems = $lineItemTotalPrice->getLineItems();
        if ($lineItems === null) {
            throw new CheckoutComException('Line items are not set');
        }

        $currency = $context->getCurrency();

        /** @var Currency $requestCurrency */
        $requestCurrency = $currency->getIsoCode();
        $cartPrice = $lineItemTotalPrice->getPrice();

        /** @var Country $purchaseCountry */
        $purchaseCountry = $this->klarnaService->getPurchaseCountryIsoCodeFromContext($context);

        $request = new CreditSessionRequest();
        $request->purchase_country = $purchaseCountry;
        $request->currency = $requestCurrency;
        $request->locale = $this->klarnaService->getLocaleFromLanguageId($context);
        $request->amount = CheckoutComUtil::formatPriceCheckout($cartPrice->getTotalPrice(), $currency->getIsoCode());
        $request->tax_amount = CheckoutComUtil::formatPriceCheckout(
            $cartPrice->getCalculatedTaxes()->getAmount(),
            $currency->getIsoCode()
        );
        $request->products = $this->klarnaService->buildProductData($lineItemTotalPrice, $currency->getIsoCode());

        return $request;
    }

    /**
     * Build request data to capture the payment
     */
    private function buildCapturePaymentRequest(OrderEntity $order): OrderCaptureRequest
    {
        $currency = $this->orderExtractor->extractCurrency($order);

        $klarna = new Klarna();
        $klarna->description = CheckoutComUtil::buildReference($order);
        $klarna->products = $this->klarnaService->buildProductData(
            CheckoutComUtil::buildLineItemTotalPrice($order),
            $currency->getIsoCode()
        );
        $klarna->shipping_info = $this->klarnaService->buildShippingInfo(
            $this->orderExtractor->extractOrderDelivery($order),
            $this->orderExtractor->extractOrderShippingMethod($order)
        );
        $klarna->shipping_delay = 0;

        $request = new OrderCaptureRequest();
        $request->amount = CheckoutComUtil::formatPriceCheckout($order->getAmountTotal(), $currency->getIsoCode());
        $request->reference = (int) $this->orderExtractor->extractOrderNumber($order);
        $request->klarna = $klarna;

        return $request;
    }

    /**
     * Build request data to void the payment
     */
    private function buildVoidPaymentRequest(OrderEntity $order): VoidRequest
    {
        $request = new VoidRequest();
        $request->reference = $this->orderExtractor->extractOrderNumber($order);

        return $request;
    }
}
