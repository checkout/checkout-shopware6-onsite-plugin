<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Handler\Method\CardPaymentHandler;
use CheckoutCom\Shopware6\Handler\Method\GooglePayHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\CountryService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Struct\PaymentHandler\PaymentHandlerCollection;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\Currency\CurrencyFormatter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentMethodServiceTest extends TestCase
{
    use ContextTrait;

    private Context $context;

    public function setUp(): void
    {
        $this->context = $this->getContext($this);
    }

    /**
     * @dataProvider getPaymentHandlersByTypeProvider
     */
    public function testGetPaymentHandlersByType(array $paymentMethods, string $paymentMethodType, bool $expectedFound): void
    {
        $installablePaymentHandlers = new PaymentHandlerCollection($paymentMethods);

        $paymentMethodRepository = $this->createMock(EntityRepository::class);
        $ruleRepository = $this->createMock(EntityRepository::class);
        $countryService = $this->createMock(CountryService::class);
        $pluginIdProvider = $this->createMock(PluginIdProvider::class);
        $paymentMethodService = new PaymentMethodService(
            $installablePaymentHandlers,
            $paymentMethodRepository,
            $ruleRepository,
            $countryService,
            $pluginIdProvider
        );

        if (!$expectedFound) {
            static::expectException(Exception::class);
        }

        $paymentHandler = $paymentMethodService->getPaymentHandlersByType($paymentMethodType);

        static::assertInstanceOf(PaymentHandler::class, $paymentHandler);
    }

    public function testGetPaymentHandlersByHandlerIdentifier(): void
    {
        $paymentHandler = $this->createConfiguredMock(CardPaymentHandler::class, [
            'getClassName' => CardPaymentHandler::class,
        ]);

        $paymentMethodRepository = $this->createMock(EntityRepository::class);
        $ruleRepository = $this->createMock(EntityRepository::class);
        $countryService = $this->createMock(CountryService::class);
        $pluginIdProvider = $this->createMock(PluginIdProvider::class);

        $paymentMethodService = new PaymentMethodService(
            [$paymentHandler],
            $paymentMethodRepository,
            $ruleRepository,
            $countryService,
            $pluginIdProvider
        );

        $expect = $paymentMethodService->getPaymentHandlersByHandlerIdentifier(CardPaymentHandler::class);
        static::assertInstanceOf(PaymentHandler::class, $expect);
    }

    /**
     * @dataProvider getPaymentHandlerByOrderTransactionProvider
     */
    public function testGetPaymentHandlerByOrderTransaction(bool $hasPaymentMethod): void
    {
        $paymentMethod = $this->createConfiguredMock(PaymentMethodEntity::class, [
            'getHandlerIdentifier' => CardPaymentHandler::class,
        ]);

        $orderTransaction = $this->createConfiguredMock(OrderTransactionEntity::class, [
            'getPaymentMethod' => $hasPaymentMethod ? $paymentMethod : null,
        ]);

        $paymentHandler = $this->createConfiguredMock(CardPaymentHandler::class, [
            'getClassName' => CardPaymentHandler::class,
        ]);

        $paymentMethodRepository = $this->createMock(EntityRepository::class);
        $ruleRepository = $this->createMock(EntityRepository::class);
        $countryService = $this->createMock(CountryService::class);
        $pluginIdProvider = $this->createMock(PluginIdProvider::class);

        $paymentMethodService = new PaymentMethodService(
            [$paymentHandler],
            $paymentMethodRepository,
            $ruleRepository,
            $countryService,
            $pluginIdProvider
        );

        $expect = $paymentMethodService->getPaymentHandlerByOrderTransaction($orderTransaction);

        if ($hasPaymentMethod) {
            static::assertInstanceOf(PaymentHandler::class, $expect);
        } else {
            static::assertNull($expect);
        }
    }

    /**
     * @dataProvider installPaymentMethodsProvider
     */
    public function testInstallPaymentMethods(bool $expectedUpsertSuccess, array $paymentMethods, array $existsPaymentMethods): void
    {
        $installablePaymentHandlers = new PaymentHandlerCollection($paymentMethods);

        $paymentMethodRepository = $this->createMock(EntityRepository::class);
        $ruleRepository = $this->createMock(EntityRepository::class);
        $countryService = $this->createMock(CountryService::class);
        $pluginIdProvider = $this->createMock(PluginIdProvider::class);
        $paymentMethodService = new PaymentMethodService(
            $installablePaymentHandlers,
            $paymentMethodRepository,
            $ruleRepository,
            $countryService,
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
        $ruleRepository = $this->createMock(EntityRepository::class);
        $countryService = $this->createMock(CountryService::class);
        $pluginIdProvider = $this->createMock(PluginIdProvider::class);
        $paymentMethodService = new PaymentMethodService(
            $installablePaymentHandlers,
            $paymentMethodRepository,
            $ruleRepository,
            $countryService,
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

    public function getPaymentHandlersByTypeProvider(): array
    {
        return [
            'Test did not find payment handler' => [
                'paymentMethods' => [
                    $this->createConfiguredMock(CardPaymentHandler::class, [
                        'getClassName' => CardPaymentHandler::class,
                    ]),
                ],
                'paymentMethodType' => GooglePayHandler::getPaymentMethodType(),
                false,
            ],
            'Test found payment handler' => [
                'paymentMethods' => [
                    $this->createConfiguredMock(CardPaymentHandler::class, [
                        'getClassName' => CardPaymentHandler::class,
                    ]),
                ],
                'paymentMethodType' => CardPaymentHandler::getPaymentMethodType(),
                true,
            ],
        ];
    }

    public function getPaymentHandlerByOrderTransactionProvider(): array
    {
        return [
            'Test not found payment method entity' => [
                false,
            ],
            'Test found payment handler' => [
                true,
            ],
        ];
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
                    $this->createMock(CardPaymentHandler::class),
                ],
                'existsPaymentMethods' => [
                ],
            ],
            'Test successful install payment methods with exists payment methods' => [
                true,
                'paymentMethods' => [
                    $this->createMock(CardPaymentHandler::class),
                ],
                'existsPaymentMethods' => [
                    CardPaymentHandler::class,
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
                    $this->createConfiguredMock(CardPaymentHandler::class, [
                        'getClassName' => CardPaymentHandler::class,
                        'getPaymentMethodDisplayName' => new DisplayNameTranslationCollection(),
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
                    $this->createConfiguredMock(CardPaymentHandler::class, [
                        'getClassName' => CardPaymentHandler::class,
                        'getPaymentMethodDisplayName' => new DisplayNameTranslationCollection(),
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
                    $this->createConfiguredMock(CardPaymentHandler::class, [
                        'getClassName' => CardPaymentHandler::class,
                        'getPaymentMethodDisplayName' => new DisplayNameTranslationCollection(),
                    ]),
                ],
                'existsPaymentMethods' => [
                    CardPaymentHandler::class,
                    CardPaymentHandler::class,
                ],
                true,
                true,
            ],
            'Test set activate for installed payment methods with same activate status' => [
                false,
                'paymentMethods' => [
                    $this->createConfiguredMock(CardPaymentHandler::class, [
                        'getClassName' => CardPaymentHandler::class,
                        'getPaymentMethodDisplayName' => new DisplayNameTranslationCollection(),
                    ]),
                ],
                'existsPaymentMethods' => [
                    CardPaymentHandler::class,
                ],
                true,
                true,
            ],
            'Test set activate for installed payment methods with wrong class name' => [
                false,
                'paymentMethods' => [
                    $this->createConfiguredMock(CardPaymentHandler::class, [
                        'getClassName' => 'Wrong class name',
                        'getPaymentMethodDisplayName' => new DisplayNameTranslationCollection(),
                    ]),
                ],
                'existsPaymentMethods' => [
                    CardPaymentHandler::class,
                ],
                true,
                false,
            ],
            'Test set activate for installed payment methods updated activate status successful' => [
                true,
                'paymentMethods' => [
                    new CardPaymentHandler(
                        $this->createConfiguredMock(TranslatorInterface::class, [
                            'trans' => 'Foo',
                        ]),
                        $this->createMock(DataValidator::class),
                        $this->createMock(CurrencyFormatter::class),
                        $this->createMock(SystemConfigService::class),
                    ),
                ],
                'existsPaymentMethods' => [
                    CardPaymentHandler::class,
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
