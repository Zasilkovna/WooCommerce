<?php

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Response;

class SenderGetReturnRouting extends BaseResponse {

	/**
	 * Checks if sender exists.
	 *
	 * @return bool|null
	 */
	public function senderExists(): ?bool {
		if ( $this->fault === null ) {
			return true;
		}
		if ( 'SenderNotExists' === $this->fault ) {
			return false;
		}

		return null;
	}
}
