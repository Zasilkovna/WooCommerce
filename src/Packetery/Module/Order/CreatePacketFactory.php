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

/**
 * Class CreatePacketFactory
 *
 * @package Packetery\Api
 */
class CreatePacketFactory {

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
			$roundingType = Options::createByCarrierId( $order->getCarrierCode() )->getCodRoundingType();
			$roundedCod   = Helper::customRoundByCurrency( $order->getCod(), $roundingType, $order->getCurrency() );
			$newCreatePacket->setCod( $roundedCod );
		}

		return $newCreatePacket;
	}
}
