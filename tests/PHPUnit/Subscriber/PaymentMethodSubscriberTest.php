<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Subscriber;

use CheckoutCom\Shopware6\Handler\Method\CardPaymentHandler;
use CheckoutCom\Shopware6\Subscriber\PaymentMethodSubscriber;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentMethodSubscriberTest extends TestCase
{
    use ContextTrait;

    private SalesChannelContext $salesChannelContext;

    private PaymentMethodSubscriber $subscriber;

    public function setUp(): void
    {
        $this->subscriber = new PaymentMethodSubscriber();
        $this->salesChannelContext = $this->getSaleChannelContext($this);
    }

    public function getCheckoutPaymentMethod(): PaymentMethodEntity
    {
        $entity = new PaymentMethodEntity();
        $entity->setId(Uuid::randomHex());
        $entity->setHandlerIdentifier(CardPaymentHandler::class);

        return $entity;
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(PaymentEvents::PAYMENT_METHOD_LOADED_EVENT, PaymentMethodSubscriber::getSubscribedEvents());
        static::assertArrayHasKey(PaymentEvents::PAYMENT_METHOD_SEARCH_RESULT_LOADED_EVENT, PaymentMethodSubscriber::getSubscribedEvents());
    }

    public function testOnPaymentMethodLoadedCorrect(): void
    {
        $definition = new PaymentMethodDefinition();
        $paymentMethod = $this->getCheckoutPaymentMethod();

        $event = new EntityLoadedEvent(
            $definition,
            [$paymentMethod],
            $this->salesChannelContext->getContext()
        );

        $this->subscriber->onPaymentMethodLoaded($event);
        $customFields = $paymentMethod->getCustomFields();

        static::assertArrayHasKey(PaymentMethodSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS, $customFields);
        static::assertIsArray($customFields[PaymentMethodSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS]);
    }

    public function testOnPaymentMethodSearchResultLoadedCorrect(): void
    {
        $definition = new PaymentMethodDefinition();
        $paymentMethod = $this->getCheckoutPaymentMethod();

        $event = new EntitySearchResultLoadedEvent(
            $definition,
            new EntitySearchResult(
                $definition->getEntityName(),
                1,
                new PaymentMethodCollection([$paymentMethod]),
                null,
                new Criteria(),
                $this->salesChannelContext->getContext(),
            ),
        );

        $this->subscriber->onPaymentMethodSearchResultLoaded($event);
        $customFields = $paymentMethod->getCustomFields();

        static::assertArrayHasKey(PaymentMethodSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS, $paymentMethod->getCustomFields());
        static::assertIsArray($customFields[PaymentMethodSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS]);
    }
}
