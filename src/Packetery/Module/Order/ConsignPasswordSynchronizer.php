<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap;
use Packetery\Core\Entity;
use Packetery\Core\Log;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;

class ConsignPasswordSynchronizer {

	private const HOOK_CONSIGN_PASSWORD_FETCH = 'packetery_consign_password_fetch_hook';

	/**
	 * @var Soap\Client
	 */
	private $soapApiClient;

	/**
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		Repository $orderRepository,
		OptionsProvider $optionsProvider,
		WcAdapter $wcAdapter
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->optionsProvider = $optionsProvider;
		$this->wcAdapter       = $wcAdapter;
	}

	public function register(): void {
		add_action(
			self::HOOK_CONSIGN_PASSWORD_FETCH,
			function ( $orderId ): void {
				$this->processById( (string) $orderId );
			},
			10,
			1
		);
	}

	public function schedule( Entity\Order $order, bool $immediatePacketStatusCheck ): void {
		if ( ! $this->optionsProvider->isShowConsignPasswordForZBoxEnabled() ) {
			return;
		}
		if ( $order->getPacketId() === null ) {
			return;
		}

		$orderNumber = $order->getNumber();
		if ( $orderNumber === null || $orderNumber === '' ) {
			return;
		}

		if ( $immediatePacketStatusCheck || ! $this->wcAdapter->isActionSchedulerEnqueueAvailable() ) {
			$this->processById( $orderNumber );

			return;
		}

		$this->wcAdapter->asEnqueueAsyncAction( self::HOOK_CONSIGN_PASSWORD_FETCH, [ $orderNumber ] );
	}

	public function processById( string $orderId ): void {
		if ( ! $this->optionsProvider->isShowConsignPasswordForZBoxEnabled() ) {
			return;
		}

		$order = $this->orderRepository->getByIdWithValidCarrier( (int) $orderId );
		if ( $order === null ) {
			return;
		}

		$packetId = $order->getPacketId();
		if ( $packetId === null ) {
			return;
		}

		$response = $this->soapApiClient->packetInfo( new Soap\Request\PacketInfo( $packetId ) );
		if ( $response->hasFault() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_INFO;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->title   = __( 'Packet info request failed', 'packeta' );
			$record->orderId = $orderId;
			$record->params  = [
				'request'  => [ 'packetId' => $packetId ],
				'response' => [
					'faultString' => $response->getFaultString(),
				],
			];
			$this->logger->add( $record );

			return;
		}

		$password = $response->getConsignPassword();
		if ( $password === null || $password === '' ) {
			return;
		}

		$order->setConsignPassword( $password );
		$this->orderRepository->save( $order );
	}
}
