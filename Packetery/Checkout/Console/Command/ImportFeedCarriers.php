<?php

declare(strict_types=1);

namespace Packetery\Checkout\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function GuzzleHttp\json_decode;

class ImportFeedCarriers extends Command
{
    /** @var \GuzzleHttp\Client */
    private $client;

    /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory */
    private $collectionFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Packetery\Checkout\Model\ResourceModel\PricingruleRepository */
    private $pricingRuleRepository;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /** @var \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier */
    private $modifier;

    /**
     * @param \GuzzleHttp\Client $client
     * @param \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingRuleRepository
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     * @param \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier
     */
    public function __construct(
        \GuzzleHttp\Client $client,
        \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $collectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingRuleRepository,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier
    ) {
        parent::__construct();
        $this->client = $client;
        $this->collectionFactory = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->pricingRuleRepository = $pricingRuleRepository;
        $this->carrierFacade = $carrierFacade;
        $this->modifier = $modifier;
    }

    /**
     *  Command general configuration
     */
    protected function configure(): void {
        $this->setName('packetery:import-feed-carriers');
        $this->setDescription('Import Packeta branch feed to carrier database table');

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Carrier feed import started');

        $apiKey = $this->scopeConfig->getValue('carriers/packetery/api_key'); // default scope
        $response = $this->client->get("https://www.zasilkovna.cz/api/v4/{$apiKey}/branch.json?address-delivery");
        $content = $response->getBody()->getContents();
        $data = json_decode($content);

        if (empty($data) || !isset($data->carriers)) {
            $output->writeln('An error has occurred');
            return;
        }

        /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->setDataToAll(
            [
                'deleted' => true,
            ]
        );
        $collection->save();

        foreach ($data->carriers as $carrier) {
            $data = [
                'carrier_id' => (int)$carrier->id,
                'name' => $carrier->name,
                'is_pickup_points' => $this->parseBool($carrier->pickupPoints), // false === addressDelivery
                'has_carrier_direct_label' => $this->parseBool($carrier->apiAllowed),
                'separate_house_number' => $this->parseBool($carrier->separateHouseNumber),
                'customs_declarations' => $this->parseBool($carrier->customsDeclarations),
                'requires_email' => $this->parseBool($carrier->requiresEmail),
                'requires_phone' => $this->parseBool($carrier->requiresPhone),
                'requires_size' => $this->parseBool($carrier->requiresSize),
                'disallows_cod' => $this->parseBool($carrier->disallowsCod),
                'country' => strtoupper($carrier->country),
                'currency' => $carrier->currency,
                'max_weight' => (float)$carrier->maxWeight,
                'deleted' => false,
            ];

            /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\Collection $collection */
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('carrier_id', $carrier->id);
            $record = $collection->fetchItem();

            if (!$record) {
                $record = $collection->getNewEmptyItem();
                $record->setData($data);
                $collection->addItem($record);
                $collection->save();
            } else {
                /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\Collection $collection */
                $collection = $this->collectionFactory->create();
                $collection->addFieldToFilter('id', $record->getId());
                $collection->setDataToAll($data);
                $collection->save();
            }
        }

        $rules = [];
        foreach ($this->carrierFacade->getAllAvailableCountries() as $country) {
            $rules = array_merge($rules, $this->modifier->getPricingRulesForCountry($country));
        }

        $this->pricingRuleRepository->disablePricingRulesExcept($rules);

        $output->writeln('Carrier feed import ended successfully');
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function parseBool($value): bool {
        if ($value === 'false') {
            return false;
        }

        return (bool)$value;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function runForCron(): int {
        return $this->run(new ArrayInput([]), new NullOutput());
    }
}

