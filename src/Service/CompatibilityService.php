<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class CompatibilityService
{
    private ContainerBuilder $container;

    private ?string $swVersion;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->swVersion = $this->container->getParameter('kernel.shopware_version');
    }

    /**
     * @throws Exception
     */
    public function loadServices(): void
    {
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__ . '/../Resources/config'));

        if ($this->gte('6.4.6.0')) {
            $loader->load('compatibility/6.4.6.0/flows.xml');
        }
    }

    public function gte(string $versionB): bool
    {
        return version_compare($this->swVersion, $versionB, '>=');
    }

    public function lt(string $version): bool
    {
        return version_compare($this->swVersion, $version, '<');
    }
}
