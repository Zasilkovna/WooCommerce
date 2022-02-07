<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

class ShippingRateCode
{
    /**
     * @var string
     */
    private $carrierCode;

    /**
     * @var MethodCode
     */
    private $methodCode;

    /**
     * ShippingRateCode constructor.
     *
     * @param string $carrierCode
     * @param \Packetery\Checkout\Model\Carrier\MethodCode $methodCode
     */
    public function __construct(string $carrierCode, MethodCode $methodCode) {
        $this->carrierCode = $carrierCode;
        $this->methodCode = $methodCode;
    }

    /**
     * @param string $carrierCode
     * @param string $methodCode
     * @return static
     */
    public static function fromStrings(string $carrierCode, string $methodCode): self {
        return new self($carrierCode, MethodCode::fromString($methodCode));
    }

    /**
     * @return string
     */
    public function getCarrierCode(): string {
        return $this->carrierCode;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\MethodCode
     */
    public function getMethodCode(): MethodCode {
        return $this->methodCode;
    }

    /**
     * @return string
     */
    public function toString(): string {
        return $this->carrierCode . '_' . $this->methodCode->toString();
    }
}
