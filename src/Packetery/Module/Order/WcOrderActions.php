<?php
/**
 * Class WcOrderActions
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Log;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;

/**
 * Class WcOrderActions
 *
 * @package Packetery
 */
class WcOrderActions {

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Logger.
	 *
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * Constructor.
	 *
	 * @param Log\ILogger     $logger Logger.
	 * @param OptionsProvider $optionsProvider Options provider.
	 * @param Repository      $orderRepository Order repository.
	 */
	public function __construct(
		Log\ILogger $logger,
		OptionsProvider $optionsProvider,
		Repository $orderRepository,
		WcAdapter $wcAdapter
	) {
		$this->logger          = $logger;
		$this->optionsProvider = $optionsProvider;
		$this->orderRepository = $orderRepository;
		$this->wcAdapter       = $wcAdapter;
	}

	/**
	 * Updates order status based on packet status.
	 *
	 * @param string $orderId      Order id.
	 * @param string $packetStatus Packet status.
	 *
	 * @return void
	 */
	public function updateOrderStatus( string $orderId, string $packetStatus ): void {
		if ( ! $this->optionsProvider->isOrderStatusChangeAllowed() ) {
			return;
		}

		$autoOrderStatus = $this->optionsProvider->getAutoOrderStatusFromMapping( $packetStatus );
		if ( $autoOrderStatus === null ) {
			return;
		}

		$validAutoOrderStatus = $this->optionsProvider->getValidAutoOrderStatusFromMapping( $packetStatus );
		if ( $validAutoOrderStatus === '' ) {
			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_ORDER_STATUS_CHANGE;
			$record->status = Log\Record::STATUS_ERROR;

			$record->title = sprintf(
			// translators: %s represents unknown order status.
				__( 'Order status has not been changed, status "%s" doesn\'t exist.', 'packeta' ),
				$autoOrderStatus
			);
			$record->orderId = $orderId;
			$record->params  = [
				'packetStatus' => $packetStatus,
			];
			$this->logger->add( $record );

			return;
		}

		$wcOrder = $this->orderRepository->getWcOrderById( (int) $orderId );
		if ( $wcOrder === null ) {
			$wcLogger = $this->wcAdapter->getLogger();
			$wcLogger->warning( sprintf( 'WC order number %s could not be instantiated.', $orderId ), [ 'source' => 'packeta' ] );

			return;
		}

		$wcOrder->update_status( $validAutoOrderStatus );
	}
}
