<?php
/**
 * Class Translator.
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );


namespace Packetery\Module;

/**
 * Class Translator.
 *
 * @package Packetery\Module
 */
class Translator implements \PacketeryNette\Localization\Translator {

	/**
	 * Translates message.
	 *
	 * @param mixed $message       Message.
	 * @param mixed ...$parameters Parameters.
	 *
	 * @return string
	 */
	public function translate( $message, ...$parameters ): string {
		switch ( $message ) {
			case 'yes':
				return __( 'Yes', 'packetery' );
			case 'no':
				return __( 'No', 'packetery' );
			case 'cancelPacket':
				return __( 'Cancel packet', 'packetery' );
			case 'reallyCancelPacket':
				return __( 'Do you really want to cancel order submission to Packeta?', 'packetery' );
			case 'forcePacketCancelDescription':
				return __( 'Force order submission cancellation if Packeta API does not allow packet to be cancelled due incorrect packet status? Packet is likely consigned in such case.', 'packetery' );
		}

		return $message;
	}
}
