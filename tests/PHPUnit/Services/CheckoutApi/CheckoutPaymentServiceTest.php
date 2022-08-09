<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\RefundRequest;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;

class CheckoutPaymentServiceTest extends AbstractCheckoutTest
{
    protected CheckoutPaymentService $checkoutPaymentService;

    public function setUp(): void
    {
        parent::setUp();
        $this->checkoutPaymentService = new CheckoutPaymentService(
            $this->logger,
            $this->getCheckoutApiFactory()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRequestPayment(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($apiShouldThrowException, 'post');

        $paymentRequest = new PaymentRequest();
        $payment = $this->checkoutPaymentService->requestPayment(
            $paymentRequest,
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(Payment::class, $payment);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testGetPaymentDetails(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($apiShouldThrowException, 'get');

        $payment = $this->checkoutPaymentService->getPaymentDetails(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(Payment::class, $payment);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testGetPaymentActions(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($apiShouldThrowException, 'get');

        $expect = $this->checkoutPaymentService->getPaymentActions(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertIsArray($expect);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testCapturePayment(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($apiShouldThrowException, 'post', [
            'action_id' => 'foo',
        ]);

        $this->checkoutPaymentService->capturePayment(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testVoidPayment(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($apiShouldThrowException, 'post', [
            'action_id' => 'foo',
        ]);

        $this->checkoutPaymentService->voidPayment(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRefundPayment(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($apiShouldThrowException, 'post', [
            'action_id' => 'foo',
        ]);

        $refundRequest = new RefundRequest();

        $this->checkoutPaymentService->refundPayment(
            'foo',
            $refundRequest,
            $this->salesChannelContext->getSalesChannelId()
        );
    }

    public function requestCheckoutApiProvider(): array
    {
        return [
            'Test throw checkout api exception' => [
                true,
            ],
            'Test call api successful' => [
                false,
            ],
        ];
    }
}
