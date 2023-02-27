<?php
/**
 * Class Carrier.
 *
 * @package Packetery\EntityFactory
 */

declare( strict_types=1 );

namespace Packetery\Module\EntityFactory;

use Packetery\Core\Entity;

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
	 * @param array $zpointCarrierData Data from configuration.
	 *
	 * @return Entity\Carrier
	 */
	public function fromZpointCarrierData( array $zpointCarrierData ): Entity\Carrier {
		return new Entity\Carrier(
			(string) $zpointCarrierData['id'],
			$zpointCarrierData['name'],
			(bool) $zpointCarrierData['is_pickup_points'],
			false,
			false,
			false,
			false,
			false,
			false,
			( $zpointCarrierData['supports_cod'] ?? true ),
			$zpointCarrierData['country'],
			$zpointCarrierData['currency'],
			10,
			false,
			$zpointCarrierData['supports_age_verification']
		);
	}
}
