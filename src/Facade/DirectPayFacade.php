<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Facade;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Service\Cart\AbstractCartBackupService;
use CheckoutCom\Shopware6\Service\Cart\AbstractCartService;
use CheckoutCom\Shopware6\Service\ContextService;
use CheckoutCom\Shopware6\Service\CountryService;
use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Service\ShippingMethodService;
use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;
use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingPayloadStruct;
use CheckoutCom\Shopware6\Struct\Request\RegisterAndLoginGuestRequest;
use CheckoutCom\Shopware6\Struct\Response\DirectProcessResponse;
use CheckoutCom\Shopware6\Struct\Response\DirectShippingResponse;
use Exception;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

/**
 * Handle business logic of the direct pay
 */
class DirectPayFacade
{
    private RouterInterface $router;

    private PaymentService $paymentService;

    private LoggerService $logger;

    private OrderService $orderService;

    private ContextService $contextService;

    private AbstractCartService $cartService;

    private AbstractCartBackupService $cartBackupService;

    private ShippingMethodService $shippingMethodService;

    private PaymentMethodService $paymentMethodService;

    private CountryService $countryService;

    private CustomerService $customerService;

    public function __construct(
        RouterInterface $router,
        PaymentService $paymentService,
        LoggerService $loggerService,
        OrderService $orderService,
        ContextService $contextService,
        AbstractCartService $cartService,
        AbstractCartBackupService $cartBackupService,
        ShippingMethodService $shippingMethodService,
        PaymentMethodService $paymentMethodService,
        CountryService $countryService,
        CustomerService $customerService
    ) {
        $this->router = $router;
        $this->paymentService = $paymentService;
        $this->logger = $loggerService;
        $this->orderService = $orderService;
        $this->contextService = $contextService;
        $this->cartService = $cartService;
        $this->cartBackupService = $cartBackupService;
        $this->shippingMethodService = $shippingMethodService;
        $this->paymentMethodService = $paymentMethodService;
        $this->countryService = $countryService;
        $this->customerService = $customerService;
    }

    /**
     * When a product is added to the cart, it will back up the original cart
     * Also create a new cart to add products to this cart (direct cart)
     * Basically, we will have 3 carts:
     *  - Current process cart (All payment/calculate/... will process in this cart)
     *  - Backup original cart
     *  - Our own cart (direct cart)
     */
    public function addProductToCart(string $productId, int $productQuantity, SalesChannelContext $context): Cart
    {
        $originalCart = $this->cartService->getCart($context->getToken(), $context);

        // Backup original cart using own cart token key
        $this->cartBackupService->cloneCartAndSave(
            $originalCart,
            $this->cartBackupService->getBackupCartTokenKey($context),
            $context
        );

        $directCart = $this->cartBackupService->createNewDirectTokenCart($context);

        // add new product to direct cart
        return $this->cartService->addProductToCart($productId, $productQuantity, $directCart, $context);
    }

    /**
     * Clear all the back-up carts
     */
    public function removeBackupCarts(?string $directCartToken, SalesChannelContext $context): void
    {
        if (!empty($directCartToken)) {
            $this->cartBackupService->deleteCart($directCartToken, $context);
        }

        $this->cartBackupService->deleteCart($this->cartBackupService->getBackupCartTokenKey($context), $context);
    }

    public function getShippingMethodsResponse(RequestDataBag $data, SalesChannelContext $context): DirectShippingResponse
    {
        try {
            $cartToken = $data->get('cartToken');
            $countryCode = $data->get('countryCode');
            $paymentMethodType = $data->get('paymentMethodType');

            // Need to switch to our direct cart to handle the cart in the context
            $this->cartBackupService->copyDirectCartToCartContext($cartToken, $context);

            $paymentHandler = $this->paymentMethodService->getPaymentHandlersByType($paymentMethodType);

            $country = $this->countryService->getCountryByIsoCode($countryCode, $context->getContext());

            $shippingMethods = $this->getShippingOptions($paymentHandler, $country, $context);

            $directShippingPayload = $this->getDirectShippingPayload($paymentHandler, $shippingMethods, $context);

            return new DirectShippingResponse(true, $directShippingPayload);
        } catch (Throwable $exception) {
            $this->logger->critical('Get the direct shipping methods and calculate the direct cart failed', ['message' => $exception->getMessage()]);

            throw new CheckoutComException('Get the direct shipping methods and calculate the direct cart failed');
        } finally {
            // Switch back to the origin cart
            $this->cartBackupService->copyOriginCartToCartContext($context);
        }
    }

