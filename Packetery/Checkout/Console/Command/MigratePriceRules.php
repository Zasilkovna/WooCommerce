<?php

declare(strict_types=1);

namespace Packetery\Checkout\Console\Command;

use Packetery\Checkout\Model\Carrier\Methods;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigratePriceRules extends Command
{
    /** @var \Packetery\Checkout\Model\ResourceModel\PricingruleRepository */
    private $pricingruleRepository;

    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /** @var \Magento\Framework\App\Config\ValueFactory */
    private $configValueFactory;

    /**
     * MigratePriceRules constructor.
     *
     * @param \Magento\Config\Model\Config\Factory $configFactory
     * @param \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     */
    public function __construct(\Magento\Config\Model\Config\Factory $configFactory, \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository, \Magento\Framework\App\Config\ValueFactory $configValueFactory)
    {
        parent::__construct();
        $this->configFactory = $configFactory;
        $this->pricingruleRepository = $pricingruleRepository;
        $this->configValueFactory = $configValueFactory;
    }

    /**
     *  Command general configuration
     */
    protected function configure(): void
    {
        $this->setName('packetery:migrate-price-rules');
        $this->setDescription('Migrates price rules to 2.0.3 data structure');

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     * @throws \Packetery\Checkout\Model\Exception\PricingRuleNotFound
     * @throws \Packetery\Checkout\Model\Exception\WeightRuleMissing
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configDataCollection = $this->configValueFactory->create()->getCollection();
        $configDataCollection->addFieldToFilter('scope_id', ['gt' => 0]);

        if ($configDataCollection->getSize() > 0) {
            $output->writeln("Multi scope not supported");
            return;
        }

        $output->writeln("Migration started");

        $configModel = $this->configFactory->create();

        $apiKey = $configModel->getConfigDataValue('widget/options/api_key');
        $codMethods = $configModel->getConfigDataValue('packetery_cod/general/payment_methods');

        $globalPrice = $configModel->getConfigDataValue('packetery_rules/rules_global/default_price');
        $globalMaxWeight = $configModel->getConfigDataValue('packetery_rules/rules_global/max_weight');
        $globalFreeShipping = $configModel->getConfigDataValue('packetery_rules/rules_global/free_shipping');

        $configModel->setDataByPath('carriers/packetery/active', 0); // to make user to check values and to trigger validators
        $output->writeln("Module was deactivated");
        $configModel->setDataByPath('carriers/packetery/api_key', $apiKey);
        $configModel->setDataByPath('carriers/packetery/default_price', $globalPrice);
        $configModel->setDataByPath('carriers/packetery/max_weight', $globalMaxWeight);
        $configModel->setDataByPath('carriers/packetery/free_shipping_enable', (is_numeric($globalFreeShipping) ? 1 : 0));
        $configModel->setDataByPath('carriers/packetery/free_shipping_subtotal', (is_numeric($globalFreeShipping) ? $globalFreeShipping : ''));
        $configModel->setDataByPath('carriers/packetery/cod_methods', $codMethods);
        $configModel->save();

        $output->writeln("Packetery carrier was updated");

        $countriesWithOther = ['cz', 'sk', 'pl', 'hu', 'ro', 'default'];
        foreach ($countriesWithOther as $country) {
            $countryDefaultPrice = $configModel->getConfigDataValue("packetery_rules/rules_$country/default_price");
            $countryDefaultPrice = str_replace(',', '.', (string)$countryDefaultPrice);
            $countryFreeShipping = $configModel->getConfigDataValue("packetery_rules/rules_$country/free_shipping");
            $countryFreeShipping = str_replace(',', '.', (string)$countryFreeShipping);
            $countryRules = $configModel->getConfigDataValue("packetery_rules/rules_$country/rules"); // json e.g.: {"_1613049082069_69":{"from":"0","to":"5","price":"79"}}
            $countryRules = (json_decode(($countryRules ?: '[]'), true) ?: []);

            if (!is_numeric($countryDefaultPrice)) {
                continue; // price rules are not defined
            }

            if ($country === 'default') {
                // new structures do not allow other country configuration
                $configModel->setDataByPath('carriers/packetery/default_price', $countryDefaultPrice);
                if (is_numeric($countryFreeShipping)) {
                    $configModel->setDataByPath('carriers/packetery/free_shipping_enable', 1);
                    $configModel->setDataByPath('carriers/packetery/free_shipping_subtotal', $countryFreeShipping);
                }
                $configModel->save();
                continue;
            }

            $weightRules = [];
            $weightRules[] = [
                'max_weight' => null, // will use global weight as fallback
                'price' => (float)$countryDefaultPrice
            ];

            $pricingRule = [
                'carrier_code' => \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain::getCarrierCodeStatic(),
                'carrier_id' => null,
                'enabled' => false,
                'free_shipment' => (is_numeric($countryFreeShipping) ? (float)$countryFreeShipping : null),
                'country_id' => strtoupper($country),
                'method' => Methods::PICKUP_POINT_DELIVERY,
            ];

            usort($countryRules, function ($countryRuleA, $countryRuleB) {
                if ($countryRuleA['from'] === $countryRuleB['from']) {
                    return 0;
                }

                return ($countryRuleA['from'] > $countryRuleB['from'] ? 1 : -1);
            });

            $previousCountryRule = null;
            foreach ($countryRules as $countryRule) {

                $countryRuleFrom = (string)$countryRule['from'];
                $countryRuleFrom = str_replace(',', '.', $countryRuleFrom);
                $countryRuleTo = (string)$countryRule['to'];
                $countryRuleTo = str_replace(',', '.', $countryRuleTo);
                $countryRulePrice = (string)$countryRule['price'];
                $countryRulePrice = str_replace(',', '.', $countryRulePrice);
                $previousTo = ($previousCountryRule ? $previousCountryRule['to'] : null);
                $previousTo = ($previousTo ? str_replace(',', '.', (string)$previousTo) : null);

                if (!is_numeric($countryRuleFrom) || !is_numeric($countryRuleTo) || !is_numeric($countryRulePrice)) {
                    continue;
                }

                if (empty($countryRuleTo)) {
                    continue;
                }

                if ($previousCountryRule === null && !empty($countryRuleFrom)) {
                    $weightRules[] = [
                        'price' => (float)$countryDefaultPrice,
                        'max_weight' => (float)$countryRuleFrom
                    ];
                }

                if ($previousCountryRule && is_numeric($previousTo) && $previousTo < $countryRuleFrom) {
                    $weightRules[] = [
                        'price' => (float)$countryDefaultPrice,
                        'max_weight' => (float)$countryRuleFrom
                    ];
                }

                $weightRules[] = [
                    'price' => (float)$countryRulePrice,
                    'max_weight' => (float)$countryRuleTo
                ];

                $previousCountryRule = $countryRule;
            }

            try {
                $this->pricingruleRepository->savePricingRule($pricingRule, $weightRules);
            } catch (\Packetery\Checkout\Model\Exception\DuplicateCountry $e) {
                $output->writeln("Price rule for $country already exists. Skipping.");
                continue;
            } catch (\Packetery\Checkout\Model\Exception\InvalidMaxWeight $e) {
                $output->writeln("Max weight for $country exceed maximum allowed. Skipping.");
                continue;
            }

            $weightRuleCount = count($weightRules);
            $output->writeln("Price rule for $country was added with $weightRuleCount weight rules");
        }

        $output->writeln("Migration finished");
    }
}
