<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic;

/**
 * Optional sub-carrier of Magento fixed carrier
 */
class DynamicCarrier extends \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier
{
    /** @var \Packetery\Checkout\Model\Carrier */
    private $model;

    /**
     * DynamicCarrier constructor.
     *
     * @param \Packetery\Checkout\Model\Carrier $model
     */
    public function __construct(\Packetery\Checkout\Model\Carrier $model) {
        $this->model = $model;
    }

    /**
     * @return int
     */
    public function getCarrierId(): int {
        return $this->model->getCarrierId();
    }

    /**
     * @return string
     */
    public function getCountryId(): string {
        return $this->model->getCountry();
    }

    /**
     * @return float|null
     */
    public function getMaxWeight(): ?float {
        return $this->model->getMaxWeight();
    }

    /**
     * @return bool
     */
    public function getDeleted(): bool {
        return $this->model->getDeleted();
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->model->getName();
    }

    /**
     * @return string
     */
    public function getFinalCarrierName(): string {
        return $this->model->getFinalCarrierName();
    }

    /**
     * @return array
     */
    public function getMethods(): array {
        return [$this->model->getMethod()];
    }
}
