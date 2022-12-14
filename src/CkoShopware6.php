<?php declare(strict_types=1);

namespace Cko\Shopware6;

use Cko\Shopware6\Service\CompatibilityService;
use Cko\Shopware6\Service\PaymentMethodService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CkoShopware6 extends Plugin
{
    /**
     * @throws \Exception
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $compatibilityService = new CompatibilityService($container);
        $compatibilityService->loadServices();
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        if ($updateContext->getPlugin()->isActive()) {
            // We need to install the payment methods again if the plugin is updated
            $this->installPaymentMethods($updateContext->getContext());
        }
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        // We have to install again to make sure everything is up-to-date
        $this->installPaymentMethods($activateContext->getContext());

        // We will activate the payment methods when the plugin is activated
        $this->setActivateInstalledPaymentMethods($activateContext->getContext(), true);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);

        // We will deactivate the payment methods when the plugin is deactivated
        $this->setActivateInstalledPaymentMethods($deactivateContext->getContext(), false);
    }

    private function installPaymentMethods(Context $context): void
    {
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = $this->container->get(PaymentMethodService::class);

        $paymentMethodService->installPaymentMethods($context);
    }

    private function setActivateInstalledPaymentMethods(Context $context, bool $isActive): void
    {
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = $this->container->get(PaymentMethodService::class);

        $paymentMethodService->setActivateInstalledPaymentMethods($context, $isActive);
    }
}
