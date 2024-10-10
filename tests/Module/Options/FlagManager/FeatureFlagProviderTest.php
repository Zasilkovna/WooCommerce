<?php

declare( strict_types=1 );

namespace Tests\Module\Options\FlagManager;

use Packetery\Module\Options\FlagManager\FeatureFlagDownloader;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use PHPUnit\Framework\TestCase;
use Tests\Module\MockFactory;

class FeatureFlagProviderTest extends TestCase {

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
		$wpAdapter = MockFactory::createWpAdapter( $this );
		$wpAdapter
			->method( 'getTransient' )
			->willReturn( $messageDismissed );

		$downloader = $this->createMock( FeatureFlagDownloader::class );
		$downloader
			->method( 'getFlags' )
			->willReturn( [
				FeatureFlagProvider::FLAG_SPLIT_ACTIVE => $isSplitActive,
			] );

		$manager = new FeatureFlagProvider(
			$wpAdapter,
			$downloader,
		);

		self::assertEquals( $expectedResult, $manager->shouldShowSplitActivationNotice() );
	}

}
