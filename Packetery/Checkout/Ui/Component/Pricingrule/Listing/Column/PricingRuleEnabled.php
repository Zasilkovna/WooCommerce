<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Pricingrule\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class PricingRuleEnabled extends Column
{
    /** @var \Packetery\Checkout\Model\Carrier\PacketeryConfig */
    protected $packeteryConfig;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    private $pricingService;

    /**
     * MaxWeight constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->packeteryConfig = $packeteryConfig;
        $this->pricingService = $pricingService;
    }

    /**
     * @param string $method
     * @return \Magento\Framework\Phrase
     */
    private function createCellContent(string $method, string $countryId): \Magento\Framework\Phrase
    {
        $methodAllowed = $this->packeteryConfig->getAllowedMethods()->hasAllowed($method);

        if ($method === AllowedMethods::PICKUP_POINT_DELIVERY) {
            $pointIdResolves = true;
        } else {
            $pointIdResolves = $this->pricingService->resolvePointId($method, $countryId);
        }

        $countryAllowed = $this->packeteryConfig->hasSpecificCountryAllowed($countryId);

        if ($countryAllowed && $methodAllowed && $pointIdResolves) {
            $result = __('Enabled');
        } else {
            $result = __('Disabled');
        }

        return $result;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $this->createCellContent($item["method"], $item["country_id"]);
            }
        }

        return $dataSource;
    }
}
