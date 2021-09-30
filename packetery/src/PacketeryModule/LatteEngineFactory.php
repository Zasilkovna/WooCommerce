<?php
/**
 * Class PacketeryLatte_Engine_Factory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace PacketeryModule;

use PacketeryLatte\Engine;
use PacketeryNette\Bridges\FormsPacketeryLatte\FormMacros;

/**
 * Class PacketeryLatte_Engine_Factory
 *
 * @package Packetery
 */
class LatteEngineFactory {

	/**
	 * Creates latte engine factory
	 *
	 * @param string $temp_dir Temporary folder.
	 *
	 * @return Engine
	 */
	public function create( string $temp_dir ) {
		$engine = new Engine();
		$engine->setTempDirectory( $temp_dir );
		FormMacros::install( $engine->getCompiler() );
		$engine->addFilter( 'wp_datetime', function ( \DateTimeImmutable $value ) {
			return $value->format( wc_date_format() . ' ' . wc_time_format() );
		} );
		return $engine;
	}
}
