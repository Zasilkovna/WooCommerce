<?php
/**
 * Class CreatePacketFactory
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap\Request\CreatePacket;
use Packetery\Core\Entity\Order;
use Packetery\Core\Helper;
use Packetery\Module\Carrier\Options;
use Packetery\Module\Carrier\Repository;

/**
 * Class CreatePacketFactory
 *
 * @package Packetery\Api
 */
class CreatePacketFactory {

	/**
	 * Carrier repository
	 *
	 * @var Repository
	 */
	private $carrierRepository;

	/**
	 * CreatePacketFactory constructor.
	 *
	 * @param Repository $carrierRepository Carrier repository.
	 */
	public function __construct( Repository $carrierRepository ) {
		$this->carrierRepository = $carrierRepository;
	}

	/**
	 * Creates new instance of CreatePacket from Order.
	 *
	 * @param Order $order Order entity.
	 *
	 * @return CreatePacket
	 */
	public function create( Order $order ): CreatePacket {

		$newCreatePacket = new CreatePacket( $order );

		if ( $order->hasCod() ) {
			$carrierId = $order->isExternalCarrier()
				? $order->getCarrierId()
				: $this->carrierRepository->getZpointCarrierIdByCountry( $order->getShippingCountry() );

			$roundingType = Options::createByCarrierId( $carrierId )->getCodRoundingType();
			$roundedCod   = Helper::customRoundByCurrency( $order->getCod(), $roundingType, $order->getCurrency() );
			$newCreatePacket->setCod( $roundedCod );
		}

		return $newCreatePacket;
	}
}