    public function updateShippingPayloadResponse(RequestDataBag $data, SalesChannelContext $context): DirectShippingResponse
    {
        try {
            $cartToken = $data->get('cartToken');
            $shippingMethodId = $data->get('shippingMethodId');
            $paymentMethodType = $data->get('paymentMethodType');

            // Need to switch to our direct cart to handle the cart in the context
            $this->cartBackupService->copyDirectCartToCartContext($cartToken, $context);

            $paymentHandler = $this->paymentMethodService->getPaymentHandlersByType($paymentMethodType);

            $context = $this->cartService->updateContextShippingMethod($context, $shippingMethodId);

            $directShippingPayload = $this->getDirectShippingPayload($paymentHandler, null, $context);

            return new DirectShippingResponse(true, $directShippingPayload);
        } catch (Throwable $exception) {
            $this->logger->critical('Calculate the direct cart for the specific shipping method failed', ['message' => $exception->getMessage()]);

            throw new CheckoutComException('Calculate the direct cart for the specific shipping method failed');
        } finally {
            // Switch back to the origin cart
            $this->cartBackupService->copyOriginCartToCartContext($context);
        }
    }

    /**
     * Get all available shipping methods of our current country by payment handler
     *
     * @throws Exception
     */
    public function getShippingOptions(
        PaymentHandler $paymentHandler,
        CountryEntity $country,
        SalesChannelContext $context
    ): AbstractShippingOptionCollection {
        $currentMethodFormatted = null;
        $currentMethodID = $context->getShippingMethod()->getId();
        $context = $this->cartService->updateContextCountry($context, $country->getId());

        $shippingMethods = $this->shippingMethodService->getActiveShippingMethods($context);

        $directShippingOptions = $paymentHandler->getDirectShippingOptions();
        foreach ($shippingMethods as $shippingMethod) {
            // temporary switch to our shipping method.
            // we will then load the cart for this shipping method
            // in order to get the calculated shipping costs for this.
            $tempShippingContext = $this->cartService->updateContextShippingMethod($context, $shippingMethod->getId());
            $tempCart = $this->cartService->recalculateCart($tempShippingContext);

            $shippingCostsPrice = $this->cartService->getShippingCostsPrice($tempCart);

            $formattedShippingMethod = $paymentHandler->formatDirectShippingOption($shippingMethod, $shippingCostsPrice, $context);

            if ($shippingMethod->getId() === $currentMethodID) {
                $currentMethodFormatted = $formattedShippingMethod;
            } else {
                $directShippingOptions->add($formattedShippingMethod);
            }
        }

        // Restore our previously used shipping method
        $this->cartService->updateContextShippingMethod($context, $currentMethodID);

        // Pre-selected method always needs to be the first item in the list
        // because it will use to calculate the current direct cart
        if ($currentMethodFormatted !== null) {
            $directShippingOptions->unshift($currentMethodFormatted);
        }

        return $directShippingOptions;
    }

