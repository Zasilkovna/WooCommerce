<?php


namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Config\Model\Config\Factory $configFactory
     */
    public function __construct(\Magento\Config\Model\Config\Factory $configFactory)
    {
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        $configModel = $this->configFactory->create();

        if (version_compare($context->getVersion(), "2.0.4", "<")) {
            $configModel->setDataByPath('carriers/packetery/active', 0); // to make user to check values and to trigger validators
            $configModel->save();
        }
    }
}
