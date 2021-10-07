<?php
/**
 * Class Packet.
 *
 * @package Packetery\Api\Soap
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap;

use Packetery\Core\Api\Soap\Request;
use Packetery\Core\Api\Soap\Response;
use SoapClient;
use SoapFault;

/**
 * Class Packet.
 *
 * @package Packetery\Api\Soap
 */
class Client {

	private const WSDL_URL = 'http://www.zasilkovna.cz/api/soap.wsdl';

	/**
	 * API password.
	 *
	 * @var string|null
	 */
	private $apiPassword;

	/**
	 * Client constructor.
	 *
	 * @param string|null $apiPassword Api password.
	 */
	public function __construct( ?string $apiPassword ) {
		$this->apiPassword = $apiPassword;
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param Request\CreatePacket $request Packet attributes.
	 *
	 * @return Response\CreatePacket
	 */
	public function createPacket( Request\CreatePacket $request ): Response\CreatePacket {
		$response = new Response\CreatePacket();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$packet     = $soapClient->createPacket( $this->apiPassword, $request->getSubmittableData() );
			$response->setId( $packet->id );
		} catch ( SoapFault $exception ) {
			$response->setFaultString( $exception->faultstring );
			$response->setValidationErrors( $this->getValidationErrors( $exception ) );
		}

		return $response;
	}

	/**
	 * Asks for packeta labels.
	 *
	 * @param Request\PacketsLabelsPdf $request Label request.
	 *
	 * @return Response\PacketsLabelsPdf
	 */
	public function packetsLabelsPdf( Request\PacketsLabelsPdf $request ): Response\PacketsLabelsPdf {
		$response = new Response\PacketsLabelsPdf();
		try {
			$soapClient  = new SoapClient( self::WSDL_URL );
			$pdfContents = $soapClient->packetsLabelsPdf( $this->apiPassword, $request->getPacketIds(), $request->getFormat(), $request->getOffset() );
			$response->setPdfContents( $pdfContents );
		} catch ( SoapFault $exception ) {
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Asks for carrier labels.
	 *
	 * @param Request\PacketsCourierLabelsPdf $request Label request.
	 *
	 * @return Response\PacketsCourierLabelsPdf
	 */
	public function packetsCarrierLabelsPdf( Request\PacketsCourierLabelsPdf $request ): Response\PacketsCourierLabelsPdf {
		$response = new Response\PacketsCourierLabelsPdf();
		try {
			$soapClient  = new SoapClient( self::WSDL_URL );
			$pdfContents = $soapClient->packetsCourierLabelsPdf( $this->apiPassword, $request->getPacketIds(), $request->getOffset(), $request->getFormat() );
			$response->setPdfContents( $pdfContents );
		} catch ( SoapFault $exception ) {
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Requests carrier number for a packet.
	 *
	 * @param Request\PacketCourierNumber $request PacketCourierNumber request.
	 *
	 * @return Response\PacketCourierNumber
	 */
	public function packetCourierNumber( Request\PacketCourierNumber $request ): Response\PacketCourierNumber {
		$response = new Response\PacketCourierNumber();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$number     = $soapClient->packetCourierNumber( $this->apiPassword, $request->getPacketId() );
			$response->setNumber( $number );
		} catch ( SoapFault $exception ) {
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Gets human readable errors form SoapFault exception.
	 *
	 * @param SoapFault $exception Exception.
	 *
	 * @return array
	 */
	protected function getValidationErrors( SoapFault $exception ): array {
		$errors = [];

		$faults = ( $exception->detail->PacketAttributesFault->attributes->fault ?? [] );
		if ( $faults && ! is_array( $faults ) ) {
			$faults = [ $faults ];
		}
		foreach ( $faults as $fault ) {
			$errors[] = sprintf( '%s: %s', $fault->name, $fault->fault );
		}

		return $errors;
	}
}
