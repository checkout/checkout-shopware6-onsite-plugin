<?php
declare(strict_types=1);

namespace Cko\Shopware6\Controller;

use Checkout\CheckoutAuthorizationException;
use Cko\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use Cko\Shopware6\Service\Webhook\AbstractWebhookService;
use Cko\Shopware6\Struct\WebhookReceiveDataStruct;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * This controller provides a webhook API to handle all Checkout's event
 *
 * @RouteScope(scopes={"api"})
 */
class WebhookController extends AbstractController
{
    private DataValidator $validator;

    private AbstractWebhookService $webhookService;

    private LoggerInterface $logger;

    public function __construct(DataValidator $validator, AbstractWebhookService $webhookService, LoggerInterface $logger)
    {
        $this->validator = $validator;
        $this->webhookService = $webhookService;
        $this->logger = $logger;
    }

    /**
     * Webhook to handle all Checkout's event
     * Do not change the route name, it's fixed with const CheckoutWebhookService::WEBHOOK_ROUTE_NAME
     *
     * @Route("/api/_action/checkout-com/payment/webhooks", name="api.action.checkout-com.payment.webhooks", defaults={"auth_required"=false}, methods={"POST"})
     *
     * @throws CheckoutAuthorizationException
     * @throws Exception
     */
    public function webhooks(Request $request, Context $context): JsonResponse
    {
        $this->logger->info('Received a checkout webhook event', $request->request->all());

        $token = $request->headers->get('Authorization');
        $salesChannelId = (string) $request->query->get('salesChannelId');

        // authenticate the request
        if ($token === null || !$this->webhookService->authenticateToken($token, $salesChannelId)) {
            throw new CheckoutAuthorizationException('Authorization token is invalid');
        }

        $data = $request->request->all();
        $this->validateWebhookData($data);

        $data = $this->buildWebhookReceiveData($data);

        $this->webhookService->handle($data, $context, $salesChannelId);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function validateWebhookData(array $data): void
    {
        $definition = new DataValidationDefinition();

        $definition->add('id', new NotBlank(), new Type('string'))
            ->add('type', new NotBlank(), new Type('string'), new Choice(CheckoutWebhookService::WEBHOOK_EVENTS))
            ->add('created_on', new NotBlank(), new Type('string'))
            ->add('data', new NotBlank(), new Type('array'))
            ->addSub(
                'data',
                (new DataValidationDefinition())
                ->add('reference', new NotBlank(), new Type('string'))
            );

        $this->validator->validate($data, $definition);
    }

    private function buildWebhookReceiveData(array $data): WebhookReceiveDataStruct
    {
        $webhookData = new WebhookReceiveDataStruct();
        $webhookData->setId($data['id']);
        $webhookData->setType($data['type']);
        $webhookData->setCreatedOn($data['created_on']);
        $webhookData->setReference($data['data']['reference']);
        $webhookData->setActionId($data['data']['action_id'] ?? null);
        $webhookData->setAmount($data['data']['amount'] ?? null);
        $webhookData->setCurrency($data['data']['currency'] ?? null);

        return $webhookData;
    }
}
