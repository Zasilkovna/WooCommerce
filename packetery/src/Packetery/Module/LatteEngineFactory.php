<?php
/**
 * Class PacketeryLatte_Engine_Factory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

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
		$engine->addFilter(
			'wpDateTime',
			function ( \DateTimeInterface $value ) {
				return $value->format( wc_date_format() . ' ' . wc_time_format() );
			}
		);
		$engine->addFilter( 'tt', [ self::class, 'ttFilter' ] );
		return $engine;
	}

	/**
	 * Transforms translated text with named placeholders.
	 *
	 * @param string $translatedText Translated text.
	 * @param array  $variables      Values for placeholders.
	 *
	 * @return string
	 */
	public static function ttFilter( string $translatedText, array $variables ): string {
		$newVariables = [];
		foreach ( $variables as $variable => $varValue ) {
			$newVariables[ '{$' . $variable . '}' ] = $varValue;
		}

		return strtr( $translatedText, $newVariables );
	}
}
