<?php
/**
 * Class CompoundCarrierCollectionFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\PickupPointProvider;

/**
 * Class CompoundCarrierCollectionFactory
 *
 * @package Packetery
 */
class CompoundCarrierCollectionFactory {

	/**
	 * Creates collection.
	 * TODO: take into account that not all types of pickup points support age verification.
	 * Can lead to situation with no pickup points available when age verification is required.
	 *
	 * @return CompoundProvider[]
	 */
	public function create() {
		return [
			// CZ Packeta pickup points.
			new CompoundProvider(
				'zpointcz',
				'cz',
				true,
				true,
				'CZK',
				true,
				[
					'czzpoint',
					'czzbox',
				]
			),
			// SK Packeta pickup points.
			new CompoundProvider(
				'zpointsk',
				'sk',
				true,
				true,
				'EUR',
				true,
				[
					'skzpoint',
					'skzbox',
				]
			),
			// HU Packeta pickup points.
			new CompoundProvider(
				'zpointhu',
				'hu',
				true,
				true,
				'HUF',
				true,
				[
					'huzpoint',
					'huzbox',
				]
			),
			// RO Packeta pickup points.
			new CompoundProvider(
				'zpointro',
				'ro',
				true,
				true,
				'RON',
				true,
				[
					'rozpoint',
					'rozbox',
				]
			),
		];
	}

}
