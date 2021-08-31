<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Ui\Component\Order\Listing\ByFieldColumnTrait;

class Value extends Column
{
    use ByFieldColumnTrait;

    /** @var \Magento\Directory\Model\CurrencyFactory */
    private $currencyFactory;

    /**
     * Country constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $value = $item[$this->getByField()];
                if (!$value) {
                    continue;
                }

                $options = [
                    'display' => \Magento\Framework\Currency::NO_SYMBOL
                ];

                $currency = $this->currencyFactory->create()->load($item['currency']);
                $item[$this->getData('name')] = $currency->formatPrecision((float)$value, PriceCurrencyInterface::DEFAULT_PRECISION, $options, false);
            }
        }

        return $dataSource;
    }
}
