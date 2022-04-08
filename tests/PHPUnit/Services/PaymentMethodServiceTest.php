<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Facade\PaymentFinalizeFacade;
use CheckoutCom\Shopware6\Facade\PaymentPayFacade;
use CheckoutCom\Shopware6\Handler\Method\CreditCardHandler;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Struct\PaymentHandler\PaymentHandlerCollection;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;

class PaymentMethodServiceTest extends TestCase
{
    use ContextTrait;

    private Context $context;

    public function setUp(): void
    {
        $this->context = $this->getContext($this);
    }

    /**
     * @dataProvider installPaymentMethodsProvider
     */
    public function testInstallPaymentMethods(bool $expectedUpsertSuccess, array $paymentMethods, array $existsPaymentMethods): void
    {
        $installablePaymentHandlers = new PaymentHandlerCollection($paymentMethods);

        $paymentMethodRepository = $this->createMock(EntityRepository::class);
        $pluginIdProvider = $this->createMock(PluginIdProvider::class);
        $paymentMethodService = new PaymentMethodService(
            $installablePaymentHandlers,
            $paymentMethodRepository,
            $pluginIdProvider
        );

        $pluginIdProvider
            ->expects($installablePaymentHandlers->count() === 0 ? static::never() : static::atLeastOnce())
            ->method('getPluginIdByBaseClass');

        $paymentMethodRepository
            ->expects($installablePaymentHandlers->count() === 0 ? static::never() : static::atLeastOnce())
            ->method('search')
            ->willReturn($this->getPaymentMethodResults($existsPaymentMethods));

        $paymentMethodRepository
            ->expects(static::exactly($expectedUpsertSuccess ? 1 : 0))
            ->method('upsert');

        $paymentMethodService->installPaymentMethods($this->context);
    }

    /**
     * @dataProvider setActivateInstalledPaymentMethodsProvider
     */
    public function testSetActivateInstalledPaymentMethods(bool $expectedUpsertSuccess, array $paymentMethods, array $existsPaymentMethods, bool $currentActive, bool $targetActive): void
    {
        $installablePaymentHandlers = new PaymentHandlerCollection($paymentMethods);

        $paymentMethodRepository = $this->createMock(EntityRepository::class);
        $pluginIdProvider = $this->createMock(PluginIdProvider::class);
        $paymentMethodService = new PaymentMethodService(
            $installablePaymentHandlers,
            $paymentMethodRepository,
            $pluginIdProvider
        );

        $pluginIdProvider
            ->expects($installablePaymentHandlers->count() === 0 ? static::never() : static::atLeastOnce())
            ->method('getPluginIdByBaseClass');

        $paymentMethodRepository
            ->expects($installablePaymentHandlers->count() === 0 ? static::never() : static::atLeastOnce())
            ->method('search')
            ->willReturn($this->getPaymentMethodResults($existsPaymentMethods, $currentActive));

        $paymentMethodRepository
            ->expects(static::exactly($expectedUpsertSuccess ? 1 : 0))
            ->method('upsert');

        $paymentMethodService->setActivateInstalledPaymentMethods($this->context, $targetActive);
    }

    public function installPaymentMethodsProvider(): array
    {
        return [
            'Test empty installable payment methods' => [
                false,
                'paymentMethods' => [],
                'existsPaymentMethods' => [],
            ],
            'Test successful install payment methods without any exists payment methods' => [
                true,
                'paymentMethods' => [
                    $this->createMock(CreditCardHandler::class),
                ],
                'existsPaymentMethods' => [
                ],
            ],
            'Test successful install payment methods with exists payment methods' => [
                true,
                'paymentMethods' => [
                    $this->createMock(CreditCardHandler::class),
                ],
                'existsPaymentMethods' => [
                    CreditCardHandler::class,
                ],
            ],
        ];
    }

    public function setActivateInstalledPaymentMethodsProvider(): array
    {
        return [
            'Test empty installable payment methods' => [
                false,
                'paymentMethods' => [],
                'existsPaymentMethods' => [
                ],
                true,
                true,
            ],
            'Test empty installed payment methods' => [
                false,
                'paymentMethods' => [
                    $this->createConfiguredMock(CreditCardHandler::class, [
                        'getClassName' => CreditCardHandler::class,
                    ]),
                ],
                'existsPaymentMethods' => [
                ],
                true,
                true,
            ],
            'Test set activate for not in checkout.com installed payment methods' => [
                false,
                'paymentMethods' => [
                    $this->createConfiguredMock(CreditCardHandler::class, [
                        'getClassName' => CreditCardHandler::class,
                    ]),
                ],
                'existsPaymentMethods' => [
                    'anything out side checkout.com payment method',
                ],
                true,
                true,
            ],
            'Test set activate for installed payment methods with duplicate payment methods' => [
                false,
                'paymentMethods' => [
                    $this->createConfiguredMock(CreditCardHandler::class, [
                        'getClassName' => CreditCardHandler::class,
                    ]),
                ],
                'existsPaymentMethods' => [
                    CreditCardHandler::class,
                    CreditCardHandler::class,
                ],
                true,
                true,
            ],
            'Test set activate for installed payment methods with same activate status' => [
                false,
                'paymentMethods' => [
                    $this->createConfiguredMock(CreditCardHandler::class, [
                        'getClassName' => CreditCardHandler::class,
                    ]),
                ],
                'existsPaymentMethods' => [
                    CreditCardHandler::class,
                ],
                true,
                true,
            ],
            'Test set activate for installed payment methods with wrong class name' => [
                false,
                'paymentMethods' => [
                    $this->createConfiguredMock(CreditCardHandler::class, [
                        'getClassName' => 'Wrong class name',
                    ]),
                ],
                'existsPaymentMethods' => [
                    CreditCardHandler::class,
                ],
                true,
                false,
            ],
            'Test set activate for installed payment methods updated activate status successful' => [
                true,
                'paymentMethods' => [
                    new CreditCardHandler(
                        $this->createMock(LoggerService::class),
                        $this->createMock(PaymentPayFacade::class),
                        $this->createMock(PaymentFinalizeFacade::class),
                    ),
                ],
                'existsPaymentMethods' => [
                    CreditCardHandler::class,
                ],
                true,
                false,
            ],
        ];
    }

    private function getPaymentMethodResults(array $paymentHandlers, bool $isActivate = false): EntitySearchResult
    {
        $paymentMethodCollection = new PaymentMethodCollection();
        foreach ($paymentHandlers as $paymentHandler) {
            $paymentMethod = new PaymentMethodEntity();
            $paymentMethod->setId(Uuid::randomHex());
            $paymentMethod->setHandlerIdentifier($paymentHandler);
            $paymentMethod->setActive($isActivate);

            $paymentMethodCollection->add($paymentMethod);
        }

        return new EntitySearchResult(
            (new PaymentMethodDefinition())->getEntityName(),
            $paymentMethodCollection->count(),
            $paymentMethodCollection,
            null,
            new Criteria(),
            $this->context
        );
    }
}
