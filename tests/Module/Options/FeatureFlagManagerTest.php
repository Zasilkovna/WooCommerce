<?php

declare( strict_types=1 );

namespace Tests\Module\Options;

use Packetery\Latte\Engine;
use Packetery\Module;
use Packetery\Module\Options\FeatureFlagManager;
use Packetery\Module\Options\Provider;
use PHPUnit\Framework\TestCase;
use Tests\Module\MockFactory;
use function json_encode;

class FeatureFlagManagerTest extends TestCase {

	public static function shouldShowSplitActivationNoticeDataProvider(): array {
		return [
			[
				'isSplitActive'    => true,
				'messageDismissed' => false,
				'expectedResult'   => true,
			],
			[
				'isSplitActive'    => true,
				'messageDismissed' => 'yes',
				'expectedResult'   => false,
			],
			[
				'isSplitActive'    => false,
				'messageDismissed' => 'yes',
				'expectedResult'   => false,
			],
			[
				'isSplitActive'    => false,
				'messageDismissed' => false,
				'expectedResult'   => false,
			],
		];
	}

	/**
	 * @dataProvider shouldShowSplitActivationNoticeDataProvider
	 */
	public function testShouldShowSplitActivationNotice(
		bool $isSplitActive,
		string|false $messageDismissed,
		bool $expectedResult,
	): void {
		$optionsProvider = $this->createMock( Provider::class );
		$optionsProvider
			->method( 'get_api_key' )
			->willReturn( 'dummy_api_key' );

		$wpAdapter = MockFactory::createWpAdapter( $this );
		$wpAdapter
			->method( 'getOption' )
			->willReturnMap( [
				[
					FeatureFlagManager::FLAGS_OPTION_ID,
					false,
					false,
				],
				[
					FeatureFlagManager::DISABLED_DUE_ERRORS_OPTION_ID,
					false,
					false,
				],
			] );

		$wpAdapter
			->method( 'remoteRetrieveBody' )
			->willReturn( json_encode( [
				'features' => [
					'split' => $isSplitActive,
				],
			] ) );
		$wpAdapter
			->method( 'getTransient' )
			->willReturn( $messageDismissed );

		FeatureFlagManager::resetCache();
		$manager = new FeatureFlagManager(
			$this->createMock( Engine::class ),
			$optionsProvider,
			$this->createMock( Module\Helper::class ),
			$wpAdapter,
		);

		self::assertEquals( $expectedResult, $manager->shouldShowSplitActivationNotice() );
	}

}
