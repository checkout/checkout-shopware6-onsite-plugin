<?php
declare(strict_types=1);

namespace Cko\Shopware6\Service\Webhook;

use Cko\Shopware6\Struct\WebhookReceiveDataStruct;
use Shopware\Core\Framework\Context;

abstract class AbstractWebhookService
{
    abstract public function getDecorated(): AbstractWebhookService;

    abstract public function authenticateToken(string $token, ?string $salesChannelId = null): bool;

    abstract public function handle(WebhookReceiveDataStruct $data, Context $context, ?string $salesChannelId = null): void;
}
