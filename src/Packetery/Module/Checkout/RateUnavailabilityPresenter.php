<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Carrier;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;

/**
 * Turns a reason code and its payload into a sentence the client can act on, plus a link to the place
 * the setting lives. Without the numbers and the culprit the reason only invites another question.
 */
class RateUnavailabilityPresenter {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct( WpAdapter $wpAdapter, WcAdapter $wcAdapter ) {
		$this->wpAdapter = $wpAdapter;
		$this->wcAdapter = $wcAdapter;
	}

	/**
	 * @param array{reason: string, context: array<string, scalar|null>} $unavailability
	 *
	 * @return array{text: string, link: string|null, linkText: string|null}
	 */
	public function present( array $unavailability, string $carrierId ): array {
		$reason  = $unavailability['reason'];
		$context = $unavailability['context'];

		switch ( $reason ) {
			case RateUnavailabilityReason::CUSTOMER_COUNTRY_UNKNOWN:
				return $this->describe(
					$this->wpAdapter->__( 'The delivery country is not known yet, so no carrier can be evaluated.', 'packeta' )
				);
			case RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY:
				return $this->describe(
					sprintf(
						// translators: %s: country code.
						$this->wpAdapter->__( 'No Packeta carrier is available for country %s.', 'packeta' ),
						strtoupper( (string) ( $context['customerCountry'] ?? '' ) )
					)
				);
			case RateUnavailabilityReason::NO_ZONE_METHOD_FOR_COUNTRY:
				return $this->describe(
					sprintf(
						// translators: %s: country code.
						$this->wpAdapter->__( 'None of the Packeta methods in the shipping zone matching the address serves country %s.', 'packeta' ),
						strtoupper( (string) ( $context['customerCountry'] ?? '' ) )
					),
					$this->getWcSettingsLink( 'shipping' ),
					$this->wpAdapter->__( 'Shipping zones', 'packeta' )
				);
			case RateUnavailabilityReason::SHIPPING_COUNTRY_NOT_ALLOWED:
				return $this->describe(
					sprintf(
						// translators: %s: country code.
						$this->wpAdapter->__( 'WooCommerce does not ship to country %s, so it never asks Packeta for rates.', 'packeta' ),
						strtoupper( (string) ( $context['customerCountry'] ?? '' ) )
					),
					$this->getWcSettingsLink( 'general' ),
					$this->wpAdapter->__( 'WooCommerce shipping locations', 'packeta' )
				);
			case RateUnavailabilityReason::NO_SHIPPING_ZONE:
				return $this->describe(
					sprintf(
						// translators: %s: shipping zone name.
						$this->wpAdapter->__( 'The shipping zone matching the address (%s) has no Packeta shipping method switched on.', 'packeta' ),
						(string) ( $context['zoneName'] ?? '' )
					),
					$this->getWcSettingsLink( 'shipping' ),
					$this->wpAdapter->__( 'Shipping zones', 'packeta' )
				);
			case RateUnavailabilityReason::CARRIER_NOT_IN_SHIPPING_ZONE:
				return $this->describe(
					$this->wpAdapter->__( 'The carrier is switched on in Packeta settings, but it is not added to the shipping zone matching the address.', 'packeta' ),
					$this->getWcSettingsLink( 'shipping' ),
					$this->wpAdapter->__( 'Shipping zones', 'packeta' )
				);
			case RateUnavailabilityReason::CARRIER_METHOD_DISABLED_IN_ZONE:
				return $this->describe(
					$this->wpAdapter->__( 'The shipping zone matching the address does contain this carrier, but its shipping method is switched off there.', 'packeta' ),
					$this->getWcSettingsLink( 'shipping' ),
					$this->wpAdapter->__( 'Shipping zones', 'packeta' )
				);
			case RateUnavailabilityReason::RATE_HIDDEN_BY_FREE_SHIPPING:
				return $this->describe(
					$this->wpAdapter->__( 'WooCommerce removed the rate because the cart qualifies for free shipping and the shop is set to hide the other shipping options.', 'packeta' ),
					$this->getWcSettingsLink( 'shipping' ),
					$this->wpAdapter->__( 'Shipping options', 'packeta' )
				);
			case RateUnavailabilityReason::RATE_REMOVED_AFTER_CALCULATION:
				return $this->describe(
					$this->wpAdapter->__( 'Packeta did return a rate for this carrier, but it is not among the shipping options WooCommerce ended up with - it was dropped after the calculation, for example by another plugin through the woocommerce_package_rates filter.', 'packeta' )
				);
			case RateUnavailabilityReason::CARRIER_UNAVAILABLE:
				return $this->describe(
					$this->wpAdapter->__( 'Packeta reports this carrier as unavailable in the carrier feed.', 'packeta' )
				);
			case RateUnavailabilityReason::CARRIER_OPTION_INACTIVE:
				return $this->describe(
					$this->wpAdapter->__( 'The carrier is not activated in Packeta settings.', 'packeta' ),
					$this->getCarrierSettingsLink( $carrierId ),
					$this->wpAdapter->__( 'Carrier settings', 'packeta' )
				);
			case RateUnavailabilityReason::CAR_DELIVERY_DISABLED:
				// Switched off by a config parameter shipped with the plugin, so there is no setting to
				// send the merchant to - the previous wording pointed at Packeta settings and sent them
				// looking for a switch that is not there.
				return $this->describe(
					$this->wpAdapter->__( 'Car delivery is not available in this version of the plugin.', 'packeta' )
				);
			case RateUnavailabilityReason::AGE_VERIFICATION_UNSUPPORTED:
				return $this->describe(
					$this->wpAdapter->__( 'A product in the cart requires age verification and this carrier does not support it.', 'packeta' )
				);
			case RateUnavailabilityReason::DISALLOWED_BY_PRODUCT:
				$productId = (int) ( $context['productId'] ?? 0 );

				return $this->describe(
					sprintf(
						// translators: %s: product name.
						$this->wpAdapter->__( 'Product %s in the cart has this carrier disabled.', 'packeta' ),
						$this->getPostTitle( $productId )
					),
					$this->wpAdapter->getEditPostLink( $productId, 'url' ),
					$this->wpAdapter->__( 'Edit product', 'packeta' )
				);
			case RateUnavailabilityReason::DISALLOWED_BY_CATEGORY:
				$productId  = (int) ( $context['productId'] ?? 0 );
				$categoryId = (int) ( $context['categoryId'] ?? 0 );

				return $this->describe(
					sprintf(
						// translators: %1$s: category name, %2$s: product name.
						$this->wpAdapter->__( 'Category %1$s of product %2$s has this carrier disabled.', 'packeta' ),
						$this->getTermName( $categoryId ),
						$this->getPostTitle( $productId )
					),
					$this->wpAdapter->getEditTermLink( $categoryId, 'product_cat' ),
					$this->wpAdapter->__( 'Edit category', 'packeta' )
				);
			case RateUnavailabilityReason::PRODUCT_OVERSIZED:
				return $this->describe(
					sprintf(
						// translators: %1$s: product name, %2$s: which size limit, %3$s: measured size, %4$s: configured limit.
						$this->wpAdapter->__( 'Product %1$s exceeds the carrier size limit (%2$s): %3$s cm against a limit of %4$s cm.', 'packeta' ),
						$this->getPostTitle( (int) ( $context['productId'] ?? 0 ) ),
						$this->getSizeRestrictionName( (string) ( $context['restriction'] ?? '' ) ),
						(string) ( $context['measured'] ?? '' ),
						(string) ( $context['limit'] ?? '' )
					),
					$this->getCarrierSettingsLink( $carrierId ),
					$this->wpAdapter->__( 'Carrier settings', 'packeta' )
				);
			case RateUnavailabilityReason::WEIGHT_OVER_LIMIT:
				return $this->describe(
					sprintf(
						// translators: %1$s: cart weight, %2$s: highest configured weight rule, %3$s: number of rules.
						$this->wpAdapter->__( 'Cart weight %1$s kg exceeds the highest configured weight rule, %2$s kg (%3$s rules set).', 'packeta' ),
						(string) ( $context['cartWeight'] ?? '' ),
						(string) ( $context['highestLimit'] ?? '' ),
						(string) ( $context['configuredRules'] ?? '' )
					),
					$this->getCarrierSettingsLink( $carrierId ),
					$this->wpAdapter->__( 'Carrier settings', 'packeta' )
				);
			case RateUnavailabilityReason::PRODUCT_VALUE_OVER_LIMIT:
				return $this->describe(
					sprintf(
						// translators: %1$s: cart product value, %2$s: highest configured value rule, %3$s: number of rules.
						$this->wpAdapter->__( 'Cart product value %1$s exceeds the highest configured value rule, %2$s (%3$s rules set).', 'packeta' ),
						$this->wcAdapter->price( (float) ( $context['totalCartProductValue'] ?? 0 ) ),
						$this->wcAdapter->price( (float) ( $context['highestLimit'] ?? 0 ) ),
						(string) ( $context['configuredRules'] ?? '' )
					),
					$this->getCarrierSettingsLink( $carrierId ),
					$this->wpAdapter->__( 'Carrier settings', 'packeta' )
				);
			case RateUnavailabilityReason::NO_PRICING_RULES:
				return $this->describe(
					$this->wpAdapter->__( 'The carrier has a pricing type set but no pricing rule configured, so no price can be calculated.', 'packeta' ),
					$this->getCarrierSettingsLink( $carrierId ),
					$this->wpAdapter->__( 'Carrier settings', 'packeta' )
				);
			default:
				return $this->describe( $reason );
		}
	}

