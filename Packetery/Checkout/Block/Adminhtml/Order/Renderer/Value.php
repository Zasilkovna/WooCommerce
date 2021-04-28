<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Backend\Block\Context;

class Value extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /** @var \Magento\Directory\Model\CurrencyFactory */
    private $currencyFactory;

    /**
     * @param Context $context
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param array $data
     */
    public function __construct(Context $context, \Magento\Directory\Model\CurrencyFactory $currencyFactory, array $data = [])
    {
        parent::__construct($context, $data);
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(DataObject $row): string
    {
        $value = $row->getData('value');

        $options = [
            'display' => \Magento\Framework\Currency::NO_SYMBOL
        ];

        $currency = $this->currencyFactory->create()->load($row->getData('currency'));
        return $currency->formatPrecision((float)$value, PriceCurrencyInterface::DEFAULT_PRECISION, $options, false);
    }
}
