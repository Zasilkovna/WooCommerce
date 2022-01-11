<?php
/**
 * Class PacketeryLatte_Engine_Factory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use PacketeryLatte\Engine;
use PacketeryLatte\MacroNode;
use PacketeryLatte\Macros\MacroSet;
use PacketeryLatte\PhpWriter;
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
	public function create( string $temp_dir ): Engine {
		$engine = new Engine();
		$engine->setTempDirectory( $temp_dir );
		FormMacros::install( $engine->getCompiler() );
		$engine->addFilter(
			'wpDateTime',
			function ( \DateTimeInterface $value ) {
				return $value->format( wc_date_format() . ' ' . wc_time_format() );
			}
		);

		$macroSet = new MacroSet( $engine->getCompiler() );
		$macroSet->addMacro(
			'phpComment',
			function ( MacroNode $node, PhpWriter $writer ) {
				$output = trim( $node->args, "'" );
				return $writer->write( '/* ' . $output . ' */' );
			}
		);

		$engine->addMacro( 'packetery-macro-set', $macroSet );
		return $engine;
	}
}
