<?php

declare( strict_types=1 );

namespace Tests\Module\Options\FlagManager;

use DateTimeImmutable;
use DateTimeZone;
use Packetery\Core\CoreHelper;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\FlagManager\FeatureFlagDownloader;
use Packetery\Module\Options\FlagManager\FeatureFlagStorage;
use Packetery\Module\Options\OptionsProvider;
use PHPUnit\Framework\TestCase;
use Tests\Module\MockFactory;

class FeatureFlagDownloaderTest extends TestCase {

	public static function getFlagsProvider(): array {
		$now          = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$nowFormatted = $now->format( CoreHelper::MYSQL_DATETIME_FORMAT );

		$fresh          = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$fresh          = $fresh->modify( '-2 hours' );
		$freshFormatted = $fresh->format( CoreHelper::MYSQL_DATETIME_FORMAT );

		$old          = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$old          = $old->modify( '-8 hours' );
		$oldFormatted = $old->format( CoreHelper::MYSQL_DATETIME_FORMAT );

		$freshData       = [
			'splitActive'  => false,
			'lastDownload' => $nowFormatted,
		];
		$freshEnoughData = [
			'splitActive'  => false,
			'lastDownload' => $freshFormatted,
		];
		$oldData         = [
			'splitActive'  => false,
			'lastDownload' => $oldFormatted,
		];

		return [
			'fills storage from wp options when storage empty' => [
				'apiKey'              => 'dummy-api-key',
				'errors'              => false,
				'getFromStorageCount' => 3,
				'dataInStorage'       => [ null, $freshEnoughData, $freshEnoughData ],
				'dataInOptions'       => $freshEnoughData,
				'dataFromApi'         => [
					'split' => false,
				],
				'expectedResult'      => $freshEnoughData,
			],
			'returns from storage in case of errors'           => [
				'apiKey'              => 'dummy-api-key',
				'errors'              => true,
				'getFromStorageCount' => 3,
				'dataInStorage'       => [ null, $oldData, $oldData ],
				'dataInOptions'       => $oldData,
				'dataFromApi'         => [
					'split' => false,
				],
				'expectedResult'      => $oldData,
			],
			'returns empty in case of no api key'              => [
				'apiKey'              => null,
				'errors'              => false,
				'getFromStorageCount' => 3,
				'dataInStorage'       => [ null, null, [] ],
				'dataInOptions'       => false,
				'dataFromApi'         => [],
				'expectedResult'      => [],
			],
			'downloads when storage empty'                     => [
				'apiKey'              => 'dummy-api-key',
				'errors'              => false,
				'getFromStorageCount' => 3,
				'dataInStorage'       => [ null, null, $freshData ],
				'dataInOptions'       => false,
				'dataFromApi'         => [
					'split' => false,
				],
				'expectedResult'      => $freshData,
			],
			'downloads when storage holds old data'            => [
				'apiKey'              => 'dummy-api-key',
				'errors'              => false,
				'getFromStorageCount' => 3,
				'dataInStorage'       => [ null, $oldData, $freshData ],
				'dataInOptions'       => $oldData,
				'dataFromApi'         => [
					'split' => false,
				],
				'expectedResult'      => $freshData,
			],
		];
	}

	/**
	 * @dataProvider getFlagsProvider
	 */
	public function testGetFlags(
		?string $apiKey,
		bool $errors,
		int $getFromStorageCount,
		array $dataInStorage,
		array|false $dataInOptions,
		array $dataFromApi,
		array $expectedResult,
	): void {
		$optionsProvider = $this->createMock( OptionsProvider::class );
		$optionsProvider
			->method( 'get_api_key' )
			->willReturn( $apiKey );

		$wpAdapter = MockFactory::createWpAdapter( $this );
		$wpAdapter
			->method( 'getOption' )
			->willReturnCallback( function ( $option ) use ( $dataInOptions, $errors ) {
				if ( $option === FeatureFlagDownloader::FLAGS_OPTION_ID ) {
					return $dataInOptions;
				}
				if ( $option === FeatureFlagDownloader::DISABLED_DUE_ERRORS_OPTION_ID ) {
					return $errors;
				}
				self::fail( 'unexpected option: ' . $option );
			} );
		$wpAdapter
			->method( 'remoteRetrieveBody' )
			->willReturn( json_encode( [
				'features' => $dataFromApi,
			] ) );

		$wcAdapter = $this->createMock( WcAdapter::class );

		$storageMock = $this->createMock( FeatureFlagStorage::class );
		$storageMock
			->expects( $this->exactly( $getFromStorageCount ) )
			->method( 'getFlags' )
			->willReturnOnConsecutiveCalls( ...$dataInStorage );

		$downloader = new FeatureFlagDownloader(
			'dummy-url',
			$optionsProvider,
			$wpAdapter,
			$wcAdapter,
			$storageMock,
		);

		self::assertEquals( $expectedResult, $downloader->getFlags() );
	}

}
