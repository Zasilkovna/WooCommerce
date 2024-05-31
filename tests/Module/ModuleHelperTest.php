<?php

declare( strict_types=1 );

namespace Tests\Module;

use DateTimeImmutable;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use Packetery\Nette\IOException;
use PHPUnit\Framework\TestCase;

class ModuleHelperTest extends TestCase {
	public static function convertToCentimetersProvider(): array {
		return [
			[
				'input'    => 0,
				'expected' => null,
			],
			[
				'input'    => 1,
				'expected' => 0.1,
			],
			[
				'input'    => 10,
				'expected' => 1.0,
			],
			[
				'input'    => 100,
				'expected' => 10.0,
			],
			[
				'input'    => - 1,
				'expected' => null,
			],
			[
				'input'    => 1000,
				'expected' => 100.0,
			],
		];
	}

	/**
	 * @dataProvider convertToCentimetersProvider
	 */
	public function testConvertToCentimeters( int $input, ?float $expected ): void {
		$result = ModuleHelper::convertToCentimeters( $input );
		$this->assertSame( $expected, $result );
	}

	public static function convertToMillimetersProvider(): array {
		return [
			[
				'input'    => 0.0,
				'expected' => null,
			],
			[
				'input'    => 0.1,
				'expected' => 1.0,
			],
			[
				'input'    => 1.0,
				'expected' => 10.0,
			],
			[
				'input'    => 10.0,
				'expected' => 100.0,
			],
			[
				'input'    => - 0.1,
				'expected' => null,
			],
			[
				'input'    => 100.0,
				'expected' => 1000.0,
			],
		];
	}

	/**
	 * @dataProvider convertToMillimetersProvider
	 */
	public function testConvertToMillimeters( float $input, ?float $expected ): void {
		$result = ModuleHelper::convertToMillimeters( $input );
		$this->assertSame( $expected, $result );
	}

	public function testGetTranslatedStringFromDateTimeReturnsFormattedDate(): void {
		$moduleHelper = new ModuleHelper(
			$this->createMock( WpAdapter::class )
		);

		$dummyDateTimeImmutable = new DateTimeImmutable( '2024-03-10 15:30:00' );

		add_filter(
			'woocommerce_admin_order_date_format',
			function () {
				return 'M j, Y';
			}
		);

		$expectedTranslatedString = date_i18n( 'M j, Y', $dummyDateTimeImmutable->getTimestamp() );

		$this->assertEquals(
			$expectedTranslatedString,
			$moduleHelper->getTranslatedStringFromDateTime( $dummyDateTimeImmutable )
		);
	}

	public function testGetTranslatedStringFromDateTimeReturnsNullForNullInput(): void {
		$moduleHelper = new ModuleHelper(
			$this->createMock( WpAdapter::class )
		);

		$this->assertNull( $moduleHelper->getTranslatedStringFromDateTime( null ) );
	}

	public function testInstantDeleteRemovesDirectorySafely(): void {
		$tempDir  = sys_get_temp_dir() . '/packeta-test-' . uniqid();
		$testFile = $tempDir . '/test-file.txt';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		mkdir( $tempDir, 0755, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $testFile, 'test content' );

		$this->assertDirectoryExists( $tempDir );
		$this->assertFileExists( $testFile );

		ModuleHelper::instantDelete( $tempDir );

		$this->assertDirectoryDoesNotExist( $tempDir );
		$this->assertDirectoryDoesNotExist( $tempDir . '-old' );
	}

	public function testInstantDeleteHandlesNonExistentDirectory(): void {
		$nonExistentDir = sys_get_temp_dir() . '/packeta-test-nonexistent-' . uniqid();

		$this->expectException( IOException::class );
		ModuleHelper::instantDelete( $nonExistentDir );
	}

	public function testInstantDeleteWithComplexDirectoryStructure(): void {
		$tempDir = sys_get_temp_dir() . '/packeta-test-complex-' . uniqid();
		$subDir1 = $tempDir . '/subdir1';
		$subDir2 = $tempDir . '/subdir2';
		$file1   = $tempDir . '/file1.txt';
		$file2   = $subDir1 . '/file2.txt';
		$file3   = $subDir2 . '/file3.txt';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		mkdir( $subDir1, 0755, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		mkdir( $subDir2, 0755, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file1, 'content1' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file2, 'content2' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file3, 'content3' );

		$this->assertDirectoryExists( $tempDir );
		$this->assertDirectoryExists( $subDir1 );
		$this->assertDirectoryExists( $subDir2 );
		$this->assertFileExists( $file1 );
		$this->assertFileExists( $file2 );
		$this->assertFileExists( $file3 );

		ModuleHelper::instantDelete( $tempDir );

		$this->assertDirectoryDoesNotExist( $tempDir );
		$this->assertDirectoryDoesNotExist( $tempDir . '-old' );
	}
}
