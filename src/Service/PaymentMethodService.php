<?php declare(strict_types=1);

namespace Cko\Shopware6\Service;

use Cko\Shopware6\CkoShopware6;
use Cko\Shopware6\Exception\PaymentMethodNotFoundException;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Struct\PaymentHandler\PaymentHandlerCollection;
use Cko\Shopware6\Struct\PaymentMethod\InstalledPaymentMethodCollection;
use Cko\Shopware6\Struct\PaymentMethod\InstalledPaymentMethodStruct;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;

class PaymentMethodService
{
    public const PRIVILEGES_VIEWER = 'payment.viewer';
    public const PRIVILEGES_DELETER = 'payment.deleter';

    private PaymentHandlerCollection $installablePaymentHandlers;

    private EntityRepositoryInterface $paymentMethodRepository;

    private EntityRepositoryInterface $ruleRepository;

    private CountryService $countryService;

    private PluginIdProvider $pluginIdProvider;

    public function __construct(
        iterable $paymentHandlers,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $ruleRepository,
        CountryService $countryService,
        PluginIdProvider $pluginIdProvider
    ) {
        $this->installablePaymentHandlers = new PaymentHandlerCollection($paymentHandlers);
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->ruleRepository = $ruleRepository;
        $this->countryService = $countryService;
        $this->pluginIdProvider = $pluginIdProvider;
    }

    /**
     * @throws Exception
     */
    public function getPaymentHandlersByType(string $paymentMethodType): PaymentHandler
    {
        $paymentHandler = $this->installablePaymentHandlers->getByPaymentType($paymentMethodType);

        if (!$paymentHandler instanceof PaymentHandler) {
            throw new Exception(sprintf('Payment handler for type %s not found', $paymentMethodType));
        }

        return $paymentHandler;
    }

    public function getPaymentHandlersByHandlerIdentifier(string $handlerIdentifier): ?PaymentHandler
    {
        return $this->installablePaymentHandlers->getByHandlerIdentifier($handlerIdentifier);
    }

    public function getPaymentHandlerByOrderTransaction(OrderTransactionEntity $orderTransaction): ?PaymentHandler
    {
        $paymentMethod = $orderTransaction->getPaymentMethod();
        if (!$paymentMethod instanceof PaymentMethodEntity) {
            return null;
        }

        return $this->getPaymentHandlersByHandlerIdentifier($paymentMethod->getHandlerIdentifier());
    }

    /**
     * Install payment methods for the plugin
     */
    public function installPaymentMethods(Context $context): void
    {
        if ($this->installablePaymentHandlers->count() === 0) {
            return;
        }

        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(CkoShopware6::class, $context);

        $this->addPaymentMethods(
            $this->installablePaymentHandlers,
            $pluginId,
            $context
        );
    }

    /**
     * Toggle activate installed payment methods for the plugin
     */
    public function setActivateInstalledPaymentMethods(Context $context, bool $isActive = true): void
    {
        if ($this->installablePaymentHandlers->count() === 0) {
            return;
        }

        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(CkoShopware6::class, $context);

        // Get installed payment methods in the shop
        $installedPaymentMethodHandlers = $this->getInstalledPaymentMethodHandlers($pluginId, $context);

        // Toggle activate newly installed payment methods
        $this->setActivatePaymentMethods(
            $this->installablePaymentHandlers,
            $installedPaymentMethodHandlers,
            $context,
            $isActive
        );
    }

    /**
     * Get payment method by handler identifier.
     */
    public function getPaymentMethodByHandlerIdentifier(Context $context, string $handlerIdentifier, ?bool $active = null): PaymentMethodEntity
    {
        $paymentCriteria = new Criteria();
        $paymentCriteria->setLimit(1);
        $paymentCriteria->addFilter(new EqualsFilter('handlerIdentifier', $handlerIdentifier));

        if (!empty($active)) {
            $paymentCriteria->addFilter(new EqualsFilter('active', $active));
        }

        $paymentMethod = $this->paymentMethodRepository->search($paymentCriteria, $context)->first();

        if (!$paymentMethod instanceof PaymentMethodEntity) {
            throw new PaymentMethodNotFoundException($handlerIdentifier);
        }

        return $paymentMethod;
    }