	/**
	 * The stored reason carries the restriction key, which is a column name, not something to read.
	 */
	private function getSizeRestrictionName( string $restriction ): string {
		switch ( $restriction ) {
			case CartService::SIZE_RESTRICTION_MAXIMUM_LENGTH:
				return $this->wpAdapter->__( 'maximum length', 'packeta' );
			case CartService::SIZE_RESTRICTION_DIMENSIONS_SUM:
				return $this->wpAdapter->__( 'sum of dimensions', 'packeta' );
			case CartService::SIZE_RESTRICTION_DIMENSIONS:
				return $this->wpAdapter->__( 'length, width and height', 'packeta' );
			default:
				return $restriction;
		}
	}

	/**
	 * @return array{text: string, link: string|null, linkText: string|null}
	 */
	private function describe( string $text, ?string $link = null, ?string $linkText = null ): array {
		return [
			'text'     => $text,
			'link'     => $link,
			'linkText' => $linkText,
		];
	}

	private function getCarrierSettingsLink( string $carrierId ): string {
		return $this->wpAdapter->addQueryArg(
			[
				'page'                                    => Carrier\OptionsPage::SLUG,
				Carrier\OptionsPage::PARAMETER_CARRIER_ID => $carrierId,
			],
			$this->wpAdapter->getAdminUrl( null, 'admin.php' )
		);
	}

	private function getWcSettingsLink( string $tab ): string {
		return $this->wpAdapter->addQueryArg(
			[
				'page' => 'wc-settings',
				'tab'  => $tab,
			],
			$this->wpAdapter->getAdminUrl( null, 'admin.php' )
		);
	}

	private function getPostTitle( int $postId ): string {
		$title = $this->wpAdapter->getPostField( 'post_title', $postId );

		return $title === '' ? sprintf( '#%d', $postId ) : $title;
	}

	private function getTermName( int $termId ): string {
		$term = $this->wpAdapter->getTerm( $termId );

		return $term instanceof \WP_Term ? $term->name : sprintf( '#%d', $termId );
	}
}
