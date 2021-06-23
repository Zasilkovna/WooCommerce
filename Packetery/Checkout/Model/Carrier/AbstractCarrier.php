<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;

abstract class AbstractCarrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements CarrierInterface
{
    /** @var \Packetery\Checkout\Model\Carrier\AbstractBrain */
    protected $packeteryBrain;

    /** @var \Packetery\Checkout\Model\Carrier\Config\AbstractConfig */
    protected $packeteryConfig;

    /**
     * AbstractCarrier constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Packetery\Checkout\Model\Carrier\AbstractBrain $brain
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Packetery\Checkout\Model\Carrier\AbstractBrain $brain,
        array $data = []
    ) {
        $this->_code = $brain->getCarrierCode();
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->packeteryBrain = $brain;
        $this->packeteryConfig = $brain->createConfig($this);
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        $result = $this->packeteryBrain->collectRates($this, $request);
        if ($result === null) {
            return false;
        }

        return $result;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\AbstractBrain
     */
    public function getPacketeryBrain(): AbstractBrain {
        return $this->packeteryBrain;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    public function getPacketeryConfig(): AbstractConfig {
        return $this->packeteryConfig;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null $dynamicCarrier
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    public function getPacketeryDynamicConfig(?AbstractDynamicCarrier $dynamicCarrier = null): AbstractConfig {
        return $this->packeteryBrain->createDynamicConfig($this->packeteryConfig, $dynamicCarrier);
    }

    /**
     * getAllowedMethods
     *
     * @param array
     */
    public function getAllowedMethods(): array
    {
        $result = [];
        $select = $this->packeteryBrain->getMethodSelect();
        $selectedMethods = $this->packeteryBrain->getFinalAllowedMethods($this->getPacketeryConfig(), $select);

        foreach ($selectedMethods as $selectedMethod) {
            $result[$selectedMethod] = $select->getLabelByValue($selectedMethod);
        }

        return $result;
    }
}
