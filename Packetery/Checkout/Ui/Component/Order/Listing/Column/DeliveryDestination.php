<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Misc\ComboPhrase;

class DeliveryDestination extends Column
{
    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * DeliveryDestination constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->methodSelect = $methodSelect;
        $this->carrierFacade = $carrierFacade;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        $cache = [];

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $shippingRateCode = $item['shipping_rate_code'];
                [$carrierCode, $methodCodeString] = explode('_', $shippingRateCode, 2);
                $methodCode = MethodCode::fromString($methodCodeString);
                // make sure you do not use any method requiring country
                $carrier = $this->carrierFacade->createHybridCarrierCached($cache, $carrierCode, $methodCode->getDynamicCarrierId(), $methodCode->getMethod(), '');

                $branchName = (string)$item['point_name'];
                $branchId = $item['point_id'];
                $methodContent = $this->methodSelect->getLabelByValue($methodCode->getMethod());
                if ($branchId && $methodCode->getMethod() === Methods::PICKUP_POINT_DELIVERY) {
                    $methodContent = sprintf("%s (%s)", $branchName, $branchId);
                }

                $item[$this->getData('name')] = new ComboPhrase(
                    [
                        $carrier->getFinalCarrierName(),
                        ' - ',
                        $methodContent,
                    ]
                );
            }
        }

        return $dataSource;
    }

    protected function applySorting() {
        // no DB select sorting
    }
}
