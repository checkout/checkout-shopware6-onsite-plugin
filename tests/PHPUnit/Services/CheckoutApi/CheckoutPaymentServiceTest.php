<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi;

use Checkout\Payments\PaymentRequest;
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
    public function testRequestPayment(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'post');

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
    public function testGetPaymentDetails(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'get');

        $payment = $this->checkoutPaymentService->getPaymentDetails(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(Payment::class, $payment);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testCapturePayment(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'post');

        $this->checkoutPaymentService->capturePayment(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRefundPayment(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'post');

        $this->checkoutPaymentService->refundPayment(
            'foo',
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
