<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\RateUnavailabilityPresenter;
use Packetery\Module\Checkout\RateUnavailabilityReason;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RateUnavailabilityPresenterTest extends TestCase {
	private function createPresenter(): RateUnavailabilityPresenter {
		$wpAdapterMock = $this->createMock( WpAdapter::class );
		$wpAdapterMock->method( '__' )->willReturnArgument( 0 );
		$wpAdapterMock->method( 'getPostField' )->willReturn( 'Blue mug' );
		$wpAdapterMock->method( 'addQueryArg' )->willReturn( 'https://example.com/wp-admin/admin.php?page=whatever' );
		$wpAdapterMock->method( 'getAdminUrl' )->willReturn( 'https://example.com/wp-admin/admin.php' );
		$wpAdapterMock->method( 'getEditPostLink' )->willReturn( 'https://example.com/wp-admin/post.php?post=42' );
		$wpAdapterMock->method( 'getEditTermLink' )->willReturn( 'https://example.com/wp-admin/term.php?tag_ID=7' );

		$wcAdapterMock = $this->createMock( WcAdapter::class );
		$wcAdapterMock->method( 'price' )->willReturnCallback(
			function ( float $price ): string {
				return sprintf( '%s CZK', $price );
			}
		);

		return new RateUnavailabilityPresenter( $wpAdapterMock, $wcAdapterMock );
	}

	public static function reasonProvider(): array {
		return [
			[ RateUnavailabilityReason::CUSTOMER_COUNTRY_UNKNOWN, [], null ],
			[ RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY, [ 'customerCountry' => 'cz' ], 'CZ' ],
			[ RateUnavailabilityReason::NO_ZONE_METHOD_FOR_COUNTRY, [ 'customerCountry' => 'de' ], 'DE' ],
			[ RateUnavailabilityReason::SHIPPING_COUNTRY_NOT_ALLOWED, [ 'customerCountry' => 'de' ], 'DE' ],
			[ RateUnavailabilityReason::NO_SHIPPING_ZONE, [ 'zoneName' => 'Czechia' ], 'Czechia' ],
			[ RateUnavailabilityReason::CARRIER_NOT_IN_SHIPPING_ZONE, [], null ],
			[ RateUnavailabilityReason::CARRIER_METHOD_DISABLED_IN_ZONE, [], null ],
			[ RateUnavailabilityReason::RATE_HIDDEN_BY_FREE_SHIPPING, [], null ],
			[ RateUnavailabilityReason::RATE_REMOVED_AFTER_CALCULATION, [], null ],
			[ RateUnavailabilityReason::CARRIER_UNAVAILABLE, [], null ],
			[ RateUnavailabilityReason::CARRIER_OPTION_INACTIVE, [], null ],
			[ RateUnavailabilityReason::CAR_DELIVERY_DISABLED, [], null ],
			[ RateUnavailabilityReason::AGE_VERIFICATION_UNSUPPORTED, [], null ],
			[ RateUnavailabilityReason::DISALLOWED_BY_PRODUCT, [ 'productId' => 42 ], 'Blue mug' ],
			[
				RateUnavailabilityReason::DISALLOWED_BY_CATEGORY,
				[
					'productId'  => 42,
					'categoryId' => 7,
				],
				'Blue mug',
			],
			[
				RateUnavailabilityReason::PRODUCT_OVERSIZED,
				[
					'productId'   => 42,
					'restriction' => CartService::SIZE_RESTRICTION_MAXIMUM_LENGTH,
					'measured'    => 45,
					'limit'       => 30.0,
				],
				'maximum length',
			],
			[
				RateUnavailabilityReason::WEIGHT_OVER_LIMIT,
				[
					'cartWeight'      => 6.2,
					'highestLimit'    => 5.0,
					'configuredRules' => 3,
				],
				'6.2',
			],
			[
				RateUnavailabilityReason::PRODUCT_VALUE_OVER_LIMIT,
				[
					'totalCartProductValue' => 1200.0,
					'highestLimit'          => 1000.0,
					'configuredRules'       => 2,
				],
				'1200 CZK',
			],
			[ RateUnavailabilityReason::NO_PRICING_RULES, [], null ],
		];
	}

	/**
	 * @dataProvider reasonProvider
	 *
	 * @param string                     $reason           Reason code.
	 * @param array<string, scalar|null> $context          Payload stored with the reason.
	 * @param string|null                $expectedFragment Value the sentence has to carry.
	 */
	public function testEveryReasonBecomesASentence( string $reason, array $context, ?string $expectedFragment ): void {
		$description = $this->createPresenter()->present(
			[
				'reason'  => $reason,
				'context' => $context,
			],
			'zpoint-cz'
		);

		self::assertNotSame( $reason, $description['text'] );
		self::assertStringEndsWith( '.', $description['text'] );
		self::assertStringNotContainsString( '%', $description['text'] );
		if ( $expectedFragment !== null ) {
			self::assertStringContainsString( $expectedFragment, $description['text'] );
		}
	}

	/**
	 * A reason left without a sentence would reach the client as a camel case identifier.
	 */
	public function testEveryReasonConstantIsCoveredByTheProvider(): void {
		$describedReasons = array_column( self::reasonProvider(), 0 );
		$declaredReasons  = array_values( ( new ReflectionClass( RateUnavailabilityReason::class ) )->getConstants() );
		sort( $describedReasons );
		sort( $declaredReasons );

		self::assertSame( $declaredReasons, $describedReasons );
	}
}
