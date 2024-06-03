<?php
/**
 * Class VendorCollectionFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\PickupPointProvider;

use Packetery\Core\Entity;

/**
 * Class VendorCollectionFactory
 *
 * @package Packetery
 */
class VendorCollectionFactory {

	/**
	 * Creates collection.
	 *
	 * @return VendorProvider[]
	 */
	public function create() {
		return [
			// CZ Packeta internal pickup points.
			new VendorProvider(
				'czzpoint',
				'cz',
				true,
				true,
				'CZK',
				true,
				Entity\Carrier::VENDOR_GROUP_ZPOINT
			),
			// CZ Packeta Z-BOX.
			new VendorProvider(
				'czzbox',
				'cz',
				true,
				true,
				'CZK',
				true,
				'zbox'
			),

			// SK Packeta internal pickup points.
			new VendorProvider(
				'skzpoint',
				'sk',
				true,
				true,
				'EUR',
				true,
				Entity\Carrier::VENDOR_GROUP_ZPOINT
			),
			// SK Packeta Z-BOX.
			new VendorProvider(
				'skzbox',
				'sk',
				true,
				true,
				'EUR',
				true,
				'zbox'
			),

			// HU Packeta internal pickup points.
			new VendorProvider(
				'huzpoint',
				'hu',
				true,
				true,
				'HUF',
				true,
				Entity\Carrier::VENDOR_GROUP_ZPOINT
			),
			// HU Packeta Z-BOX.
			new VendorProvider(
				'huzbox',
				'hu',
				true,
				true,
				'HUF',
				true,
				'zbox'
			),

			// RO Packeta internal pickup points.
			new VendorProvider(
				'rozpoint',
				'ro',
				true,
				true,
				'RON',
				true,
				Entity\Carrier::VENDOR_GROUP_ZPOINT
			),
			// RO Packeta Z-BOX.
			new VendorProvider(
				'rozbox',
				'ro',
				true,
				true,
				'RON',
				true,
				'zbox'
			),
		];
	}

}
