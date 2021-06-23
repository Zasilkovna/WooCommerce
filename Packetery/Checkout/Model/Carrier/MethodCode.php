<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

/**
 *  Magento method code of Magento shipping method string that is used in magento JS framework
 */
class MethodCode
{
    /** @var string */
    private $method;

    /** @var int|null */
    private $dynamicCarrierId;

    /**
     * @param string $method
     * @param int|null $dynamicCarrierId
     */
    public function __construct(string $method, ?int $dynamicCarrierId) {
        $this->method = $method;
        $this->dynamicCarrierId = $dynamicCarrierId;
    }

    /**
     * @param string $methodCode
     * @return \Packetery\Checkout\Model\Carrier\MethodCode
     */
    public static function fromString(string $methodCode) {
        $parts = explode('-', $methodCode);
        $method = array_pop($parts);
        $dynamicCarrierId = array_pop($parts);

        if ($dynamicCarrierId !== null) {
            $dynamicCarrierId = (int)$dynamicCarrierId;
        }

        return new self($method, $dynamicCarrierId);
    }

    /**
     * @return string
     */
    public function toString(): string {
        if ($this->dynamicCarrierId !== null) {
            return "{$this->dynamicCarrierId}-{$this->method}";
        }

        return $this->method;
    }

    /**
     * @return string
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * @return int|null
     */
    public function getDynamicCarrierId(): ?int {
        return $this->dynamicCarrierId;
    }
}
