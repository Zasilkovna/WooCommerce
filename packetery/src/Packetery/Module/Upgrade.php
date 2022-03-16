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

	const POST_TYPE_VALIDATED_ADDRESS = 'packetery_address';

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
	 * Log repository.
	 *
	 * @var Log\Repository
	 */
	private $logRepository;

	/**
	 * Constructor.
	 *
	 * @param Order\Repository $orderRepository Order repository.
	 * @param MessageManager   $messageManager  Message manager.
	 * @param ILogger          $logger          Logger.
	 * @param Log\Repository   $logRepository   Log repository.
	 */
	public function __construct(
		Order\Repository $orderRepository,
		MessageManager $messageManager,
		ILogger $logger,
		Log\Repository $logRepository
	) {
		$this->orderRepository = $orderRepository;
		$this->messageManager  = $messageManager;
		$this->logger          = $logger;
		$this->logRepository   = $logRepository;
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
			$this->logRepository->createTable();
			$logEntries = get_posts(
				[
					'post_type'   => 'packetery_log',
					'post_status' => 'any',
					'nopaging'    => true,
					'fields'      => 'ids',
				]
			);
			foreach ( $logEntries as $logEntryId ) {
				wp_delete_post( $logEntryId, true );
			}

			unregister_post_type( 'packetery_log' );

			$this->migrateWpOrderMetadata();
			$addressEntries = get_posts(
				[
					'post_type'   => self::POST_TYPE_VALIDATED_ADDRESS,
					'post_status' => 'any',
					'nopaging'    => true,
					'fields'      => 'ids',
				]
			);
			foreach ( $addressEntries as $addressEntryId ) {
				wp_delete_post( $addressEntryId, true );
			}

			unregister_post_type( self::POST_TYPE_VALIDATED_ADDRESS );
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

			$validatedAddressId = $this->getValidatedAddressIdByOrderId( (int) $order->get_id() );
			if ( $validatedAddressId ) {
				$validatedAddress = $this->createAddressFromPostId( $validatedAddressId );
				$orderEntity->setAddressValidated( true );
				$orderEntity->setDeliveryAddress( $validatedAddress );
			}

			$this->orderRepository->save( $orderEntity );
			$order->save_meta_data();
		}
	}

	/**
	 * Creates active widget address using woocommerce order id.
	 *
	 * @param int $addressId Address ID.
	 *
	 * @return Core\Entity\Address|null
	 */
	public function createAddressFromPostId( int $addressId ): ?Core\Entity\Address {
		$address = new Core\Entity\Address(
			get_post_meta( $addressId, 'street', true ),
			get_post_meta( $addressId, 'city', true ),
			get_post_meta( $addressId, 'postCode', true )
		);
		$address->setHouseNumber( get_post_meta( $addressId, 'houseNumber', true ) );
		$address->setCounty( get_post_meta( $addressId, 'county', true ) );
		$address->setLongitude( get_post_meta( $addressId, 'longitude', true ) );
		$address->setLatitude( get_post_meta( $addressId, 'latitude', true ) );

		return $address;
	}

	/**
	 * Gets active address.
	 *
	 * @param int $orderId Order ID.
	 *
	 * @return int|null
	 */
	public function getValidatedAddressIdByOrderId( int $orderId ): ?int {
		$postIds = get_posts(
			[
				'post_type'   => self::POST_TYPE_VALIDATED_ADDRESS,
				'post_status' => 'any',
				'nopaging'    => true,
				'numberposts' => 1,
				'fields'      => 'ids',
				'post_parent' => $orderId,
			]
		);

		if ( empty( $postIds ) ) {
			return null;
		}

		return (int) array_shift( $postIds );
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
