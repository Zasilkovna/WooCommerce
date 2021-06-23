<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic;

use Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier;

/**
 * PacketaDynamic aggregates feed carriers. Each pricing request requires single carrier.
 */
class DynamicConfig extends \Packetery\Checkout\Model\Carrier\Config\AbstractDynamicConfig
{
    /** @var AbstractDynamicCarrier */
    private $carrier;

    /** @var Config */
    private $config;

    /**
     * @param array $data
     */
    public function __construct(Config $config, AbstractDynamicCarrier $carrier)
    {
        parent::__construct($config->toArray());
        $this->carrier = $carrier;
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function isActive(): bool {
        return parent::isActive() && !$this->carrier->getDeleted();
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTitle() {
        return $this->carrier->getFinalCarrierName();
    }

    /**
     * @return array
     */
    public function getAllowedMethods(): array {
        return $this->carrier->getMethods();
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Config
     */
    public function getConfig(): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig {
        return $this->config;
    }
}
