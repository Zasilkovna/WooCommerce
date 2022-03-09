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
				$this->getMetaAsNullableFloat( $order, 'packetery_weight' ),
				null,
				$this->getMetaAsNullableString( $order, 'packetery_carrier_id' )
			);

			$order->delete_meta_data( 'packetery_weight' );
			$order->delete_meta_data( 'packetery_carrier_id' );

			$orderEntity->setPacketStatus( $this->getMetaAsNullableString( $order, 'packetery_packet_status' ) );
			$order->delete_meta_data( 'packetery_packet_status' );

			$orderEntity->setIsExported( (bool) $this->getMetaAsNullableString( $order, 'packetery_is_exported' ) );
			$order->delete_meta_data( 'packetery_is_exported' );

			$orderEntity->setIsLabelPrinted( (bool) $this->getMetaAsNullableString( $order, 'packetery_is_label_printed' ) );
			$order->delete_meta_data( 'packetery_is_label_printed' );

			$orderEntity->setCarrierNumber( $this->getMetaAsNullableString( $order, 'packetery_carrier_number' ) );
			$order->delete_meta_data( 'packetery_carrier_number' );

			$orderEntity->setPacketId( $this->getMetaAsNullableString( $order, 'packetery_packet_id' ) );
			$order->delete_meta_data( 'packetery_packet_id' );

			$orderEntity->setSize(
				new Core\Entity\Size(
					$this->getMetaAsNullableString( $order, 'packetery_length' ),
					$this->getMetaAsNullableString( $order, 'packetery_width' ),
					$this->getMetaAsNullableString( $order, 'packetery_height' )
				)
			);
			$order->delete_meta_data( 'packetery_length' );
			$order->delete_meta_data( 'packetery_width' );
			$order->delete_meta_data( 'packetery_height' );

			if ( null !== $this->getMetaAsNullableString( $order, 'packetery_point_id' ) ) {
				$orderEntity->setPickupPoint(
					new Core\Entity\PickupPoint(
						$this->getMetaAsNullableString( $order, 'packetery_point_id' ),
						$this->getMetaAsNullableString( $order, 'packetery_point_name' ),
						$this->getMetaAsNullableString( $order, 'packetery_point_city' ),
						$this->getMetaAsNullableString( $order, 'packetery_point_zip' ),
						$this->getMetaAsNullableString( $order, 'packetery_point_street' ),
						$this->getMetaAsNullableString( $order, 'packetery_point_url' )
					)
				);
			}
			$order->delete_meta_data( 'packetery_point_id' );
			$order->delete_meta_data( 'packetery_point_name' );
			$order->delete_meta_data( 'packetery_point_city' );
			$order->delete_meta_data( 'packetery_point_zip' );
			$order->delete_meta_data( 'packetery_point_street' );
			$order->delete_meta_data( 'packetery_point_url' );

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
				'key'     => 'packetery_carrier_id',
				'compare' => 'EXISTS',
			];
			$queryVars[] = [
				'key'     => 'packetery_carrier_id',
				'value'   => '',
				'compare' => '!=',
			];
		}

		return $queryVars;
	}

}
