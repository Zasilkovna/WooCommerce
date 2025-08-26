<?php

declare( strict_types=1 );

namespace Packetery\Module\Labels;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Request;
use Packetery\Core\Api\Soap\Response;
use Packetery\Core\Log;
use Packetery\Core\Log\ILogger;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\Order\Repository;

class CarrierLabelService {

	/**
	 * @var Client
	 */
	private $soapApiClient;

	/**
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		Client $soapApiClient,
		MessageManager $messageManager,
		ILogger $logger,
		Repository $orderRepository,
		WpAdapter $wpAdapter
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->messageManager  = $messageManager;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->wpAdapter       = $wpAdapter;
	}

	/**
	 * Gets carrier packet numbers from API.
	 *
	 * @param string[] $packetIds
	 *
	 * @return array<int, array{packetId: string, courierNumber: string}>
	 */
	public function getPacketIdsWithCourierNumbers( array $packetIds ): array {
		$pairs = [];
		foreach ( $packetIds as $orderId => $packetId ) {
			$existingCarrierNumber = $this->getExistingCarrierNumber( (int) $orderId, $packetId );
			if ( $existingCarrierNumber !== null ) {
				$pairs[ $orderId ] = $existingCarrierNumber;

				continue;
			}

			$request  = new Request\PacketCourierNumber( $packetId );
			$response = $this->soapApiClient->packetCourierNumber( $request );
			if ( $response->hasFault() ) {
				$continueProcessing = $this->handleApiError( $request, $response, (int) $orderId, $packetId );
				if ( $continueProcessing === false ) {
					return [];
				}

				continue;
			}

			$pairs[ $orderId ] = $this->handleApiSuccess( $response, (int) $orderId, $packetId );
		}

		return $pairs;
	}

	/**
	 * @return array{packetId: string, courierNumber: string}|null
	 */
	private function getExistingCarrierNumber( int $orderId, string $packetId ): ?array {
		$order = $this->orderRepository->getByIdWithValidCarrier( $orderId );
		if ( $order !== null && $order->getCarrierNumber() !== null ) {
			return [
				'packetId'      => $packetId,
				'courierNumber' => $order->getCarrierNumber(),
			];
		}

		return null;
	}

	private function handleApiError( Request\PacketCourierNumber $request, Response\PacketCourierNumber $response, int $orderId, string $packetId ): bool {
		if ( $response->hasWrongPassword() ) {
			$this->messageManager->flash_message(
				$this->wpAdapter->__( 'Please set a proper API password.', 'packeta' ),
				MessageManager::TYPE_ERROR
			);

			return false;
		}

		$this->logError( $request, $response, $orderId );

		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		$order   = null;
		if ( $wcOrder !== null ) {
			$order = $this->orderRepository->getByWcOrderWithValidCarrier( $wcOrder );
		}

		if ( $order !== null ) {
			$order->updateApiErrorMessage( $response->getFaultString() );
			$this->orderRepository->save( $order );
		}
		if ( $wcOrder !== null ) {
			$wcOrder->add_order_note(
				sprintf(
				// translators: %s represents shipment id
					$this->wpAdapter->__( 'Packeta: Unable to obtain carrier tracking number for shipment Z%s.', 'packeta' ),
					$packetId
				)
			);
			$wcOrder->save();
		}

		return true;
	}

	private function logError( Request\PacketCourierNumber $request, Response\PacketCourierNumber $response, int $orderId ): void {
		$record          = new Log\Record();
		$record->action  = Log\Record::ACTION_CARRIER_NUMBER_RETRIEVING;
		$record->status  = Log\Record::STATUS_ERROR;
		$record->title   = __( 'Carrier number could not be retrieved.', 'packeta' );
		$record->params  = [
			'packetId'     => $request->getPacketId(),
			'errorMessage' => $response->getFaultString(),
		];
		$record->orderId = $orderId;
		$this->logger->add( $record );
	}

	/**
	 * @return array{packetId: string, courierNumber: string}
	 */
	private function handleApiSuccess( Response\PacketCourierNumber $response, int $orderId, string $packetId ): array {
		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		$order   = null;
		if ( $wcOrder !== null ) {
			$order = $this->orderRepository->getByWcOrderWithValidCarrier( $wcOrder );
		}

		if ( $order !== null ) {
			$order->setCarrierNumber( $response->getNumber() );
			$this->orderRepository->save( $order );
		}
		if ( $wcOrder !== null ) {
			$wcOrder->add_order_note(
				sprintf(
				// translators: %1$s represents shipment id and %2$s is carrier tracking number
					$this->wpAdapter->__( 'Packeta: Shipment Z%1$s was assigned carrier tracking number: %2$s.', 'packeta' ),
					$packetId,
					$response->getNumber()
				)
			);
			$wcOrder->save();
		}

		return [
			'packetId'      => $packetId,
			'courierNumber' => $response->getNumber(),
		];
	}
}
