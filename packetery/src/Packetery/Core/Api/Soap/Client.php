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
			$response->setBarcode( $packet->barcode );
		} catch ( SoapFault $exception ) {
			$response->setFaultString( $exception->faultstring );
			$response->setValidationErrors( $this->getValidationErrors( $exception ) );
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
