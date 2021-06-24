<?php

namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /**
     * @param \Magento\Config\Model\Config\Factory $configFactory
     */
    public function __construct(\Magento\Config\Model\Config\Factory $configFactory) {
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $configModel = $this->configFactory->create();
            $configModel->setDataByPath('carriers/packetery/sallowspecific', 0); // config option UI was removed
            $configModel->save();
        }
    }
}
