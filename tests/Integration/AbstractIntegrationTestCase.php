<?php

declare( strict_types=1 );

namespace Tests\Integration;

use Packetery\Nette\DI\Container;
use PHPUnit\Framework\TestCase;

abstract class AbstractIntegrationTestCase extends TestCase {

	protected Container $container;

	public function __construct( string $name ) {
		parent::__construct( $name );

		$this->container = IntegrationTestsHelper::getContainer();
	}
}
