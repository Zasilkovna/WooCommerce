<?php

namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Config\Model\Config\Factory $configFactory
     */
    public function __construct(\Magento\Config\Model\Config\Factory $configFactory) {
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $configModel = $this->configFactory->create();
            $configModel->setDataByPath('carriers/packetery/sallowspecific', 0); // config option UI was removed
            $configModel->save();
        }

        if (version_compare($context->getVersion(), '2.3.0', '<')) {
            $packeteryOrderTable = $setup->getTable('packetery_order');
            $salesOrderTable = $setup->getTable('sales_order');
            $salesOrderAddressTable = $setup->getTable('sales_order_address');
            $setup->getConnection()->query("
                UPDATE `$packeteryOrderTable`
                JOIN `$salesOrderTable` ON `$salesOrderTable`.`increment_id` = $packeteryOrderTable.`order_number`
                JOIN `$salesOrderAddressTable` ON `$salesOrderTable`.`shipping_address_id` IS NOT NULL AND `$salesOrderAddressTable`.`entity_id` = `$salesOrderTable`.`shipping_address_id`
                SET `$packeteryOrderTable`.`recipient_country_id` = `$salesOrderAddressTable`.`country_id`
                WHERE `$packeteryOrderTable`.`recipient_country_id` IS NULL
            ");
        }
    }
}
