<?php
/**
 * Class ReturnEligibilityTest
 *
 * @package Tests
 */

declare( strict_types=1 );

namespace Tests\Core\Returns;

use Packetery\Core\Returns\ReturnableOrderInfo;
use Packetery\Core\Returns\ReturnEligibility;
use Packetery\Core\Returns\ReturnSettings;
use PHPUnit\Framework\TestCase;

class ReturnEligibilityTest extends TestCase {

	private \DateTimeImmutable $now;
	private \DateTimeImmutable $completedAt;
	private ReturnEligibility $eligibility;

	protected function setUp(): void {
		$this->now         = new \DateTimeImmutable( '2026-07-17 12:00:00' );
		$this->completedAt = new \DateTimeImmutable( '2026-07-10 12:00:00' );
		$this->eligibility = new ReturnEligibility();
	}

	public function testEligibleOrderPassesAllChecks(): void {
		self::assertTrue( $this->isEligible() );
	}

	public function testNotDeliveredOrderIsNotEligible(): void {
		self::assertFalse( $this->isEligible( [ 'orderStatus' => 'processing' ] ) );
	}

	public function testOrderPastReturnWindowIsNotEligible(): void {
		self::assertFalse(
			$this->isEligible( [ 'completedAt' => new \DateTimeImmutable( '2026-06-01 12:00:00' ) ] )
		);
	}

	public function testOrderOnLastWindowDayIsEligible(): void {
		// completed 14 days before now, window = 14 → deadline equals now.
		self::assertTrue(
			$this->isEligible( [ 'completedAt' => new \DateTimeImmutable( '2026-07-03 12:00:00' ) ] )
		);
	}

	public function testMissingCompletedDateDoesNotBlock(): void {
		self::assertTrue( $this->isEligible( [ 'completedAt' => null ] ) );
	}

	public function testUnservicedCountryIsNotEligible(): void {
		self::assertFalse( $this->isEligible( [ 'deliveryCountry' => 'de' ] ) );
	}

	public function testMissingCountryIsNotEligible(): void {
		self::assertFalse( $this->isEligible( [ 'deliveryCountry' => null ] ) );
	}

	public function testCountryNotOnWhitelistIsNotEligible(): void {
		self::assertFalse(
			$this->isEligible( [ 'deliveryCountry' => 'sk' ], [ 'allowedCountries' => [ 'CZ' ] ] )
		);
	}

	public function testCountryOnWhitelistIsEligibleCaseInsensitive(): void {
		self::assertTrue(
			$this->isEligible( [ 'deliveryCountry' => 'cz' ], [ 'allowedCountries' => [ 'CZ' ] ] )
		);
	}

	public function testCarrierNotOnWhitelistIsNotEligible(): void {
		self::assertFalse(
			$this->isEligible( [ 'carrierId' => '999' ], [ 'allowedCarriers' => [ '123', '456' ] ] )
		);
	}

	public function testCarrierOnWhitelistIsEligible(): void {
		self::assertTrue(
			$this->isEligible( [ 'carrierId' => '123' ], [ 'allowedCarriers' => [ '123', '456' ] ] )
		);
	}

	public function testMissingCarrierWithWhitelistIsNotEligible(): void {
		self::assertFalse(
			$this->isEligible( [ 'carrierId' => null ], [ 'allowedCarriers' => [ '123' ] ] )
		);
	}

	public function testOrderOverValueLimitIsNotEligible(): void {
		self::assertFalse(
			$this->isEligible( [ 'totalValue' => 500.0 ], [ 'maxOrderValue' => 300.0 ] )
		);
	}

	public function testOrderOnValueLimitIsEligible(): void {
		self::assertTrue(
			$this->isEligible( [ 'totalValue' => 300.0 ], [ 'maxOrderValue' => 300.0 ] )
		);
	}

	public function testUnknownValueDoesNotBlockWhenLimitSet(): void {
		self::assertTrue(
			$this->isEligible( [ 'totalValue' => null ], [ 'maxOrderValue' => 300.0 ] )
		);
	}

	public function testOrderOverWeightLimitIsNotEligible(): void {
		self::assertFalse(
			$this->isEligible( [ 'totalWeightKg' => 5.0 ], [ 'maxWeightKg' => 3.0 ] )
		);
	}

	public function testOrderWithExcludedCategoryIsNotEligible(): void {
		self::assertFalse(
			$this->isEligible( [ 'categoryIds' => [ 10, 20 ] ], [ 'excludedCategories' => [ 20 ] ] )
		);
	}

	public function testOrderWithoutExcludedCategoryIsEligible(): void {
		self::assertTrue(
			$this->isEligible( [ 'categoryIds' => [ 10, 30 ] ], [ 'excludedCategories' => [ 20 ] ] )
		);
	}

	public function testVirtualItemIsNotEligibleWhenExcluded(): void {
		self::assertFalse(
			$this->isEligible( [ 'hasVirtualItem' => true ], [ 'excludeVirtual' => true ] )
		);
	}

	public function testVirtualItemIsEligibleWhenNotExcluded(): void {
		self::assertTrue(
			$this->isEligible( [ 'hasVirtualItem' => true ], [ 'excludeVirtual' => false ] )
		);
	}

	/**
	 * Runs the engine on a baseline-eligible order/settings pair with the given overrides.
	 *
	 * @param array<string, mixed> $orderOverrides    Order fact overrides.
	 * @param array<string, mixed> $settingsOverrides Settings overrides.
	 */
	private function isEligible( array $orderOverrides = [], array $settingsOverrides = [] ): bool {
		return $this->eligibility->isEligible(
			$this->makeOrder( $orderOverrides ),
			$this->makeSettings( $settingsOverrides ),
			$this->now
		);
	}

	/**
	 * Builds a baseline-eligible order with optional overrides.
	 *
	 * @param array<string, mixed> $o Overrides.
	 *
	 * @return ReturnableOrderInfo
	 */
	private function makeOrder( array $o = [] ): ReturnableOrderInfo {
		$defaults = [
			'orderStatus'     => 'completed',
			'completedAt'     => $this->completedAt,
			'deliveryCountry' => 'cz',
			'carrierId'       => '123',
			'totalValue'      => 100.0,
			'totalWeightKg'   => 1.0,
			'categoryIds'     => [ 10, 20 ],
			'hasVirtualItem'  => false,
		];
		$v        = array_merge( $defaults, $o );

		return new ReturnableOrderInfo(
			$v['orderStatus'],
			$v['completedAt'],
			$v['deliveryCountry'],
			$v['carrierId'],
			$v['totalValue'],
			$v['totalWeightKg'],
			$v['categoryIds'],
			$v['hasVirtualItem']
		);
	}

	/**
	 * Builds baseline (unrestricted) settings with optional overrides.
	 *
	 * @param array<string, mixed> $s Overrides.
	 *
	 * @return ReturnSettings
	 */
	private function makeSettings( array $s = [] ): ReturnSettings {
		$defaults = [
			'returnWindowDays'   => 14,
			'excludeVirtual'     => true,
			'maxOrderValue'      => null,
			'maxWeightKg'        => null,
			'allowedCarriers'    => [],
			'allowedCountries'   => [],
			'excludedCategories' => [],
		];
		$v        = array_merge( $defaults, $s );

		return new ReturnSettings(
			$v['returnWindowDays'],
			$v['excludeVirtual'],
			$v['maxOrderValue'],
			$v['maxWeightKg'],
			$v['allowedCarriers'],
			$v['allowedCountries'],
			$v['excludedCategories']
		);
	}
}
