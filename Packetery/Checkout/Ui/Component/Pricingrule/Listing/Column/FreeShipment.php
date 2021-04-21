<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Pricingrule\Listing\Column;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class FreeShipment extends Column
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory  */
    protected $pricingRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\Carrier\PacketeryConfig */
    protected $packeteryConfig;

    /**
     * Price constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig,
        \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->packeteryConfig = $packeteryConfig;
        $this->pricingRuleCollectionFactory = $pricingRuleCollectionFactory;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (is_numeric($item["free_shipment"])) {
                    $item[$this->getData('name')] = $item["free_shipment"];
                } else {
                    $item[$this->getData('name')] = ($this->packeteryConfig->getFreeShippingThreshold() ?? '');
                }
            }
        }

        return $dataSource;
    }
}
