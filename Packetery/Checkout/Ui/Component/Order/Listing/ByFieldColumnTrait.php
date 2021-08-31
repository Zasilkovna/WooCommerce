<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing;

trait ByFieldColumnTrait
{
    /**
     * Specifies what field value is used as input in field transformation.
     *
     * @return string
     */
    private function getByField(): string {
        return $this->getData('packetery/byField') ?? $this->getData('name');
    }
}
