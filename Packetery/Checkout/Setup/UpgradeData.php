<?php


namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Packetery\Checkout\Console\Command\MigratePriceRules;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class UpgradeData implements UpgradeDataInterface
{
    /** @var MigratePriceRules */
    private $migratePriceRulesCommand;

    /**
     * UpgradeData constructor.
     *
     * @param \Packetery\Checkout\Console\Command\MigratePriceRules $migratePriceRulesCommand
     */
    public function __construct(MigratePriceRules $migratePriceRulesCommand)
    {
        $this->migratePriceRulesCommand = $migratePriceRulesCommand;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        if (version_compare($context->getVersion(), "2.0.1", ">=") && version_compare($context->getVersion(), "2.0.3", "<")) {
            $this->migratePriceRulesCommand->run(new ArrayInput([]), new NullOutput());
        }
    }
}
