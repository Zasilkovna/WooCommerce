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

	protected const WSDL_URL = 'http://www.zasilkovna.cz/api/soap.wsdl';

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param string              $apiPassword API password.
	 * @param CreatePacketRequest $request Packet attributes.
	 *
	 * @return CreatePacketResponse
	 */
	public function createPacket( string $apiPassword, CreatePacketRequest $request ): CreatePacketResponse {
		// todo 288 password do property? factorka v neonu?
		$response = new CreatePacketResponse();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$packet = $soapClient->createPacket( $apiPassword, $request->getAsArray() );
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
	 * @return string
	 */
	protected function getSoapFaultErrors( SoapFault $exception ): string {
		$errors = '';

		if ( isset( $exception->detail->PacketAttributesFault->attributes->fault ) ) {
			if ( is_array( $exception->detail->PacketAttributesFault->attributes->fault ) ) {
				foreach ( $exception->detail->PacketAttributesFault->attributes->fault as $fault ) {
					$errors .= sprintf( '%s: %s ', $fault->name, $fault->fault );
				}
			} else {
				$fault   = $exception->detail->PacketAttributesFault->attributes->fault;
				$errors .= sprintf( '%s: %s ', $fault->name, $fault->fault );
			}
		}

		if ( '' === $errors ) {
			$errors = $exception->faultstring;
		}

		// TODO: update before release.
		$logger = wc_get_logger();
		$logger->error( $errors );

		return $errors;
	}
}
