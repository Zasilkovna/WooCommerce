<?php
/**
 * Class PacketeryLatte_Engine_Factory
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery;

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
	 * @return \PacketeryLatte\Engine
	 */
	public function create( string $temp_dir ) {
		$engine = new \PacketeryLatte\Engine();
		$engine->setTempDirectory( $temp_dir );
		\PacketeryNette\Bridges\FormsPacketeryLatte\FormMacros::install( $engine->getCompiler() );
		return $engine;
	}
}
