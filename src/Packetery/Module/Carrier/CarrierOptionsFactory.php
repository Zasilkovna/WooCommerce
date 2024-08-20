<?php
/**
 * CarrierOptionsFactory class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Module\Framework\WpAdapter;

/**
 * CarrierOptionsFactory class.
 */
class CarrierOptionsFactory {

	/**
	 * WP adapter.
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Constructor.
	 *
	 * @param WpAdapter $wpAdapter WP adapter.
	 */
	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * Creates carrier options by option id.
	 *
	 * @param string $optionId Option id.
	 *
	 * @return Options
	 */
	public function createByOptionId( string $optionId ): Options {
		$options = $this->wpAdapter->getOption( $optionId );
		if ( empty( $options ) ) {
			$options = [];
		}

		return new Options( $optionId, $options );
	}

	/**
	 * Creates instance by carrier ID.
	 *
	 * @param string $carrierId Carrier ID.
	 *
	 * @return Options
	 */
	public function createByCarrierId( string $carrierId ): Options {
		$optionId = OptionPrefixer::getOptionId( $carrierId );

		return $this->createByOptionId( $optionId );
	}

}
