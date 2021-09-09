<?php
/**
 * Class Base
 *
 * @package Packetery\Api\Soap
 */

namespace Packetery\Api\Soap;

use SoapFault;

/**
 * Class Base
 *
 * @package Packetery\Api\Soap
 */
class Base {

	protected const WSDL_URL = 'http://www.zasilkovna.cz/api/soap.wsdl';

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
			if ( is_array( $exception->detail->PacketAttributesFault->attributes->fault ) && count( $exception->detail->PacketAttributesFault->attributes->fault ) > 1 ) {
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
