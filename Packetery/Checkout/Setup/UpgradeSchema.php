<?php


namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /** @var \Packetery\Checkout\Setup\InstallSchema */
    private $installSchema;

    /**
     * UpgradeSchema constructor.
     *
     * @param \Packetery\Checkout\Setup\InstallSchema $installSchema
     */
    public function __construct(InstallSchema $installSchema)
    {
        $this->installSchema = $installSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "2.0.3", "<")) {
            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_order'),
                'is_carrier',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Is Point_id ID of external carrier?',
                    'after' => 'point_name'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_order'),
                'carrier_pickup_point',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 40,
                    'comment' => 'External carrier pickup point ID',
                    'after' => 'is_carrier'
                ]
            );

            $this->installSchema->pricingRulesTable($setup);
            $this->installSchema->weightRulesTable($setup);

            $setup->getConnection()->modifyColumn(
                $setup->getTable('packetery_order'),
                'cod',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '20,4'
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('packetery_order'),
                'value',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '20,4'
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('packetery_order'),
                'weight',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,4'
                ]
            );
        }

        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $this->installSchema->carrierTable($setup);

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_pricing_rule'),
                'carrier_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'default' => 'packetery',
                    'nullable' => false,
                    'length' => '64',
                    'comment' => 'Magento unique carrier class identifier',
                    'after' => 'method'
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('packetery_pricing_rule'),
                'carrier_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'length' => '64',
                    'comment' => 'Magento unique carrier class identifier',
                    'after' => 'method'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_pricing_rule'),
                'carrier_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'length' => '11',
                    'comment' => 'Dynamic carrier id from Mordor',
                    'after' => 'carrier_code'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_pricing_rule'),
                'enabled',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'default' => 0,
                    'nullable' => false,
                    'comment' => 'Is rule enabled?',
                    'after' => 'carrier_id',
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.3.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_pricing_rule'),
                'address_validation',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'default' => 'none',
                    'nullable' => false,
                    'length' => '10',
                    'comment' => 'Address validation',
                    'after' => 'carrier_id'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_pricing_rule'),
                'max_cod',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'nullable' => true,
                    'after' => 'address_validation',
                    'length' => '20,4',
                    'comment' => 'minimal value to hide COD payment methods'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_order'),
                'address_validated',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Is recipient address validated?',
                    'after' => 'delayed_delivery'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_order'),
                'recipient_country_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => '2',
                    'comment' => 'Recipient country',
                    'after' => 'recipient_zip'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_order'),
                'recipient_county',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => '128',
                    'comment' => 'Recipient county',
                    'after' => 'recipient_country_id'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_order'),
                'recipient_longitude',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'nullable' => true,
                    'length' => '11,8',
                    'comment' => 'Longitude',
                    'after' => 'recipient_county'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('packetery_order'),
                'recipient_latitude',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'nullable' => true,
                    'length' => '10,8',
                    'comment' => 'Latitude',
                    'after' => 'recipient_longitude'
                ]
            );
        }

        $setup->endSetup();
    }
}
