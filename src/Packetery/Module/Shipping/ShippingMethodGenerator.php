<?php
/**
 * Class ShippingMethodGenerator.
 *
 * @package Packetery
 */

namespace Packetery\Module\Shipping;

use Packetery\Module\Carrier\EntityRepository;
use Packetery\Nette\PhpGenerator\PhpFile;

/**
 * Class ShippingMethodGenerator.
 *
 * @package Packetery
 */
class ShippingMethodGenerator {
	private const TARGET_NAMESPACE = 'Packetery\Module\Shipping\Generated';

	/**
	 * Entity repository.
	 *
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Constructor.
	 *
	 * @param EntityRepository $carrierRepository Carrier repository.
	 */
	public function __construct( EntityRepository $carrierRepository ) {
		$this->carrierRepository = $carrierRepository;
	}

	/**
	 * Generates classes for all carriers in feed.
	 *
	 * @return void
	 */
	public function generateClasses(): void {
		$allCarriers = $this->carrierRepository->getAllCarriersIncludingNonFeed();
		if ( ! $allCarriers ) {
			return;
		}

		foreach ( $allCarriers as $carrier ) {
			if ( ! class_exists( self::TARGET_NAMESPACE . '\\' . $this->getClassnameFromCarrierId( $carrier->getId() ) ) ) {
				$this->generateClass( $carrier->getId(), $carrier->getName() );
			}
		}
	}

	/**
	 * See https://doc.nette.org/en/php-generator
	 *
	 * @param string $carrierId   Carrier id.
	 * @param string $carrierName Carrier name.
	 *
	 * @return void
	 */
	private function generateClass( string $carrierId, string $carrierName ): void {
		$file = new PhpFile();
		$file->addComment( 'This file is auto-generated.' );
		$file->setStrictTypes();

		$namespace = $file->addNamespace( self::TARGET_NAMESPACE );
		$namespace->addUse( BaseShippingMethod::class );

		$className = $this->getClassnameFromCarrierId( $carrierId );
		$class     = $namespace->addClass( $className );
		$class->setExtends( BaseShippingMethod::class )->addComment( $carrierName );

		// Add setType later.
		$class->addConstant( 'CARRIER_ID', $carrierId )->setPublic();

		$filePath = __DIR__ . '/Generated/' . $className . '.php';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents( $filePath, $file );
	}

	/**
	 * Returns carrier shipping method classname.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return string
	 */
	private function getClassnameFromCarrierId( string $carrierId ): string {
		return 'ShippingMethod_' . $carrierId;
	}

}
