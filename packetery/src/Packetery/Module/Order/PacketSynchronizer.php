<?php
/**
 * Class Synchronizer
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

use Packetery\Core\Api;
use Packetery\Core\Log;
use Packetery\Module\Options;

/**
 * Class Synchronizer
 *
 * @package Packetery\Module\Order
 */
class PacketSynchronizer {

	/**
	 * API soap client.
	 *
	 * @var Api\Soap\Client
	 */
	private $apiSoapClient;

	/**
	 * Options provider.
	 *
	 * @var Options\Provider
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
	 * @var DbRepository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Api\Soap\Client  $apiSoapClient   API soap client.
	 * @param Log\ILogger      $logger          Logger.
	 * @param Options\Provider $optionsProvider Options provider.
	 * @param DbRepository     $orderRepository Order repository.
	 */
	public function __construct(
		Api\Soap\Client $apiSoapClient,
		Log\ILogger $logger,
		Options\Provider $optionsProvider,
		DbRepository $orderRepository
	) {
		$this->apiSoapClient   = $apiSoapClient;
		$this->logger          = $logger;
		$this->optionsProvider = $optionsProvider;
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Synchronizes packets.
	 *
	 * @return void
	 */
	public function syncStatuses(): void {
		$args = [
			'limit'                => $this->optionsProvider->getMaxStatusSyncingPackets(),
			'paginate'             => false,
			'order'                => 'ASC',
			'orderby'              => 'date',
			'return'               => 'objects',
			'packetery_meta_query' => [
				'relation' => 'AND',
				[
					'key'     => Entity::META_PACKET_ID,
					'compare' => 'EXISTS',
				],
				[
					'key'     => Entity::META_PACKET_ID,
					'value'   => '',
					'compare' => '!=',
				],
				[
					'relation' => 'OR',
					[
						'key'     => Entity::META_PACKET_STATUS,
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => Entity::META_PACKET_STATUS,
						'value'   => [ 'delivered', 'returned', 'cancelled' ],
						'compare' => 'NOT IN',
					],
				],
			],
		];

		$results = wc_get_orders( $args );

		foreach ( $results as $wcOrder ) {
			$moduleOrder = new Entity( $wcOrder, $this->orderRepository );
			$packetId    = $moduleOrder->getPacketId();

			$request  = new Api\Soap\Request\PacketStatus( (int) $packetId );
			$response = $this->apiSoapClient->packetStatus( $request );

			if ( $response->hasFault() ) {
				$record         = new Log\Record();
				$record->action = Log\Record::ACTION_PACKET_STATUS_SYNC;
				$record->status = Log\Record::STATUS_ERROR;
				$record->title  = __( 'packetStatusSyncErrorLogTitle', 'packetery' );
				$record->params = [
					'orderId'      => $wcOrder->get_id(),
					'packetId'     => $request->getPacketId(),
					'errorMessage' => $response->getFaultString(),
				];
				$this->logger->add( $record );

				if ( $response->hasWrongPassword() ) {
					break;
				}

				continue;
			}

			if ( $response->getCodeText() === $moduleOrder->getPacketStatus() ) {
				continue;
			}

			$this->orderRepository->update( [ Entity::META_PACKET_STATUS => $response->getCodeText() ], $wcOrder->get_id() );
		}
	}
}