    /**
     * Add payment methods for the plugin
     */
    private function addPaymentMethods(PaymentHandlerCollection $paymentHandlers, string $pluginId, Context $context): void
    {
        $paymentData = [];
        $rulesData = [];

        foreach ($paymentHandlers->getElements() as $paymentHandler) {
            $paymentMethodData = [
                'handlerIdentifier' => $paymentHandler->getClassName(),
                'name' => $paymentHandler->getPaymentMethodDisplayName()->toTranslationArray(),
                'description' => '',
                'pluginId' => $pluginId,
                'afterOrderEnabled' => true,
            ];

            try {
                $existingPaymentMethod = $this->getPaymentMethodByHandlerIdentifier(
                    $context,
                    $paymentMethodData['handlerIdentifier'],
                );

                // We update the payment method data if it already exists
                $paymentMethodData['id'] = $existingPaymentMethod->getId();
                $paymentMethodData['name'] = $existingPaymentMethod->getName();
                $paymentMethodData['description'] = $existingPaymentMethod->getDescription();
                $paymentMethodData['active'] = $existingPaymentMethod->getActive();
            } catch (PaymentMethodNotFoundException $exception) {
                $rule = $this->getRuleForPaymentHandler($paymentHandler, $context);
            }

            if (!empty($rule)) {
                $paymentMethodData['availabilityRuleId'] = $rule['id'];
                $rulesData[] = $rule;
            }

            $paymentData[] = $paymentMethodData;
        }

        if (!empty($rulesData)) {
            $this->ruleRepository->upsert($rulesData, $context);
        }

        if (!empty($paymentData)) {
            $this->paymentMethodRepository->upsert($paymentData, $context);
        }
    }

    private function getRuleForPaymentHandler(PaymentHandler $paymentHandler, Context $context): ?array
    {
        $paymentAvailableCountries = $paymentHandler->getAvailableCountries();
        if (empty($paymentAvailableCountries)) {
            return null;
        }

        $ruleId = Uuid::randomHex();

        return [
            'id' => $ruleId,
            'name' => sprintf(
                '(checkout.com) %s',
                $paymentHandler->getPaymentMethodDisplayName()->getName('en-GB')
            ),
            'priority' => 1,
            'conditions' => [
                [
                    'type' => 'orContainer',
                    'children' => [
                        [
                            'type' => 'andContainer',
                            'children' => [
                                [
                                    'type' => 'customerBillingCountry',
                                    'value' => [
                                        'operator' => '=',
                                        'countryIds' => $this->countryService->getCountryIdsByListIsoCode(
                                            $paymentAvailableCountries,
                                            $context
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get a collection of installed payment methods base on installable payment methods
     */
    private function getInstalledPaymentMethodHandlers(string $pluginId, Context $context): InstalledPaymentMethodCollection
    {
        $paymentCriteria = new Criteria();
        $paymentCriteria->addFilter(new EqualsFilter('pluginId', $pluginId));
        $paymentMethods = $this->paymentMethodRepository->search($paymentCriteria, $context);

        $installedHandlers = new InstalledPaymentMethodCollection();

        /** @var PaymentMethodEntity $paymentMethod */
        foreach ($paymentMethods->getEntities() as $paymentMethod) {
            // Skip if it is not in the installable handlers
            if (!$this->installablePaymentHandlers->hasHandlerIdentifier($paymentMethod->getHandlerIdentifier())) {
                continue;
            }

            // Skip if it is already in the installed handlers
            if ($installedHandlers->has($paymentMethod->getHandlerIdentifier())) {
                continue;
            }

            $installedPaymentMethod = new InstalledPaymentMethodStruct(
                $paymentMethod->getHandlerIdentifier(),
                $paymentMethod->getActive()
            );

            $installedHandlers->set($paymentMethod->getHandlerIdentifier(), $installedPaymentMethod);
        }

        return $installedHandlers;
    }

    /**
     * Toggle activate payment methods in Shopware.
     */
    private function setActivatePaymentMethods(PaymentHandlerCollection $paymentHandlers, InstalledPaymentMethodCollection $installedPaymentMethods, Context $context, bool $isActive = true): void
    {
        foreach ($paymentHandlers->getElements() as $paymentHandler) {
            // We skip if empty payment method handler or if it is not in the installed payment methods
            if (!$installedPaymentMethods->has($paymentHandler->getClassName())) {
                continue;
            }

            $installedPaymentMethod = $installedPaymentMethods->get($paymentHandler->getClassName());
            if ($installedPaymentMethod instanceof InstalledPaymentMethodStruct && $installedPaymentMethod->isActive() === $isActive) {
                // Skip if It is exists in the installed handlers and same activation status
                continue;
            }

            try {
                $existingPaymentMethod = $this->getPaymentMethodByHandlerIdentifier($context, $paymentHandler->getClassName());

                $this->setActivatePaymentMethod($existingPaymentMethod->getId(), $isActive, $context);
            } catch (PaymentMethodNotFoundException $exception) {
                // Do nothing to make sure this exception does not block any action behind
            }
        }
    }

    /**
     * Toggle activate a payment method in Shopware
     */
    private function setActivatePaymentMethod(string $paymentMethodId, bool $isActive, Context $context): void
    {
        $this->paymentMethodRepository->upsert(
            [
                [
                    'id' => $paymentMethodId,
                    'active' => $isActive,
                ],
            ],
            $context
        );
    }
}
