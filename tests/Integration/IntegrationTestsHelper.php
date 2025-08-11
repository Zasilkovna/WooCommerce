<?php

declare( strict_types=1 );

namespace Tests\Integration;

use Packetery\Nette\DI;

class IntegrationTestsHelper {
	public static function getContainer(): DI\Container {
		return require __DIR__ . '/../../bootstrap-cli.php';
	}
}
