<?php
/**
 * Class CreatePacketFactory
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

use Packetery\Core\Entity\Order;
use Packetery\Core\Helper;
use Packetery\Module\Options\Provider;
use Packetery\Module\Carrier\Repository;
use WC_Order;

/**
 * Class CreatePacketFactory
 *
 * @package Packetery\Api
 */
class CreatePacketFactory {
	/**
	 * Options provider.
	 *
	 * @var Provider
	 */
	private $optionsProvider;

	/**
	 * Carrier repository
	 *
	 * @var Repository
	 */
	private $carrierRepository;

	/**
	 * CreatePacketFactory constructor.
	 *
	 * @param Provider   $optionsProvider Options provider.
	 * @param Repository $carrierRepository Carrier repository.
	 */
	public function __construct( Provider $optionsProvider, Repository $carrierRepository ) {
		$this->optionsProvider   = $optionsProvider;
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

		if ( null !== $order->getCod() ) {
			$carrierId = $order->getCarrierId();
			if ( Repository::INTERNAL_PICKUP_POINTS_ID === $carrierId ) {
				$wcOrder = wc_get_order( $order->getNumber() );
				if ( $wcOrder instanceof WC_Order ) {
					$carrierId = $this->carrierRepository->getZpointCarrierIdByCountry( strtolower( $wcOrder->get_shipping_country() ) );
				}
			}

			$roundingType = $this->optionsProvider->getCarrierRoundingType( $carrierId );
			$roundedCod   = Helper::customRoundByCurrency( $order->getCod(), $roundingType, $order->getCurrency() );
			$newCreatePacket->setCod( $roundedCod );
		}

		return $newCreatePacket;
	}
}
