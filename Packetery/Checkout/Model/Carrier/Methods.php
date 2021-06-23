<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

/**
 * Represents all possible carrier methods
 */
class Methods
{
    public const PICKUP_POINT_DELIVERY = 'pickupPointDelivery';
    public const ADDRESS_DELIVERY = 'addressDelivery'; // BDS
    public const DIRECT_ADDRESS_DELIVERY = 'directAddressDelivery'; // home delivery for specific dynamic carriers

    /**
     * @return string[]
     */
    public static function getAll(): array {
        return [
            self::PICKUP_POINT_DELIVERY,
            self::ADDRESS_DELIVERY,
            self::DIRECT_ADDRESS_DELIVERY,
        ];
    }

    /** Is method BDS or direct?
     * @param string $method
     * @return bool
     */
    public static function isAnyAddressDelivery(string $method): bool {
        return in_array($method, [self::ADDRESS_DELIVERY, self::DIRECT_ADDRESS_DELIVERY]);
    }
}
