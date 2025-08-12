<?php

namespace Tests\Module;

use Packetery\Module\WidgetUrlResolver;
use PHPUnit\Framework\TestCase;

class WidgetUrlResolverTest extends TestCase {
	public function testGetUrl(): void {
		$productionResolver = new WidgetUrlResolver( 'production' );
		$this->assertEquals( WidgetUrlResolver::WIDGET_URL_PRODUCTION, $productionResolver->getUrl() );

		$stageResolver = new WidgetUrlResolver( 'stage' );
		$this->assertEquals( WidgetUrlResolver::WIDGET_URL_STAGE, $stageResolver->getUrl() );

		$defaultResolver = new WidgetUrlResolver( 'any_other_value' );
		$this->assertEquals( WidgetUrlResolver::WIDGET_URL_PRODUCTION, $defaultResolver->getUrl() );
	}
}
