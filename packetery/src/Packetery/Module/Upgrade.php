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

	const META_LENGTH           = 'packetery_length';
	const META_WIDTH            = 'packetery_width';
	const META_HEIGHT           = 'packetery_height';
	const META_PACKET_STATUS    = 'packetery_packet_status';
	const META_WEIGHT           = 'packetery_weight';
	const META_CARRIER_ID       = 'packetery_carrier_id';
	const META_IS_EXPORTED      = 'packetery_is_exported';
	const META_IS_LABEL_PRINTED = 'packetery_is_label_printed';
	const META_CARRIER_NUMBER   = 'packetery_carrier_number';
	const META_PACKET_ID        = 'packetery_packet_id';
	const META_POINT_ID         = 'packetery_point_id';
	const META_POINT_NAME       = 'packetery_point_name';
	const META_POINT_CITY       = 'packetery_point_city';
	const META_POINT_ZIP        = 'packetery_point_zip';
	const META_POINT_STREET     = 'packetery_point_street';
	const META_POINT_URL        = 'packetery_point_url';

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
		if ( $oldVersion && version_compare( $oldVersion, '1.2.0', '<' ) ) {
			$this->migrateWpOrderMetadata();
		}

		update_option( 'packetery_version', Plugin::VERSION );
	}

	/**
	 * Migrates WP order metadata.
	 *
	 * @return void
	 */
	private function migrateWpOrderMetadata(): void {
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
				$this->getMetaAsNullableString( $order, self::META_CARRIER_ID )
			);
			$order->delete_meta_data( self::META_CARRIER_ID );

			$orderEntity->setWeight( Core\Helper::simplifyWeight( $this->getMetaAsNullableFloat( $order, self::META_WEIGHT ) ) );
			$order->delete_meta_data( self::META_WEIGHT );

			$orderEntity->setPacketStatus( $this->getMetaAsNullableString( $order, self::META_PACKET_STATUS ) );
			$order->delete_meta_data( self::META_PACKET_STATUS );

			$orderEntity->setIsExported( (bool) $this->getMetaAsNullableString( $order, self::META_IS_EXPORTED ) );
			$order->delete_meta_data( self::META_IS_EXPORTED );

			$orderEntity->setIsLabelPrinted( (bool) $this->getMetaAsNullableString( $order, self::META_IS_LABEL_PRINTED ) );
			$order->delete_meta_data( self::META_IS_LABEL_PRINTED );

			$orderEntity->setCarrierNumber( $this->getMetaAsNullableString( $order, self::META_CARRIER_NUMBER ) );
			$order->delete_meta_data( self::META_CARRIER_NUMBER );

			$orderEntity->setPacketId( $this->getMetaAsNullableString( $order, self::META_PACKET_ID ) );
			$order->delete_meta_data( self::META_PACKET_ID );

			$orderEntity->setSize(
				new Core\Entity\Size(
					$this->getMetaAsNullableFloat( $order, self::META_LENGTH ),
					$this->getMetaAsNullableFloat( $order, self::META_WIDTH ),
					$this->getMetaAsNullableFloat( $order, self::META_HEIGHT )
				)
			);
			$order->delete_meta_data( self::META_LENGTH );
			$order->delete_meta_data( self::META_WIDTH );
			$order->delete_meta_data( self::META_HEIGHT );

			if ( null !== $this->getMetaAsNullableString( $order, self::META_POINT_ID ) ) {
				$orderEntity->setPickupPoint(
					new Core\Entity\PickupPoint(
						$this->getMetaAsNullableString( $order, self::META_POINT_ID ),
						$this->getMetaAsNullableString( $order, self::META_POINT_NAME ),
						$this->getMetaAsNullableString( $order, self::META_POINT_CITY ),
						$this->getMetaAsNullableString( $order, self::META_POINT_ZIP ),
						$this->getMetaAsNullableString( $order, self::META_POINT_STREET ),
						$this->getMetaAsNullableString( $order, self::META_POINT_URL )
					)
				);
			}
			$order->delete_meta_data( self::META_POINT_ID );
			$order->delete_meta_data( self::META_POINT_NAME );
			$order->delete_meta_data( self::META_POINT_CITY );
			$order->delete_meta_data( self::META_POINT_ZIP );
			$order->delete_meta_data( self::META_POINT_STREET );
			$order->delete_meta_data( self::META_POINT_URL );

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
	private function addQueryVars( array $queryVars, array $get ): array {
		if ( ! empty( $get['packetery_all'] ) ) {
			$queryVars[] = [
				'key'     => self::META_CARRIER_ID,
				'compare' => 'EXISTS',
			];
			$queryVars[] = [
				'key'     => self::META_CARRIER_ID,
				'value'   => '',
				'compare' => '!=',
			];
		}

		return $queryVars;
	}

}
