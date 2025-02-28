<?php
/**
 * SenderGetReturnRouting
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

/**
 * SenderGetReturnRouting
 */
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
		if ( $this->fault === 'SenderNotExists' ) {
			return false;
		}

		return null;
	}
}
