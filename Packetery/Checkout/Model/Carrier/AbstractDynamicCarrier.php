<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

abstract class AbstractDynamicCarrier
{
    /**
     * @return int
     */
    abstract public function getCarrierId(): int;

    /**
     * @return string
     */
    abstract public function getCountryId(): string;

    /**
     * @return float|null
     */
    abstract public function getMaxWeight(): ?float;

    /**
     * @return bool
     */
    abstract public function getDeleted(): bool;

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    abstract public function getFinalCarrierName(): string;

    /**
     * @return array
     */
    abstract public function getMethods(): array;
}