    /**
     * Process the direct payment
     */
    public function processPayment(SalesChannelContext $context, RequestDataBag $data): DirectProcessResponse
    {
        /** @var RequestDataBag $shippingContact */
        $shippingContact = $data->get('shippingContact');
        $paymentMethodType = $data->get('paymentMethodType');
        $cartToken = $data->get('cartToken');

        $originCartTokenKey = $this->cartBackupService->getBackupCartTokenKey($context);

        try {
            // Need to switch to our direct cart to handle the cart in the context
            $this->cartBackupService->copyDirectCartToCartContext($cartToken, $context);

            $paymentHandler = $this->paymentMethodService->getPaymentHandlersByType($paymentMethodType);
            $paymentMethod = $this->paymentMethodService->getPaymentMethodByHandlerIdentifier(
                $context->getContext(),
                $paymentHandler->getClassName(),
                true
            );

            $country = $this->countryService->getCountryByIsoCode(
                $shippingContact->get('countryCode'),
                $context->getContext()
            );

            $customer = $context->getCustomer();

            // If the customer is not logged in, we need to create and log in a new customer.
            if (!$customer instanceof CustomerEntity) {
                $countryState = null;
                if ($shippingContact->has('countryStateCode')) {
                    $countryState = $this->countryService->getCountryState(
                        $shippingContact->get('countryStateCode'),
                        $country,
                        $context->getContext()
                    );
                }

                $registerAndLoginGuestRequest = new RegisterAndLoginGuestRequest(
                    $shippingContact->get('firstName', ''),
                    $shippingContact->get('lastName', ''),
                    $shippingContact->get('email'),
                    $shippingContact->get('phoneNumber', ''),
                    $shippingContact->get('street', ''),
                    $shippingContact->get('additionalAddressLine1', ''),
                    $shippingContact->get('zipCode'),
                    $shippingContact->get('city'),
                    $countryState,
                    $country,
                );

                // Create guest customer and login
                $response = $this->customerService->registerAndLoginCustomer(
                    $registerAndLoginGuestRequest,
                    $context
                );

                $context = $this->contextService->getSalesChannelContext(
                    $context->getSalesChannel()->getId(),
                    $response->getContextToken(),
                );
            }

            $context = $this->cartService->updateContextPaymentMethod($context, $paymentMethod->getId());
        } catch (Throwable $exception) {
            $this->logger->critical('Prepare process payment failed', ['message' => $exception->getMessage()]);

            // Switch back to the origin cart
            $this->cartBackupService->copyOriginCartToCartContext($context);

            throw new CheckoutComException('Prepare process payment failed');
        }

        $response = $this->handleProcessPayment($context, $data, $country, $shippingContact);

        $originCart = $this->cartService->getCart($originCartTokenKey, $context);
        $this->cartBackupService->cloneCartAndSave($originCart, $context->getToken(), $context);

        // After the process payment is finished, we have to clear the backup for both case failure and success.
        $this->cartBackupService->deleteCart($cartToken, $context);
        $this->cartBackupService->deleteCart($originCartTokenKey, $context);

        return $response;
    }

    /**
     * @throws Exception
     */
    private function getDirectShippingPayload(
        PaymentHandler $paymentHandler,
        ?AbstractShippingOptionCollection $shippingMethods,
        SalesChannelContext $context
    ): AbstractShippingPayloadStruct {
        $cart = $this->cartService->recalculateCart($context);
        $directPayCart = CheckoutComUtil::buildDirectPayCart($cart);

        return $paymentHandler->getDirectShippingPayload($shippingMethods, $directPayCart, $context);
    }

    /**
     * Handle the process payment request.
     * Use the same logic as the Core Shopware checkout controller.
     *
     * @see \Shopware\Storefront\Controller\CheckoutController::order()
     */
    private function handleProcessPayment(SalesChannelContext $context, RequestDataBag $data, CountryEntity $country, RequestDataBag $shippingContact): DirectProcessResponse
    {
        // Have to agree to the terms of services
        // to avoid constraint violation checks when create an order
        $data->set('tos', true);
        $data->remove('shippingContact');
        $data->remove('paymentMethodType');
        $data->remove('cartToken');

        try {
            $order = $this->orderService->createOrder($country, $shippingContact, $data, $context);
        } catch (Throwable $exception) {
            $this->logger->critical('Create order failed', ['message' => $exception->getMessage()]);

            return new DirectProcessResponse(
                false,
                $this->generateUrl('frontend.checkout.confirm.page')
            );
        }

        $orderId = $order->getId();

        try {
            $finishUrl = $this->generateUrl('frontend.checkout.finish.page', ['orderId' => $orderId]);
            $errorUrl = $this->generateUrl('frontend.account.edit-order.page', ['orderId' => $orderId]);

            $response = $this->paymentService->handlePaymentByOrder($orderId, $data, $context, $finishUrl, $errorUrl);

            return new DirectProcessResponse(
                true,
                $response ? $response->getTargetUrl() : $finishUrl
            );
        } catch (Throwable $exception) {
            $this->logger->critical('Handler payment by order failed', ['message' => $exception->getMessage()]);

            return new DirectProcessResponse(
                false,
                $this->generateUrl('frontend.checkout.finish.page', ['orderId' => $orderId, 'changedPayment' => false, 'paymentFailed' => true])
            );
        }
    }

    private function generateUrl(string $name, array $parameters = []): string
    {
        return $this->router->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
