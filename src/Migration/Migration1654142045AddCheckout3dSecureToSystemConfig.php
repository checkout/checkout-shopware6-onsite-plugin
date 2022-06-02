<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Migration;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1654142045AddCheckout3dSecureToSystemConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1654142045;
    }

    public function update(Connection $connection): void
    {
        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_3DS,
            'configuration_value' => \json_encode(['_value' => true]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
