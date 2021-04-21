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

        $setup->endSetup();
    }
}
