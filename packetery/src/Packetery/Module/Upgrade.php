<?php
/**
 * Class Upgrade.
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module;

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
		// Update the version if no upgrade is run.
		$result = true;

		// If no previous version detected, no upgrade will be run.
		if ( $oldVersion && version_compare( $oldVersion, '1.1.2', '<' ) ) {
			$result = $this->upgrade_1_1_2();
		}

		if ( $result ) {
			update_option( 'packetery_version', Plugin::VERSION );
		}
	}

	/**
	 * Upgrade to version 1.1.2.
	 *
	 * @return bool
	 */
	private function upgrade_1_1_2(): bool {
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

		$possibleKeys = [
			Order\Entity::META_CARRIER_ID,
			Order\Entity::META_IS_EXPORTED,
			Order\Entity::META_PACKET_ID,
			Order\Entity::META_IS_LABEL_PRINTED,
			Order\Entity::META_POINT_ID,
			Order\Entity::META_POINT_NAME,
			Order\Entity::META_POINT_URL,
			Order\Entity::META_POINT_STREET,
			Order\Entity::META_POINT_ZIP,
			Order\Entity::META_POINT_CITY,
			Order\Entity::META_WEIGHT,
			Order\Entity::META_LENGTH,
			Order\Entity::META_WIDTH,
			Order\Entity::META_HEIGHT,
			Order\Entity::META_CARRIER_NUMBER,
			Order\Entity::META_PACKET_STATUS,
		];

		foreach ( $orders as $order ) {
			$propsToSave = [ 'id' => $order->get_id() ];
			foreach ( $possibleKeys as $key ) {
				$value = $order->get_meta( $key );
				if ( '' !== (string) $value ) {
					$propsToSave[ $key ] = $value;
					$order->delete_meta_data( $key );
				}
			}
			$this->orderRepository->insert( $propsToSave );
			$order->save_meta_data();
		}

		return true;
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
				'key'     => Order\Entity::META_CARRIER_ID,
				'compare' => 'EXISTS',
			];
		}

		return $queryVars;
	}

}
