<?php

namespace Packetery\Checkout\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    private $schema = [
        "id" => [
            "type" => Table::TYPE_INTEGER,
            "size" => null,
            "attr" => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
        ],
        "order_number" => [
            "type" => Table::TYPE_TEXT,
            "size" => 128,
            "attr" => ['nullable' => false],
        ],
        "recipient_firstname" => [
            "type" => Table::TYPE_TEXT,
            "size" => 128,
            "attr" => ['nullable' => false]
        ],
        "recipient_lastname" => [
            "type" => Table::TYPE_TEXT,
            "size" => 128,
            "attr" => ['nullable' => false]
        ],
        "recipient_company" => [
            "type" => Table::TYPE_TEXT,
            "size" => 128
        ],
        "recipient_email" => [
            "type" => Table::TYPE_TEXT,
            "size" => 128
        ],
        "recipient_phone" => [
            "type" => Table::TYPE_TEXT,
            "size" => 32
        ],
        "cod" => [
            "type" => Table::TYPE_DECIMAL,
            'length'    => '8,2'
        ],
        "currency" => [
            "type" => Table::TYPE_TEXT,
            'size'    => 8
        ],
        "value" => [
            "type" => Table::TYPE_DECIMAL,
            'length'    => '8,2',
            "attr" => ['nullable' => false]
        ],
        "weight" => [
            "type" => Table::TYPE_DECIMAL,
            'length'    => '4,2'
        ],
        "point_id" => [
            "type" => Table::TYPE_TEXT,
            'size'    => '32',
            "attr" => ['nullable' => false]
        ],
        "point_name" => [
            "type" => Table::TYPE_TEXT,
            'size'    => '1024'
        ],
        "is_carrier" => [
            "type" => Table::TYPE_BOOLEAN,
            'attr' => [
                'nullable' => false,
                'default' => 0,
                'comment' => 'Is Point_id ID of external carrier?',
                'after' => 'point_name'
            ]
        ],
        "carrier_pickup_point" => [
            "type" => Table::TYPE_TEXT,
            'size' => '40',
            'attr' => [
                'nullable' => true,
                'comment' => 'External carrier pickup point ID',
                'after' => 'is_carrier'
            ]
        ],
        "sender_label" => [
            "type" => Table::TYPE_TEXT,
            'size'    => '64'
        ],
        "adult_content" => [
            "type" => Table::TYPE_BOOLEAN
        ],
        "delayed_delivery" => [
            "type" => Table::TYPE_DATE
        ],
        "recipient_street" => [
            "type" => Table::TYPE_TEXT,
            'size'    => '128'
        ],
        "recipient_house_number" => [
            "type" => Table::TYPE_TEXT,
            'size'    => '32'
        ],
        "recipient_city" => [
            "type" => Table::TYPE_TEXT,
            'size'    => '128'
        ],
        "recipient_zip" => [
            "type" => Table::TYPE_TEXT,
            'size'    => '32'
        ],
        "carrier_point" => [
            "type" => Table::TYPE_TEXT,
            'size'    => '64'
        ],
        "width" => [
            "type" => Table::TYPE_INTEGER,
            'size'    => '11'
        ],
        "height" => [
            "type" => Table::TYPE_INTEGER,
            'size'    => '11'
        ],
        "depth" => [
            "type" => Table::TYPE_INTEGER,
            'size'    => '11'
        ],
        "exported" => [
            "type" => Table::TYPE_BOOLEAN,
            'size'    => '32'
        ],
        "exported_at" => [
            "type" => Table::TYPE_DATETIME
        ]

    ];

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $this->table($setup);

        $setup->endSetup();
    }

    private function table(SchemaSetupInterface &$setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('packetery_order')
        );

        $this->columns($table);

        $table->setComment('Zásilkovna objednávky');

        $setup->getConnection()->createTable($table);
    }

    private function columns(Table &$table)
    {
        foreach ($this->schema as $name => $column) {
            $column['attr'] = (isset($column['attr'])) ? $column['attr'] : [];
            $column['size'] = (isset($column['size'])) ? $column['size'] : null;
            $column['comment'] = (isset($column['comment'])) ? $column['comment'] : null;
            $table->addColumn(
                $name,
                $column['type'],
                $column['size'],
                $column['attr'],
                $column['comment']
            );
        }
    }
}
