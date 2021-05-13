<?php

namespace Packetery\Checkout\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /** @var array[]  */
    private $orderTableSchema = [
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
            "attr" => [
                'length' => '20,4'
            ]
        ],
        "currency" => [
            "type" => Table::TYPE_TEXT,
            'size'    => 8
        ],
        "value" => [
            "type" => Table::TYPE_DECIMAL,
            "attr" => [
                'nullable' => false,
                'length' => '20,4',
            ]
        ],
        "weight" => [
            "type" => Table::TYPE_DECIMAL,
            "attr" => [
                'nullable' => false,
                'length' => '12,4',
            ]
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
    ): void {
        $setup->startSetup();

        $this->ordersTable($setup);
        $this->pricingRulesTable($setup);
        $this->weightRulesTable($setup);

        $setup->endSetup();
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function ordersTable(SchemaSetupInterface &$setup): void
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('packetery_order')
        );

        $this->columns($table, $this->orderTableSchema);

        $table->setComment('Zásilkovna objednávky');

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    public function pricingRulesTable(SchemaSetupInterface &$setup): void
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('packetery_pricing_rule')
        );

        $this->columns($table, [
            "id" => [
                "type" => Table::TYPE_INTEGER,
                "attr" => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            ],
            "free_shipment" => [
                "type" => Table::TYPE_DECIMAL,
                'attr' => [
                    'nullable' => true,
                    'after' => 'price',
                    'length' => '20,4',
                    'comment' => 'From what order value will be shipping for free?'
                ],
            ],
            "country_id" => [
                "type" => Table::TYPE_TEXT,
                'attr' => [
                    'nullable' => false,
                    'length' => '2',
                    'comment' => 'Country that relates to specified price',
                    'after' => 'free_shipment'
                ]
            ],
            "method" => [
                "type" => Table::TYPE_TEXT,
                'attr' => [
                    'nullable' => false,
                    'length' => '64',
                    'comment' => 'Related delivery method',
                    'after' => 'country_id'
                ]
            ]
        ]);

        $table->setComment('Packetery pricing rules. Relates to packetery_weight_rules.');

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    public function weightRulesTable(SchemaSetupInterface &$setup): void
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('packetery_weight_rule')
        );

        $this->columns($table, [
            "id" => [
                "type" => Table::TYPE_INTEGER,
                "attr" => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            ],
            "packetery_pricing_rule_id" => [
                "type" => Table::TYPE_INTEGER,
                "attr" => ['unsigned' => true, 'nullable' => false],
            ],
            "price" => [
                "type" => Table::TYPE_DECIMAL,
                'attr' => [
                    'nullable' => false,
                    'after' => 'id',
                    'length' => '20,4',
                    'comment' => 'Price for given constrains'
                ],
            ],
            "max_weight" => [
                "type" => Table::TYPE_DECIMAL,
                'attr' => [
                    'nullable' => true,
                    'after' => 'price',
                    'length' => '12,4',
                    'comment' => 'Maximum weight in kilograms'
                ],
            ]
        ]);

        $table->addForeignKey(
            $setup->getFkName($setup->getTable('packetery_weight_rule'),'packetery_pricing_rule_id', $setup->getTable('packetery_pricing_rule'), 'id'),
            'packetery_pricing_rule_id', $setup->getTable('packetery_pricing_rule'), 'id'
        );

        $table->setComment('Packetery weight rules. Relates to packetery pricing rules.');

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param \Magento\Framework\DB\Ddl\Table $table
     * @param $schema
     * @throws \Zend_Db_Exception
     */
    private function columns(Table &$table, $schema): void
    {
        foreach ($schema as $name => $column) {
            $column['attr'] = (isset($column['attr']) ? $column['attr'] : []);
            $column['attr']['comment'] = (isset($column['attr']['comment']) ? $column['attr']['comment'] : null);
            $column['attr']['length'] = (isset($column['attr']['length']) ? $column['attr']['length'] : null);
            $column['size'] = (isset($column['size']) ? $column['size'] : null);
            $table->addColumn(
                $name,
                $column['type'],
                ($column['size'] ?: $column['attr']['length']),
                $column['attr'],
                $column['attr']['comment']
            );
        }
    }
}
