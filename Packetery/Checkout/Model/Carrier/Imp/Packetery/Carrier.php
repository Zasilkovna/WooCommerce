<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

class Carrier extends \Packetery\Checkout\Model\Carrier\AbstractCarrier
{
    /** @var bool  */
    protected $_isFixed = true;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain */
    protected $packeteryBrain;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain $brain
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain $brain,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $brain, $data);
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain
     */
    public function getPacketeryBrain(): \Packetery\Checkout\Model\Carrier\AbstractBrain {
        return $this->packeteryBrain;
    }
}
