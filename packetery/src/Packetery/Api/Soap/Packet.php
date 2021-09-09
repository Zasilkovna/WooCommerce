<?php
/**
 * Class Packet.
 *
 * @package Packetery\Api\Soap
 */

namespace Packetery\Api\Soap;

use SoapClient;
use SoapFault;
use stdClass;

/**
 * Class Packet.
 *
 * @package Packetery\Api\Soap
 */
class Packet extends Base {

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param string $apiPassword API password.
	 * @param array  $attributes Packet attributes.
	 *
	 * @return stdClass
	 */
	public function createPacket( string $apiPassword, array $attributes ):stdClass {
		$result = new stdClass();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );

			$packet          = $soapClient->createPacket( $apiPassword, $attributes );
			$result->barcode = $packet->barcode;
		} catch ( SoapFault $exception ) {
			$result->errors = $this->getSoapFaultErrors( $exception );
		}

		return $result;
	}
}
