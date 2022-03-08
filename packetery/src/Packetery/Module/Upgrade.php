<?php
/**
 * Class Upgrade.
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use Packetery\Module\Order\Entity;

/**
 * Class Upgrade.
 */
class Upgrade {

	/**
	 * Order repository.
	 *
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * Logger.
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Order\Repository $orderRepository Order repository.
	 * @param MessageManager   $messageManager  Message manager.
	 * @param ILogger          $logger          Logger.
	 */
	public function __construct(
		Order\Repository $orderRepository,
		MessageManager $messageManager,
		ILogger $logger
	) {
		$this->orderRepository = $orderRepository;
		$this->messageManager  = $messageManager;
		$this->logger          = $logger;
	}

	/**
	 * Checks previous plugin version and runs upgrade if needed.
	 * https://www.sitepoint.com/wordpress-plugin-updates-right-way/
	 *
	 * @return void
	 */
	public function check(): void {
		$oldVersion = get_option( 'packetery_version' );
		if ( Plugin::VERSION === $oldVersion ) {
			return;
		}

		// If no previous version detected, no upgrade will be run.
		if ( $oldVersion && version_compare( $oldVersion, '1.1.2', '<' ) ) {
			$this->upgrade_1_1_2();
		}

		update_option( 'packetery_version', Plugin::VERSION );
	}

	/**
	 * Upgrade to version 1.1.2.
	 *
	 * @return void
	 */
	private function upgrade_1_1_2(): void {
		global $wpdb;

		$createResult = $this->orderRepository->createTable();
		if ( false === $createResult ) {
			$lastError = $wpdb->last_error;
			$this->messageManager->flash_message( __( 'orderTableNotCreatedMoreInformationInPacketaLog', 'packetery' ), MessageManager::TYPE_ERROR );

			$record         = new Record();
			$record->action = Record::ACTION_ORDER_TABLE_NOT_CREATED;
			$record->status = Record::STATUS_ERROR;
			$record->title  = __( 'orderTableNotCreated', 'packetery' );
			$record->params = [
				'errorMessage' => $lastError,
			];
			$this->logger->add( $record );
		}

		// Did not work when called from plugins_loaded hook.
		$orders = wc_get_orders(
			[
				'packetery_all' => '1',
				'nopaging'      => true,
			]
		);

		foreach ( $orders as $order ) {
			$orderEntity = new Core\Entity\Order(
				(string) $order->get_id(),
				null,
				null,
				null,
				$this->getMetaAsNullableFloat( $order, Entity::META_WEIGHT ),
				null,
				$this->getMetaAsNullableString( $order, Entity::META_CARRIER_ID )
			);

			$order->delete_meta_data( Entity::META_WEIGHT );
			$order->delete_meta_data( Entity::META_CARRIER_ID );

			$orderEntity->setPacketStatus( $this->getMetaAsNullableString( $order, Entity::META_PACKET_STATUS ) );
			$order->delete_meta_data( Entity::META_PACKET_STATUS );

			$orderEntity->setIsExported( (bool) $this->getMetaAsNullableString( $order, Entity::META_IS_EXPORTED ) );
			$order->delete_meta_data( Entity::META_IS_EXPORTED );

			$orderEntity->setIsLabelPrinted( (bool) $this->getMetaAsNullableString( $order, Entity::META_IS_LABEL_PRINTED ) );
			$order->delete_meta_data( Entity::META_IS_LABEL_PRINTED );

			$orderEntity->setCarrierNumber( $this->getMetaAsNullableString( $order, Entity::META_CARRIER_NUMBER ) );
			$order->delete_meta_data( Entity::META_CARRIER_NUMBER );

			$orderEntity->setPacketId( $this->getMetaAsNullableString( $order, Entity::META_PACKET_ID ) );
			$order->delete_meta_data( Entity::META_PACKET_ID );

			$orderEntity->setSize(
				new Core\Entity\Size(
					$this->getMetaAsNullableString( $order, Entity::META_LENGTH ),
					$this->getMetaAsNullableString( $order, Entity::META_WIDTH ),
					$this->getMetaAsNullableString( $order, Entity::META_HEIGHT )
				)
			);
			$order->delete_meta_data( Entity::META_LENGTH );
			$order->delete_meta_data( Entity::META_WIDTH );
			$order->delete_meta_data( Entity::META_HEIGHT );

			if ( null !== $this->getMetaAsNullableString( $order, Entity::META_POINT_ID ) ) {
				$orderEntity->setPickupPoint(
					new Core\Entity\PickupPoint(
						$this->getMetaAsNullableString( $order, Entity::META_POINT_ID ),
						$this->getMetaAsNullableString( $order, Entity::META_POINT_NAME ),
						$this->getMetaAsNullableString( $order, Entity::META_POINT_CITY ),
						$this->getMetaAsNullableString( $order, Entity::META_POINT_ZIP ),
						$this->getMetaAsNullableString( $order, Entity::META_POINT_STREET ),
						$this->getMetaAsNullableString( $order, Entity::META_POINT_URL )
					)
				);
			}
			$order->delete_meta_data( Entity::META_POINT_ID );
			$order->delete_meta_data( Entity::META_POINT_NAME );
			$order->delete_meta_data( Entity::META_POINT_CITY );
			$order->delete_meta_data( Entity::META_POINT_ZIP );
			$order->delete_meta_data( Entity::META_POINT_STREET );
			$order->delete_meta_data( Entity::META_POINT_URL );

			$this->orderRepository->save( $orderEntity );
			$order->save_meta_data();
		}
	}


	/**
	 * Gets meta property of order as string.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $key   Meta order key.
	 *
	 * @return string|null
	 */
	private function getMetaAsNullableString( \WC_Order $order, string $key ): ?string {
		$value = $order->get_meta( $key, true );
		return ( ( null !== $value && '' !== $value ) ? (string) $value : null );
	}

	/**
	 * Gets meta property of order as float.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $key   Meta order key.
	 *
	 * @return float|null
	 */
	private function getMetaAsNullableFloat( \WC_Order $order, string $key ): ?float {
		$value = $order->get_meta( $key, true );
		return ( ( null !== $value && '' !== $value ) ? (float) $value : null );
	}

	/**
	 * Transforms custom query variable to meta query.
	 *
	 * @param array $queryVars Query vars.
	 * @param array $get Input values.
	 *
	 * @return array
	 */
	public function handleCustomQueryVar( array $queryVars, array $get ): array {
		$metaQuery = $this->addQueryVars( ( $queryVars['meta_query'] ?? [] ), $get );
		if ( $metaQuery ) {
			// @codingStandardsIgnoreStart
			$queryVars['meta_query'] = $metaQuery;
			// @codingStandardsIgnoreEnd
		}

		return $queryVars;
	}

	/**
	 * Adds query vars to fetch order list.
	 *
	 * @param array $queryVars Query vars.
	 * @param array $get Get parameters.
	 *
	 * @return array
	 */
	public function addQueryVars( array $queryVars, array $get ): array {
		if ( ! empty( $get['packetery_all'] ) ) {
			$queryVars[] = [
				'key'     => Entity::META_CARRIER_ID,
				'compare' => 'EXISTS',
			];
			$queryVars[] = [
				'key'     => Entity::META_CARRIER_ID,
				'value'   => '',
				'compare' => '!=',
			];
		}

		return $queryVars;
	}

}
