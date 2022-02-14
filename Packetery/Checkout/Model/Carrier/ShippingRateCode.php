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
     * @param string $code Carrier code or rate code.
     * @return bool
     */
    public static function isPacketery(string $code): bool {
        return strpos($code, AbstractBrain::PREFIX) !== false;
    }

    /**
     * @param string $rateCode
     * @return static
     */
    public static function fromString(string $rateCode): self {
        if (!self::isPacketery($rateCode)) {
            throw new \Exception('Unsupported carrier code. Only packetery carrier codes are supported.');
        }

        $parts = explode('_', $rateCode, 2);
        return new self($parts[0], MethodCode::fromString($parts[1]));
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
