<?php
/**
 * Class PacketCanceller
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap;
use Packetery\Core\Entity\Order;
use Packetery\Core\Log;
use Packetery\Module\Helper;
use Packetery\Module\MessageManager;
use Packetery\Nette\Http\Request;

/**
 * Class PacketSetStoredUntil
 *
 * @package Packetery\Module\Order
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
	 * Constructor.
	 *
	 * @param Soap\Client    $soapApiClient   Soap client API.
	 * @param Log\ILogger    $logger          Logger.
	 * @param Repository     $orderRepository Order repository.
	 * @param Request        $request         Request.
	 * @param MessageManager $messageManager  Message manager.
	 * @param Helper         $helper          Helper.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		Repository $orderRepository,
		Request $request,
		MessageManager $messageManager,
		Helper $helper
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->request         = $request;
		$this->messageManager  = $messageManager;
		$this->helper          = $helper;
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
	public function setStoredUntil( Order $order, ?string $packetId, \DateTimeImmutable $storedUntil ): null|string {
		$request      = Soap\Request\PacketSetStoredUntil::create( $packetId, $storedUntil );
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
