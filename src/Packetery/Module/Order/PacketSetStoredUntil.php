<?php
/**
 * Class PacketSetStoredUntil
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap;
use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\Order;
use Packetery\Core\Log;

/**
 * Class PacketSetStoredUntil
 *
 * @package Packetery
 */
class PacketSetStoredUntil {

	/**
	 * SOAP API Client.
	 *
	 * @var Soap\Client SOAP API Client.
	 */
	private $soapApiClient;

	/**
	 * ILogger.
	 *
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * CoreHelper.
	 *
	 * @var CoreHelper
	 */
	private $coreHelper;

	/**
	 * Constructor.
	 *
	 * @param Soap\Client $soapApiClient Soap client API.
	 * @param Log\ILogger $logger        Logger.
	 * @param CoreHelper  $coreHelper    CoreHelper.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		CoreHelper $coreHelper
	) {
		$this->soapApiClient = $soapApiClient;
		$this->logger        = $logger;
		$this->coreHelper    = $coreHelper;
	}

	/**
	 * Set stored until for packet.
	 *
	 * @param Order              $order Order ID.
	 * @param string|null        $packetId Packet ID.
	 * @param \DateTimeImmutable $storedUntil Stored until.
	 *
	 * @return null|string
	 */
	public function setStoredUntil( Order $order, ?string $packetId, \DateTimeImmutable $storedUntil ): ?string {
		$request      = new Soap\Request\PacketSetStoredUntil( $packetId, $storedUntil, $this->coreHelper );
		$result       = $this->soapApiClient->packetSetStoredUntil( $request );
		$errorMessage = null;

		if ( ! $result->hasFault() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_STORED_UNTIL_CHANGE;
			$record->status  = Log\Record::STATUS_SUCCESS;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet set stored until success', 'packeta' );
			$record->params  = [
				'orderId'  => $order->getNumber(),
				'packetId' => $packetId,
			];

			$this->logger->add( $record );
		}

		if ( $result->hasFault() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_STORED_UNTIL_CHANGE;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet set stored until error', 'packeta' );
			$record->params  = [
				'orderId'      => $order->getNumber(),
				'packetId'     => $packetId,
				'errorMessage' => $result->getFaultString(),
			];

			$this->logger->add( $record );
			$errorMessage = $result->getFaultString();
		}

		$order->updateApiErrorMessage( $errorMessage );

		return $errorMessage;
	}
}
