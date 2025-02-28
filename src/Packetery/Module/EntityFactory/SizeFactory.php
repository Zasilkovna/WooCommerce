<?php
/**
 * Class SizeFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\EntityFactory;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;

class SizeFactory {
	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	public function __construct( OptionsProvider $optionsProvider ) {
		$this->optionsProvider = $optionsProvider;
	}

	public function createSizeInSetDimensionUnit( Entity\Order $order ): Entity\Size {
		$dimensions = [
			$order->getLength(),
			$order->getWidth(),
			$order->getHeight(),
		];

		$size = [];
		foreach ( $dimensions as $dimension ) {
			$size[] = $dimension !== null && $this->optionsProvider->getDimensionsUnit() === OptionsProvider::DIMENSIONS_UNIT_CM
				? CoreHelper::simplifyFloat(
					ModuleHelper::convertToCentimeters( (int) $dimension ),
					$this->optionsProvider->getDimensionsNumberOfDecimals()
				)
				: $dimension;
		}

		return new Entity\Size( ...$size );
	}

	public function createDefaultSizeForNewOrder(): Entity\Size {
		$length = $this->optionsProvider->getDimensionsUnit() === OptionsProvider::DIMENSIONS_UNIT_CM ? ModuleHelper::convertToMillimeters( $this->optionsProvider->getDefaultLength() ) : $this->optionsProvider->getDefaultLength();
		$width  = $this->optionsProvider->getDimensionsUnit() === OptionsProvider::DIMENSIONS_UNIT_CM ? ModuleHelper::convertToMillimeters( $this->optionsProvider->getDefaultWidth() ) : $this->optionsProvider->getDefaultWidth();
		$height = $this->optionsProvider->getDimensionsUnit() === OptionsProvider::DIMENSIONS_UNIT_CM ? ModuleHelper::convertToMillimeters( $this->optionsProvider->getDefaultHeight() ) : $this->optionsProvider->getDefaultHeight();

		return new Entity\Size( $length, $width, $height );
	}
}
