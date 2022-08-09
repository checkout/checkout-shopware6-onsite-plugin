<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi\Apm;

use Checkout\Apm\Klarna\CreditSessionRequest;
use Checkout\Apm\Klarna\OrderCaptureRequest;
use Checkout\Payments\VoidRequest;
use CheckoutCom\Shopware6\Service\CheckoutApi\Apm\CheckoutKlarnaService;
use CheckoutCom\Shopware6\Struct\PaymentMethod\Klarna\CreditSessionStruct;
use CheckoutCom\Shopware6\Tests\Services\CheckoutApi\AbstractCheckoutTest;

class CheckoutKlarnaServiceTest extends AbstractCheckoutTest
{
    protected CheckoutKlarnaService $checkoutKlarnaService;

    public function setUp(): void
    {
        parent::setUp();
        $this->checkoutKlarnaService = new CheckoutKlarnaService(
            $this->logger,
            $this->getCheckoutApiFactory()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testCreateCreditSession(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($apiShouldThrowException, 'post');

        $creditSessionRequest = new CreditSessionRequest();
        $creditSession = $this->checkoutKlarnaService->createCreditSession(
            $creditSessionRequest,
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(CreditSessionStruct::class, $creditSession);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testCapturePayment(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($apiShouldThrowException, 'post', [
            'action_id' => 'foo',
        ]);

        $orderCaptureRequest = new OrderCaptureRequest();
        $this->checkoutKlarnaService->capturePayment(
            'foo',
            $orderCaptureRequest,
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

        $voidRequest = new VoidRequest();
        $this->checkoutKlarnaService->voidPayment(
            'foo',
            $voidRequest,
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
