<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\RateUnavailabilityPresenter;
use Packetery\Module\Checkout\RateUnavailabilityReason;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RateUnavailabilityPresenterTest extends TestCase {
	private const ADMIN_URL = 'https://example.com/wp-admin/admin.php';

	private const SHIPPING_ZONES_LINK = self::ADMIN_URL . '?page=wc-settings&tab=shipping';

	private const NO_ACTIVE_CARRIER_TEXT = 'No available carrier of country VE is active in Packeta settings.';

	private const ZONE_TEXT = 'The shipping zone matching the address (Venezuela) has no Packeta shipping method switched on.';

	private const ZONE_AFTER_ACTIVATION_TEXT = 'After you activate a carrier, add its shipping method to the shipping zone matching the address (Venezuela).';

	private const ZONE_METHOD_TEXT = 'None of the Packeta methods in the shipping zone matching the address serves country VE.';

	private const ZONE_METHOD_AFTER_ACTIVATION_TEXT = 'After you activate a carrier, add its shipping method to the shipping zone matching the address.';

	private function createPresenter(): RateUnavailabilityPresenter {
		$wpAdapterMock = $this->createMock( WpAdapter::class );
		$wpAdapterMock->method( '__' )->willReturnArgument( 0 );
		$wpAdapterMock->method( 'getPostField' )->willReturn( 'Blue mug' );
		$wpAdapterMock->method( 'addQueryArg' )->willReturnCallback(
			function ( array $params, string $url ): string {
				return $url . '?' . http_build_query( $params );
			}
		);
		$wpAdapterMock->method( 'getAdminUrl' )->willReturn( self::ADMIN_URL );
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

	public static function noActiveCarrierProvider(): array {
		$countryCarriersLink = self::ADMIN_URL . '?' . http_build_query(
			[
				'page'                              => OptionsPage::SLUG,
				OptionsPage::PARAMETER_COUNTRY_CODE => 've',
			]
		);
		$zoneOnly            = static function ( string $text ): array {
			return [
				'text'     => $text,
				'link'     => self::SHIPPING_ZONES_LINK,
				'linkText' => 'Shipping zones',
				'followUp' => null,
			];
		};
		$activationFirst     = static function ( string $afterActivationText ) use ( $countryCarriersLink ): array {
			return [
				'text'     => self::NO_ACTIVE_CARRIER_TEXT,
				'link'     => $countryCarriersLink,
				'linkText' => 'Carrier settings',
				'followUp' => [
					'text'     => $afterActivationText,
					'link'     => self::SHIPPING_ZONES_LINK,
					'linkText' => 'Shipping zones',
				],
			];
		};

		return [
			'zone without Packeta method, no carrier active' => [
				RateUnavailabilityReason::NO_SHIPPING_ZONE,
				[
					'zoneName'        => 'Venezuela',
					'customerCountry' => 've',
					'noActiveCarrier' => true,
				],
				$activationFirst( self::ZONE_AFTER_ACTIVATION_TEXT ),
			],
			'zone without Packeta method, a carrier active' => [
				RateUnavailabilityReason::NO_SHIPPING_ZONE,
				[
					'zoneName'        => 'Venezuela',
					'customerCountry' => 've',
					'noActiveCarrier' => false,
				],
				$zoneOnly( self::ZONE_TEXT ),
			],
			'zone without Packeta method, record without flag' => [
				RateUnavailabilityReason::NO_SHIPPING_ZONE,
				[ 'zoneName' => 'Venezuela' ],
				$zoneOnly( self::ZONE_TEXT ),
			],
			'no zone method for country, no carrier active' => [
				RateUnavailabilityReason::NO_ZONE_METHOD_FOR_COUNTRY,
				[
					'customerCountry' => 've',
					'noActiveCarrier' => true,
				],
				$activationFirst( self::ZONE_METHOD_AFTER_ACTIVATION_TEXT ),
			],
			'no zone method for country, a carrier active' => [
				RateUnavailabilityReason::NO_ZONE_METHOD_FOR_COUNTRY,
				[
					'customerCountry' => 've',
					'noActiveCarrier' => false,
				],
				$zoneOnly( self::ZONE_METHOD_TEXT ),
			],
			'no zone method for country, record without flag' => [
				RateUnavailabilityReason::NO_ZONE_METHOD_FOR_COUNTRY,
				[ 'customerCountry' => 've' ],
				$zoneOnly( self::ZONE_METHOD_TEXT ),
			],
		];
	}

	/**
	 * Until a carrier of the country is active, the zone has no method to add, so the activation sentence comes first.
	 *
	 * @dataProvider noActiveCarrierProvider
	 *
	 * @param string                                                                                                                            $reason              Reason code.
	 * @param array<string, scalar|null>                                                                                                        $context             Payload stored with the reason.
	 * @param array{text: string, link: string|null, linkText: string|null, followUp: array{text: string, link: string, linkText: string}|null} $expectedDescription Sentences and links in display order.
	 */
	public function testZoneReasonPutsCarrierActivationFirst( string $reason, array $context, array $expectedDescription ): void {
		$description = $this->createPresenter()->present(
			[
				'reason'  => $reason,
				'context' => $context,
			],
			''
		);

		self::assertSame( $expectedDescription, $description );
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
