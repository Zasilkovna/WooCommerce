<?php
/**
 * Class PacketeryLatte_Engine_Factory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Latte\Engine;
use Packetery\Nette\Bridges\FormsLatte\FormMacros;

/**
 * Class PacketeryLatte_Engine_Factory
 *
 * @package Packetery
 */
class LatteEngineFactory {
	/**
	 * Creates latte engine factory
	 *
	 * @param string $tempDir Temporary folder.
	 *
	 * @return Engine
	 */
	public function create( string $tempDir ): Engine {
		$engine = new Engine();
		$engine->setTempDirectory( $tempDir );
		FormMacros::install( $engine->getCompiler() );
		$engine->addFilter(
			'wpDateTime',
			function ( \DateTimeInterface $value ) {
				return $value->format( wc_date_format() . ' ' . wc_time_format() );
			}
		);

		return $engine;
	}
}
