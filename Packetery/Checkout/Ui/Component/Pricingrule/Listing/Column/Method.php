<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Pricingrule\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class Method extends Column
{
    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->methodSelect = $methodSelect;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $phrase = $this->methodSelect->getLabelByValue($item["method"]);
                $item[$this->getData('name')] = ($phrase !== null ? $phrase : $item["method"]);
            }
        }

        return $dataSource;
    }
}
