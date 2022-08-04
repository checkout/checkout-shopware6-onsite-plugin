<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Controller;

use Checkout\CheckoutAuthorizationException;
use CheckoutCom\Shopware6\Controller\WebhookController;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use CheckoutCom\Shopware6\Service\Webhook\WebhookService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class WebhookControllerTest extends TestCase
{
    protected WebhookController $webhookController;

    /**
     * @var DataValidator|MockObject
     */
    private $validator;

    /**
     * @var WebhookService|MockObject
     */
    private $webhookService;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    public function setUp(): void
    {
        $this->validator = $this->createMock(DataValidator::class);
        $this->webhookService = $this->createMock(WebhookService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->webhookController = new WebhookController(
            $this->validator,
            $this->webhookService,
            $this->logger
        );
    }

    /**
     * @dataProvider getAuthorizeData
     */
    public function testWebhooksWithNoAuthorizationToken(?string $token = null): void
    {
        static::expectException(CheckoutAuthorizationException::class);

        $request = new Request();
        $request->headers->set('Authorization', $token);

        if ($token !== null) {
            $this->webhookService->expects(static::once())->method('authenticateToken')->willReturn(false);
        }

        $this->logger->expects(static::once())->method('info');

        $this->webhookController->webhooks($request, Context::createDefaultContext());
    }

    public function getAuthorizeData(): array
    {
        return [
            'null token' => [null],
            'wrong token' => ['test'],
        ];
    }

    public function testDataValidationIncorrect(): void
    {
        static::expectException(ConstraintViolationException::class);

        $data = [
            'id' => null,
            'type' => CheckoutWebhookService::PAYMENT_VOIDED,
            'created_on' => '2019-06-07T08:36:43Z',
            'data' => ['reference' => 'test'],
        ];
        $request = new Request([], $data);
        $request->headers->set('Authorization', 'token');

        $this->webhookService->expects(static::once())->method('authenticateToken')->willReturn(true);
        $this->validator->expects(static::once())->method('validate')
            ->willThrowException(new ConstraintViolationException(
                new ConstraintViolationList([
                    new ConstraintViolation('test', '', [], null, '', null),
                ]),
                $data
            ));

        $this->logger->expects(static::once())->method('info');

        $this->webhookController->webhooks($request, Context::createDefaultContext());
    }

    public function testWebhooksWorkCorrectly(): void
    {
        $data = [
            'id' => 'id',
            'type' => CheckoutWebhookService::PAYMENT_VOIDED,
            'created_on' => '2019-06-07T08:36:43Z',
            'data' => ['reference' => 'test'],
        ];
        $request = new Request([], $data);
        $request->headers->set('Authorization', 'token');

        $this->webhookService->expects(static::once())->method('authenticateToken')->willReturn(true);
        $this->validator->expects(static::once())->method('validate');

        $this->logger->expects(static::once())->method('info');

        $response = $this->webhookController->webhooks($request, Context::createDefaultContext());

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
