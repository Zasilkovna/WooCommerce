<?php


namespace Packetery\Checkout\Block\System\Config\Form;


class Version extends \Magento\Framework\App\Config\Value
{

    /** @var \Packetery\Checkout\Helper\Data */
    private $helperData;

    public function __construct(
        \Packetery\Checkout\Helper\Data $helperData,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = NULL,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = NULL,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->helperData= $helperData;
    }

    public function afterLoad() {
        $version = $this->helperData->getModuleVersion();
        $this->setValue($version);
    }

}
