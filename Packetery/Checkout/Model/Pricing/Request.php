<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Pricing;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Packetery\Checkout\Model\Carrier\PacketeryConfig;

class Request
{
    /** @var \Magento\Quote\Model\Quote\Address\RateRequest */
    private $rateRequest;

    /** @var \Packetery\Checkout\Model\Carrier\PacketeryConfig */
    private $carrierConfig;

    /** @var string  */
    private $carrierCode;

    /**
     * Request constructor.
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $rateRequest
     * @param \Packetery\Checkout\Model\Carrier\PacketeryConfig $carrierConfig
     * @param string $carrierCode
     */
    public function __construct(RateRequest $rateRequest, PacketeryConfig $carrierConfig, string $carrierCode)
    {
        $this->rateRequest = $rateRequest;
        $this->carrierConfig = $carrierConfig;
        $this->carrierCode = $carrierCode;
    }

    /**
     * @return \Magento\Quote\Model\Quote\Address\RateRequest
     */
    public function getRateRequest(): RateRequest
    {
        return $this->rateRequest;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\PacketeryConfig
     */
    public function getCarrierConfig(): PacketeryConfig
    {
        return $this->carrierConfig;
    }

    /**
     * @return string
     */
    public function getCarrierCode(): string
    {
        return $this->carrierCode;
    }
}
