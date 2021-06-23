<?php

declare(strict_types=1);

namespace Packetery\Checkout\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateDefaultPrice extends Command
{
    /** @var \Packetery\Checkout\Model\ResourceModel\PricingruleRepository */
    private $pricingruleRepository;

    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier */
    private $packeteryCarrier;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    private $pricingService;

    /** @var \Magento\Framework\App\Config\ValueFactory */
    private $configValueFactory;

    /**
     * MigratePriceRules constructor.
     *
     * @param \Magento\Config\Model\Config\Factory $configFactory
     * @param \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packeteryCarrier
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     */
    public function __construct(
        \Magento\Config\Model\Config\Factory $configFactory,
        \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packeteryCarrier,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory
    ) {
        parent::__construct();
        $this->configFactory = $configFactory;
        $this->pricingruleRepository = $pricingruleRepository;
        $this->packeteryCarrier = $packeteryCarrier;
        $this->pricingService = $pricingService;
        $this->configValueFactory = $configValueFactory;
    }

    protected function configure(): void {
        $this->setName('packetery:migrate-default-price');
        $this->setDescription('Migrates default price to weight rule');

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     * @throws \Packetery\Checkout\Model\Exception\PricingRuleNotFound
     * @throws \Packetery\Checkout\Model\Exception\WeightRuleMissing
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $configDataCollection = $this->configValueFactory->create()->getCollection();
        $configDataCollection->addFieldToFilter('path', ['like' => 'carriers/packetery/%']);
        $configDataCollection->addFieldToFilter('scope_id', ['gt' => 0]);

        if ($configDataCollection->getSize() > 0) {
            $output->writeln("Multi scope not supported");
            return;
        }

        $configModel = $this->configFactory->create();
        $config = $this->packeteryCarrier->getPacketeryConfig();
        $brain = $this->packeteryCarrier->getPacketeryBrain();

        $allowedMethods = $brain->getFinalAllowedMethods($config, $brain->getMethodSelect());
        $sallowspecific = $configModel->getConfigDataValue('carriers/packetery/sallowspecific');
        $defaultPrice = $configModel->getConfigDataValue('carriers/packetery/default_price');

        if ($sallowspecific !== '1' || !is_numeric($defaultPrice)) {
            $output->writeln("No need to migrate");
            return;
        }

        $output->writeln("Migration started");

        $countries = $configModel->getConfigDataValue('carriers/packetery/specificcountry');
        $countries = explode(',', (string)$countries);

        foreach ($allowedMethods as $allowedMethod) {
            foreach ($countries as $country) {
                if (empty($country)) {
                    continue;
                }

                $resolvedPricingRule = $this->pricingService->resolvePricingRule($allowedMethod, $country, \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain::getCarrierCodeStatic());

                if ($resolvedPricingRule === null) {
                    $pricingRule = [
                        'carrier_code' => \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain::getCarrierCodeStatic(),
                        'carrier_id' => null,
                        'enabled' => false,
                        'free_shipment' => null,
                        'country_id' => $country,
                        'method' => $allowedMethod,
                    ];

                    $weightRules = [
                        [
                            'max_weight' => null,
                            'price' => (float)$defaultPrice,
                        ],
                    ];

                    try {
                        $this->pricingruleRepository->savePricingRule($pricingRule, $weightRules);
                    } catch (\Packetery\Checkout\Model\Exception\DuplicateCountry $e) {
                        $output->writeln("Duplicate country for country $country and method $allowedMethod with fallback weight price $defaultPrice");
                        continue;
                    } catch (\Packetery\Checkout\Model\Exception\InvalidMaxWeight $e) {
                        $output->writeln("Invalid weight for country $country and method $allowedMethod with fallback weight price $defaultPrice");
                        continue;
                    }

                    $output->writeln("New pricing rule inserted for country $country and method $allowedMethod with fallback weight price $defaultPrice");
                }
            }
        }

        $output->writeln("Migration finished");
    }
}
