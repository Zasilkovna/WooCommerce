<?php
/**
 * Class Packet.
 *
 * @package Packetery\Api\Soap
 */

namespace Packetery\Api\Soap;

use Packetery\Api\Soap\Request\CreatePacket as CreatePacketRequest;
use Packetery\Api\Soap\Response\CreatePacket as CreatePacketResponse;
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
	 * @var string
	 */
	private $apiPassword;

	/**
	 * Client constructor.
	 *
	 * @param string $apiPassword Api password.
	 */
	public function __construct( string $apiPassword ) {
		$this->apiPassword = $apiPassword;
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param CreatePacketRequest $request Packet attributes.
	 *
	 * @return CreatePacketResponse
	 */
	public function createPacket( CreatePacketRequest $request ): CreatePacketResponse {
		$response = new CreatePacketResponse();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$packet     = $soapClient->createPacket( $this->apiPassword, $request->getSubmittableData() );
			$response->setBarcode( $packet->barcode );
		} catch ( SoapFault $exception ) {
			$response->setErrors( $this->getSoapFaultErrors( $exception ) );
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
	protected function getSoapFaultErrors( SoapFault $exception ): array {
		$errors   = [];
		$errors[] = $exception->faultstring;

		if ( ! empty( $exception->detail->PacketAttributesFault->attributes->fault ) ) {
			$faults = $exception->detail->PacketAttributesFault->attributes->fault ?? [];
			if ( ! is_array( $faults ) ) {
				$faults = [ $faults ];
			}
			foreach ( $faults as $fault ) {
				$errors[] = sprintf( '%s: %s', $fault->name, $fault->fault );
			}
		}

		return $errors;
	}
}
