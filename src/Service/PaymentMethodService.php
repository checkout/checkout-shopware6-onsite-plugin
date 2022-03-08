<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use CheckoutCom\Shopware6\CheckoutCom;
use CheckoutCom\Shopware6\Handler\Method\CreditCardHandler;
use CheckoutCom\Shopware6\Helper\Util;
use CheckoutCom\Shopware6\Struct\PaymentMethod\InstallablePaymentMethodCollection;
use CheckoutCom\Shopware6\Struct\PaymentMethod\InstallablePaymentMethodStruct;
use CheckoutCom\Shopware6\Struct\PaymentMethod\InstalledPaymentMethodCollection;
use CheckoutCom\Shopware6\Struct\PaymentMethod\InstalledPaymentMethodStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

class PaymentMethodService
{
    private EntityRepositoryInterface $paymentMethodRepository;

    private PluginIdProvider $pluginIdProvider;

    public function __construct(EntityRepositoryInterface $paymentMethodRepository, PluginIdProvider $pluginIdProvider)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->pluginIdProvider = $pluginIdProvider;
    }

    /**
     * Install payment methods for the plugin
     */
    public function installPaymentMethods(Context $context): void
    {
        // Get installable payment methods
        $installablePaymentMethods = $this->getInstallablePaymentMethods();
        if ($installablePaymentMethods->count() === 0) {
            return;
        }

        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(CheckoutCom::class, $context);

        $this->addPaymentMethods($installablePaymentMethods, $pluginId, $context);
    }

    /**
     * Toggle activate installed payment methods for the plugin
     */
    public function setActivateInstalledPaymentMethods(Context $context, bool $isActive = true): void
    {
        // Get installable payment methods
        $installablePaymentMethods = $this->getInstallablePaymentMethods();
        if ($installablePaymentMethods->count() === 0) {
            return;
        }

        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(CheckoutCom::class, $context);

        // Get installed payment methods in the shop
        $installedPaymentMethodHandlers = $this->getInstalledPaymentMethodHandlers($this->getPaymentHandlers(), $pluginId, $context);

        // Toggle activate newly installed payment methods
        $this->setActivatePaymentMethods(
            $installablePaymentMethods,
            $installedPaymentMethodHandlers,
            $context,
            $isActive
        );
    }

    /**
     * Get an array of installable payment methods
     */
    private function getInstallablePaymentMethods(): InstallablePaymentMethodCollection
    {
        $paymentMethods = new InstallablePaymentMethodCollection();
        $installablePaymentMethods = $this->getPaymentHandlers();

        foreach ($installablePaymentMethods as $installablePaymentMethod) {
            $paymentMethods->add(
                new InstallablePaymentMethodStruct(
                    Util::handleCallUserFunc($installablePaymentMethod . '::getPaymentMethodDisplayName'),
                    $installablePaymentMethod
                )
            );
        }

        return $paymentMethods;
    }

    /**
     * Returns an array of payment handlers.
     */
    private function getPaymentHandlers(): array
    {
        return [
            CreditCardHandler::class,
        ];
    }

    /**
     * Add payment methods for the plugin
     */
    private function addPaymentMethods(InstallablePaymentMethodCollection $paymentMethods, string $pluginId, Context $context): void
    {
        $paymentData = [];

        /** @var InstallablePaymentMethodStruct $paymentMethod */
        foreach ($paymentMethods->getElements() as $paymentMethod) {
            $paymentMethodData = [
                'handlerIdentifier' => $paymentMethod->getHandler(),
                'name' => $paymentMethod->getDisplayName()->toTranslationArray(),
                'description' => '',
                'pluginId' => $pluginId,
                'afterOrderEnabled' => true,
            ];

            $existingPaymentMethod = $this->getPaymentMethodByHandlerIdentifier(
                $paymentMethodData['handlerIdentifier'],
                $context
            );

            // We update the payment method data if it already exists
            if (isset($existingPaymentMethod) && $existingPaymentMethod instanceof PaymentMethodEntity) {
                $paymentMethodData['id'] = $existingPaymentMethod->getId();
                $paymentMethodData['name'] = $existingPaymentMethod->getName();
                $paymentMethodData['description'] = $existingPaymentMethod->getDescription();
                $paymentMethodData['active'] = $existingPaymentMethod->getActive();
            }

            $paymentData[] = $paymentMethodData;
        }

        // Insert or update payment data
        if (empty($paymentData)) {
            return;
        }

        $this->paymentMethodRepository->upsert($paymentData, $context);
    }

    /**
     * Get payment method by handler identifier.
     */
    private function getPaymentMethodByHandlerIdentifier(string $handlerIdentifier, Context $context): ?PaymentMethodEntity
    {
        $paymentCriteria = new Criteria();
        $paymentCriteria->addFilter(new EqualsFilter('handlerIdentifier', $handlerIdentifier));

        return $this->paymentMethodRepository->search($paymentCriteria, $context)->first();
    }

    /**
     * Get a collection of installed payment methods base on installable payment methods
     */
    private function getInstalledPaymentMethodHandlers(array $installableHandlers, string $pluginId, Context $context): InstalledPaymentMethodCollection
    {
        $paymentCriteria = new Criteria();
        $paymentCriteria->addFilter(new EqualsFilter('pluginId', $pluginId));
        $paymentMethods = $this->paymentMethodRepository->search($paymentCriteria, $context);

        $installedHandlers = new InstalledPaymentMethodCollection();

        /** @var PaymentMethodEntity $paymentMethod */
        foreach ($paymentMethods->getEntities() as $paymentMethod) {
            if (!\in_array($paymentMethod->getHandlerIdentifier(), $installableHandlers, true)) {
                // Skip if it is not in the installable handlers
                continue;
            }

            if ($installedHandlers->has($paymentMethod->getHandlerIdentifier())) {
                // Skip if it is already in the installed handlers
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
    private function setActivatePaymentMethods(InstallablePaymentMethodCollection $paymentMethods, InstalledPaymentMethodCollection $installedPaymentMethods, Context $context, bool $isActive = true): void
    {
        if ($paymentMethods->count() === 0) {
            return;
        }

        /** @var InstallablePaymentMethodStruct $paymentMethod */
        foreach ($paymentMethods->getElements() as $paymentMethod) {
            $paymentMethodHandler = $paymentMethod->getHandler();
            if (empty($paymentMethodHandler)) {
                continue;
            }

            $installedPaymentMethod = $installedPaymentMethods->get($paymentMethodHandler);
            if ($installedPaymentMethod instanceof InstalledPaymentMethodStruct && $installedPaymentMethod->isActive() === $isActive) {
                // Skip if It is exists in the installed handlers and same activation status
                continue;
            }

            $existingPaymentMethod = $this->getPaymentMethodByHandlerIdentifier($paymentMethodHandler, $context);
            if (!$existingPaymentMethod instanceof PaymentMethodEntity) {
                continue;
            }

            $this->setActivatePaymentMethod($existingPaymentMethod->getId(), $isActive, $context);
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
