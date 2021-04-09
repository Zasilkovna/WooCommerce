<?php


namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        $installer = $setup;
        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('packetery_order'),
            'is_carrier',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Is Point_id ID of external carrier?',
                'after' => 'point_name'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('packetery_order'),
            'carrier_pickup_point',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => 40,
                'comment' => 'External carrier pickup point ID',
                'after' => 'is_carrier'
            ]
        );

        $installSchema = new InstallSchema();
        $installSchema->pricingRulesTable($setup);
        $installSchema->weightRulesTable($setup);

        $installer->endSetup();
    }
}
