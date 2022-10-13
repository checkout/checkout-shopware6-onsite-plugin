<?php
declare(strict_types=1);

namespace Cko\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use Checkout\Workflows\Actions\WebhookWorkflowActionRequest;
use Checkout\Workflows\Conditions\EventWorkflowConditionRequest;
use Checkout\Workflows\CreateWorkflowRequest;
use Cko\Shopware6\Factory\CheckoutApiFactory;
use Cko\Shopware6\Struct\CheckoutApi\Webhook;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This service provides functions to manage webhooks from Checkout
 */
class CheckoutWorkflowService extends AbstractCheckoutService
{
    public const WORKFLOW_NAME = 'Checkout.com workflow';

    private RouterInterface $router;

    public function __construct(
        LoggerInterface $logger,
        CheckoutApiFactory $checkoutApiFactory,
        RouterInterface $router
    ) {
        parent::__construct($logger, $checkoutApiFactory);

        $this->router = $router;
    }

    /**
     * Add a new workflow to Checkout.com
     *
     * @throws CheckoutApiException
     */
    public function createWorkFlows(?string $salesChannelId = null): Webhook
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $authorization = Uuid::randomHex();
            $webhook = $checkoutApi->getWorkflowsClient()->createWorkflow($this->buildWorkflowRequest($authorization, $salesChannelId));

            return $this->buildWebhook($webhook['id'], $authorization);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Get a workflow by id
     *
     * @throws CheckoutApiException
     */
    public function getWorkflow(string $workflowId, ?string $salesChannelId = null): void
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $checkoutApi->getWorkflowsClient()->getWorkflow($workflowId);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    private function buildWorkflowRequest(
        string $authorization,
        ?string $salesChannelId = null,
        string $routeName = CheckoutWebhookService::WEBHOOK_ROUTE_NAME,
        array $eventTypes = CheckoutWebhookService::WEBHOOK_EVENTS
    ): CreateWorkflowRequest {
        $workflowCondition = new EventWorkflowConditionRequest();
        $workflowCondition->events = [
            'gateway' => $eventTypes,
        ];

        $workflowAction = new WebhookWorkflowActionRequest();
        $workflowAction->url = $this->router->generate(
            $routeName,
            ['salesChannelId' => $salesChannelId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $workflowAction->headers = [
            'Authorization' => $authorization,
        ];

        $request = new CreateWorkflowRequest();
        $request->name = self::WORKFLOW_NAME;
        $request->active = true;
        $request->conditions = [$workflowCondition];
        $request->actions = [$workflowAction];

        return $request;
    }

    private function buildWebhook(string $id, string $authorization): Webhook
    {
        $webhook = new Webhook();
        $webhook->setId($id);
        $webhook->setAuthorization($authorization);

        return $webhook;
    }
}
