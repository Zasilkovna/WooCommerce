<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

class RateUnavailabilityReason {

	public const CUSTOMER_COUNTRY_UNKNOWN = 'customerCountryUnknown';
	public const NO_CARRIER_FOR_COUNTRY   = 'noCarrierForCountry';

	/**
	 * Carriers for the country exist, but none of them has a method in the zone matching the address.
	 * Separate from NO_CARRIER_FOR_COUNTRY, which speaks about the Packeta carrier list, not the zones.
	 */
	public const NO_ZONE_METHOD_FOR_COUNTRY = 'noZoneMethodForCountry';

	/**
	 * Reasons WooCommerce never even asks our shipping method - detected when the snapshot is saved,
	 * not inside the method itself.
	 */
	public const NO_SHIPPING_ZONE             = 'noShippingZone';
	public const SHIPPING_COUNTRY_NOT_ALLOWED = 'shippingCountryNotAllowed';

	public const CARRIER_UNAVAILABLE          = 'carrierUnavailable';
	public const AGE_VERIFICATION_UNSUPPORTED = 'ageVerificationUnsupported';
	public const CARRIER_NOT_IN_SHIPPING_ZONE = 'carrierNotInShippingZone';

	/**
	 * The zone matching the address does hold the carrier's method, switched off - which is a different
	 * fix for the client than adding the method to the zone.
	 */
	public const CARRIER_METHOD_DISABLED_IN_ZONE = 'carrierMethodDisabledInZone';

	public const CARRIER_OPTION_INACTIVE = 'carrierOptionInactive';
	public const CAR_DELIVERY_DISABLED   = 'carDeliveryDisabled';
	public const DISALLOWED_BY_PRODUCT   = 'disallowedByProduct';
	public const DISALLOWED_BY_CATEGORY  = 'disallowedByCategory';
	public const PRODUCT_OVERSIZED       = 'productOversized';

	/**
	 * Our method did return the rate and it is gone from the final ones. WooCommerce itself drops the
	 * other rates when the cart has free shipping and the shop asks for it, which is the one culprit
	 * that can be named; anything else leaves no trace of who did it.
	 */
	public const RATE_HIDDEN_BY_FREE_SHIPPING   = 'rateHiddenByFreeShipping';
	public const RATE_REMOVED_AFTER_CALCULATION = 'rateRemovedAfterCalculation';

	public const WEIGHT_OVER_LIMIT        = 'weightOverLimit';
	public const PRODUCT_VALUE_OVER_LIMIT = 'productValueOverLimit';
	public const NO_PRICING_RULES         = 'noPricingRules';
}
