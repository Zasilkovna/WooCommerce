<?php
/**
 * Class ReturnService.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Returns;

use Packetery\Core\Api\Soap;
use Packetery\Core\CoreHelper;
use Packetery\Core\Entity;
use Packetery\Core\Entity\PacketReturn;
use Packetery\Core\Log;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Order\Repository as OrderRepository;

/**
 * Creates a return (claim) via the API and persists it. Source-agnostic so both the admin flow and
 * the front-office flow can call it; presentation (flash/redirect) is left to the caller.
 */
class ReturnService {

	private Soap\Client $soapApiClient;
	private Log\ILogger $logger;
	private OrderRepository $orderRepository;
	private Repository $returnRepository;
	private CoreHelper $coreHelper;
	private ModuleHelper $moduleHelper;
	private WpAdapter $wpAdapter;

	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		OrderRepository $orderRepository,
		Repository $returnRepository,
		CoreHelper $coreHelper,
		ModuleHelper $moduleHelper,
		WpAdapter $wpAdapter
	) {
		$this->soapApiClient    = $soapApiClient;
		$this->logger           = $logger;
		$this->orderRepository  = $orderRepository;
		$this->returnRepository = $returnRepository;
		$this->coreHelper       = $coreHelper;
		$this->moduleHelper     = $moduleHelper;
		$this->wpAdapter        = $wpAdapter;
	}

	/**
	 * Creates a return for the given order via the API, persists it and logs the outcome.
	 *
	 * @param Entity\Order $order  Order to create the return for.
	 * @param string       $source Who initiated the return, one of PacketReturn::SOURCE_*.
	 */
	public function createReturn( Entity\Order $order, string $source ): ReturnCreationResult {
		$request  = new Soap\Request\CreatePacketClaimWithPassword( $order );
		$response = $this->soapApiClient->createPacketClaimWithPassword( $request );

		$record          = new Log\Record();
		$record->action  = Log\Record::ACTION_PACKET_CLAIM_SENDING;
		$record->orderId = $order->getNumber();

		if ( $response->hasFault() ) {
			$record->status = Log\Record::STATUS_ERROR;
			$record->title  = $this->wpAdapter->__( 'Packet claim could not be created.', 'packeta' );
			$record->params = [
				'request'      => $request->getSubmittableData(),
				'errorMessage' => $response->getFaultString(),
				'errors'       => $response->getValidationErrors(),
			];
			$this->logger->add( $record );

			return new ReturnCreationResult( $response, false, null );
		}

		$record->status = Log\Record::STATUS_SUCCESS;
		$record->title  = $this->wpAdapter->__( 'Packet claim was successfully created.', 'packeta' );
		$record->params = [
			'request'  => $request->getSubmittableData(),
			'packetId' => $response->getId(),
		];
		$this->logger->add( $record );

		$order->setPacketClaimId( $response->getId() );
		$order->setPacketClaimTrackingUrl( $this->coreHelper->getTrackingUrl( $response->getId() ) );
		$order->setPacketClaimPassword( $response->getPassword() );
		$orderSaved = $this->orderRepository->save( $order ) !== false;

		$packetReturn = new PacketReturn(
			(string) $order->getNumber(),
			PacketReturn::STATUS_CREATED,
			$source,
			CoreHelper::now()
		);
		$packetReturn->setPacketClaimId( $response->getId() );
		$packetReturn->setPacketClaimPassword( $response->getPassword() );
		$packetReturn->setEmail( $order->getEmail() );
		$packetReturn->setPhone( $order->getPhone() );
		$this->returnRepository->save( $packetReturn );

		$this->addCreatedOrderNote( $order );

		return new ReturnCreationResult( $response, $orderSaved, $packetReturn );
	}

	private function addCreatedOrderNote( Entity\Order $order ): void {
		$wcOrder = $this->orderRepository->getWcOrderById( (int) $order->getNumber() );
		if ( $wcOrder === null ) {
			return;
		}

		$wcOrder->add_order_note(
			sprintf(
			// translators: %s represents a packet tracking link.
				$this->wpAdapter->__( 'Packeta: Packet claim %s has been created', 'packeta' ),
				$this->moduleHelper->createHtmlLink(
					(string) $order->getPacketClaimTrackingUrl(),
					(string) $order->getPacketClaimBarcode()
				)
			)
		);
		$wcOrder->save();
	}
}
