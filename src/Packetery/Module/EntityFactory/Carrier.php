<?php
/**
 * Class Carrier.
 *
 * @package Packetery\EntityFactory
 */

declare( strict_types=1 );

namespace Packetery\Module\EntityFactory;

use Packetery\Core\Entity;
use Packetery\Core\PickupPointProvider\BaseProvider;

/**
 * Class Carrier.
 *
 * @package Packetery\EntityFactory
 */
class Carrier {

	/**
	 * Carrier factory.
	 *
	 * @param array $dbResult Data from db.
	 *
	 * @return Entity\Carrier
	 */
	public function fromDbResult( array $dbResult ): Entity\Carrier {
		return new Entity\Carrier(
			$dbResult['id'],
			$dbResult['name'],
			(bool) $dbResult['is_pickup_points'],
			(bool) $dbResult['has_carrier_direct_label'],
			(bool) $dbResult['separate_house_number'],
			(bool) $dbResult['customs_declarations'],
			(bool) $dbResult['requires_email'],
			(bool) $dbResult['requires_phone'],
			(bool) $dbResult['requires_size'],
			! (bool) $dbResult['disallows_cod'],
			$dbResult['country'],
			$dbResult['currency'],
			(float) $dbResult['max_weight'],
			(bool) $dbResult['deleted'],
			false
		);
	}

	/**
	 * Carrier factory.
	 *
	 * @param BaseProvider $nonFeedCarrierProvider Data from configuration.
	 *
	 * @return Entity\Carrier
	 */
	public function fromNonFeedCarrierData( BaseProvider $nonFeedCarrierProvider ): Entity\Carrier {
		return new Entity\Carrier(
			(string) $nonFeedCarrierProvider->getId(),
			$nonFeedCarrierProvider->getName(),
			(bool) $nonFeedCarrierProvider->hasPickupPoints(),
			false,
			false,
			false,
			false,
			false,
			false,
			( $nonFeedCarrierProvider->supportsCod() ?? true ),
			$nonFeedCarrierProvider->getCountry(),
			$nonFeedCarrierProvider->getCurrency(),
			10,
			false,
			$nonFeedCarrierProvider->supportsAgeVerification()
		);
	}
}
