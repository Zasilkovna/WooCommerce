---
title: "woocommerce — packetery-module-checkout module"
repo: woocommerce
module: packetery-module-checkout
generated-by: skill:generate-docs@0.3.5
source-commit: 3d44ef98
last-generated: 2026-09-22
covers: [src/Packetery/Module/Checkout]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-module-checkout, type-reference]
---

Repo: woocommerce · Module: packetery-module-checkout · Type: reference · Status: current

## packetery-module-checkout: purpose

packetery-module-checkout puts the Packeta delivery options into the WooCommerce cart and checkout.
The module builds the shipping rates of the Packeta carriers
[VERIFY: src/Packetery/Module/Checkout/ShippingRateFactory.php#createShippingRates], it renders the
widget button and the hidden fields of the checkout form
[VERIFY: src/Packetery/Module/Checkout/CheckoutRenderer.php#actionRenderHiddenInputFields], and it
validates what the customer selected
[VERIFY: src/Packetery/Module/Checkout/CheckoutValidator.php#actionValidateCheckoutData]. After
WooCommerce creates the order, the module writes the pickup point, the address and the car delivery
data to the order [VERIFY: src/Packetery/Module/Checkout/OrderUpdater.php#actionUpdateOrder].

packetery-module-checkout supports the classic checkout and the block checkout, and it detects which
one the shop uses [VERIFY: src/Packetery/Module/Checkout/CheckoutService.php#areBlocksUsedInCheckout].
The module holds 12 files, 2162 lines of logic and 69 public methods. One class registers most hooks
of the namespace, and the other classes give the callbacks
[VERIFY: src/Packetery/Module/Checkout/Checkout.php#registerHooks]. Three callbacks of the namespace
are registered outside this module: the cart fee callback and the guest session callback
[VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerFrontEnd] and the widget settings AJAX
callback [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd].

> ⚠ add business context (elicitation)

## packetery-module-checkout: public interface

packetery-module-checkout exposes its members as WooCommerce hook callbacks and as services of the
plugin. The module registers no REST route of its own.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Hook registration | `registerHooks` | Registers the checkout hooks of the namespace, except the three that the plugin registers | [VERIFY: src/Packetery/Module/Checkout/Checkout.php#registerHooks] |
| Cart fees | `actionCalculateFees` | Adds the age verification fee and the cash on delivery surcharge | [VERIFY: src/Packetery/Module/Checkout/Checkout.php#actionCalculateFees] |
| Payment gateway filter | `woocommerce_available_payment_gateways` | Removes the gateways that the selected carrier does not allow | [VERIFY: src/Packetery/Module/Checkout/Checkout.php#filterPaymentGateways] |
| Shipping rates | `createShippingRates` | Builds one rate for each available Packeta carrier | [VERIFY: src/Packetery/Module/Checkout/ShippingRateFactory.php#createShippingRates] |
| Classic checkout validation | `woocommerce_after_checkout_validation` | Rejects an incomplete or invalid Packeta selection | [VERIFY: src/Packetery/Module/Checkout/CheckoutValidator.php#actionValidateCheckoutData] |
| Block checkout validation | `woocommerce_store_api_checkout_update_order_from_request` | Does the same check for the block checkout and throws a REST exception | [VERIFY: src/Packetery/Module/Checkout/CheckoutValidator.php#actionValidateBlockCheckoutData] |
| Order update | `woocommerce_checkout_update_order_meta`, `woocommerce_store_api_checkout_order_processed` | Writes the Packeta data to the new order | [VERIFY: src/Packetery/Module/Checkout/OrderUpdater.php#actionUpdateOrder] |
| Form fields | `woocommerce_review_order_before_submit` | Renders the hidden fields of the pickup point, the address and the car delivery | [VERIFY: src/Packetery/Module/Checkout/CheckoutRenderer.php#actionRenderHiddenInputFields] |
| Widget button | `woocommerce_review_order_after_shipping`, `woocommerce_after_shipping_rate` | Renders the button that opens the Packeta widget, in the configured place | [VERIFY: src/Packetery/Module/Checkout/CheckoutRenderer.php#actionRenderWidgetButtonAfterShippingRate] |
| Widget settings | `createSettings` | Builds the settings and the translations for the widget in the browser | [VERIFY: src/Packetery/Module/Checkout/CheckoutSettings.php#createSettings] |

The module gives four extension filters to other code: `packeta_shipping_price`
[VERIFY: src/Packetery/Module/Checkout/RateCalculator.php#getShippingRateCost], `packetery_price`
[VERIFY: src/Packetery/Module/Checkout/CurrencySwitcherService.php#getConvertedPrice],
`packeta_widget_weight` and `packeta_widget_language`
[VERIFY: src/Packetery/Module/Checkout/CheckoutSettings.php#createSettings]. The module also
clears the cached shipping rates of every package when WooCommerce renders the order review
[VERIFY: src/Packetery/Module/Checkout/SessionService.php#actionUpdateShippingRates]. The payment
method enters the cache key of the package, so a change of the payment method gives new rates
[VERIFY: src/Packetery/Module/Checkout/SessionService.php#filterUpdateShippingPackages].

## packetery-module-checkout: price and validation rules

packetery-module-checkout computes one price for each carrier and rejects an invalid selection
before WooCommerce creates the order. The price comes from the carrier options
[VERIFY: src/Packetery/Module/Checkout/RateCalculator.php#getShippingRateCost]. The module reads the
weight limits or the order value limits of the carrier, and it takes the first limit that the cart
does not exceed. When the carrier has per class options, the module groups the cart items by the
shipping class, computes a price for each class, and then adds the prices or takes the most
expensive one [VERIFY: src/Packetery/Module/Checkout/RateCalculator.php#getFinalShippingClassesCost].
A free shipping limit or a free shipping coupon sets the price to zero
[VERIFY: src/Packetery/Module/Checkout/RateCalculator.php#getShippingRateCost].
When no limit applies, the price stays `null` and the module does not offer that carrier.
The cash on delivery surcharge follows the same first match rule over the surcharge limits
[VERIFY: src/Packetery/Module/Checkout/RateCalculator.php#getCODSurcharge].

The module hides a carrier that cannot deliver the cart. It checks the age verification, the maximum
cart value, the product size, the product category and the disallowed rate identifiers
[VERIFY: src/Packetery/Module/Checkout/ShippingRateFactory.php#canCreateShippingRate]. The size
check compares the three largest dimensions of a product with the size restrictions of the carrier
[VERIFY: src/Packetery/Module/Checkout/CartService.php#cartContainsProductOversizedForCarrier].
The validator then checks the selection of the customer. For a pickup point order it verifies the
required attributes, the country of the carrier and, when the shop enables the check, the pickup
point itself [VERIFY: src/Packetery/Module/Checkout/CheckoutValidator.php#validatePickupPoint]. For
a home delivery order it verifies the validated address when the carrier option requires it
[VERIFY: src/Packetery/Module/Checkout/CheckoutValidator.php#validateHomeDelivery].

## packetery-module-checkout: dependencies

packetery-module-checkout depends on the carrier configuration, on the order module and on the
WooCommerce cart. Structured lines:

references → packetery-core
references → packetery-module-carrier
references → packetery-module-order
references → packetery-module-options
references → packetery-module-product
references → packetery-module-framework
references → packetery-module-api
references → packetery-module-shipping
references → packetery-module-diagnosticslogger
references → packetery-module-exception
references → packetery-module-views
references → packetery-module-log
references → packetery-module-productcategory
references → packetery-module-payment
calls → packeta-widget (sync, HTTPS)

The module reads the carrier entity, the carrier options and the pickup point configuration from
`packetery-module-carrier` [VERIFY: src/Packetery/Module/Checkout/ShippingRateFactory.php#canCreateShippingRate].
It uses the attribute keys, the address mapper, the order repository and the pickup point validator
of `packetery-module-order`, and it starts the automatic packet submission after the order
[VERIFY: src/Packetery/Module/Checkout/OrderUpdater.php#getPropsFromCheckoutData]. The browser loads
the Packeta widget, and the module gives the widget its settings, its translations and the URLs of
the internal REST routes [VERIFY: src/Packetery/Module/Checkout/CheckoutSettings.php#createSettings].
The module uses the internal REST router of `packetery-module-api`, the shipping method classes of
`packetery-module-shipping`, and the logger, the exceptions, the views and the product category
helpers of the other Packeta modules
[VERIFY: src/Packetery/Module/Checkout/CheckoutSettings.php#createSettings].
The module reads the cart and the session through the adapters of `packetery-module-framework`
[VERIFY: src/Packetery/Module/Checkout/SessionService.php#getChosenMethodFromSession]. One external
currency switcher plugin is supported, and every other conversion goes through a filter
[VERIFY: src/Packetery/Module/Checkout/CurrencySwitcherService.php#getConvertedPrice].

## packetery-module-checkout: data model

packetery-module-checkout owns no database table. The module keeps the `checkout-data` of the
customer in one WordPress transient, and it keeps the selected methods in the WooCommerce session.

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| checkout-data | `CHECKOUT_DATA_PREFIX` | transient name prefix | The name adds the session token of the user or the customer id of a guest | [VERIFY: src/Packetery/Module/Checkout/CheckoutStorage.php#getTransientNamePacketaCheckoutData] |
| checkout-data | `POINT_ID` | string | Pickup point that the customer selected in the widget | [VERIFY: src/Packetery/Module/Checkout/CheckoutStorage.php#getPostDataIncludingStoredData] |
| checkout-data | `ADDRESS_IS_VALIDATED` | string flag | Result of the address validation in the widget | [VERIFY: src/Packetery/Module/Checkout/CheckoutValidator.php#validateHomeDelivery] |
| checkout-data | `CAR_DELIVERY_ID` | string | Car delivery selection | [VERIFY: src/Packetery/Module/Checkout/OrderUpdater.php#updateCarDelivery] |
| checkout-data | `CARRIER_ID` | string | Carrier of the selected shipping rate | [VERIFY: src/Packetery/Module/Checkout/OrderUpdater.php#actionUpdateOrder] |
| checkout-data | expiration | seconds | The filter `wc_session_expiration` can change the default | [VERIFY: src/Packetery/Module/Checkout/CheckoutStorage.php#getSessionExpiration] |
| session | `chosen_shipping_methods` | array | Shipping method that the customer selected | [VERIFY: src/Packetery/Module/Checkout/SessionService.php#getChosenMethodFromSession] |
| session | `chosen_payment_method` | string | Payment method for the surcharge and for the gateway filter | [VERIFY: src/Packetery/Module/Checkout/SessionService.php#getChosenPaymentMethod] |
| session | `shipping_for_package_` | cache key prefix | The module clears the cached rates of a package | [VERIFY: src/Packetery/Module/Checkout/SessionService.php#actionUpdateShippingRates] |
| package | `packetery_payment_method` | string | The module adds the payment method to the package, so the rate cache hash changes | [VERIFY: src/Packetery/Module/Checkout/SessionService.php#filterUpdateShippingPackages] |

The module reads the stored data back into the POST data of the checkout
[VERIFY: src/Packetery/Module/Checkout/CheckoutStorage.php#getPostDataIncludingStoredData]. When a
guest signs in, the module moves the transient to the session of the user
[VERIFY: src/Packetery/Module/Checkout/CheckoutStorage.php#migrateGuestSessionToUserSession]. The
order module owns the attribute keys, and this module uses them as the keys of the stored data
[VERIFY: src/Packetery/Module/Checkout/CheckoutStorage.php#getFromTransient].

## packetery-module-checkout: known limitations

packetery-module-checkout has these limitations evidenced in the code. The cart values are zero
before WordPress completes the `wp_loaded` action, and the weight of the cart is then zero
[VERIFY: src/Packetery/Module/Checkout/CartService.php#getCartWeightKg]. The size of the largest
product is `null` in the same situation
[VERIFY: src/Packetery/Module/Checkout/CartService.php#getBiggestProductSize]. Only one currency
switcher plugin is supported by name, and its plugin file is a hardcoded string
[VERIFY: src/Packetery/Module/Checkout/CurrencySwitcherService.php#getConvertedPrice].

The update of a new order ends without an error when the chosen method is missing, when the stored
checkout data is empty, or when the carrier is not found. The module writes a log line and returns
[VERIFY: src/Packetery/Module/Checkout/OrderUpdater.php#actionUpdateOrder]. The fee logic of the
cart is skipped in the same silent way when the customer selects a method of another plugin
[VERIFY: src/Packetery/Module/Checkout/Checkout.php#actionCalculateFees]. The name of the shipping
class group falls back to a hardcoded slug when a product has no class
[VERIFY: src/Packetery/Module/Checkout/RateCalculator.php#getFinalShippingClassesCost]. The module
contains no TODO comment and no FIXME comment.

The class that builds the widget settings gives an AJAX callback, but the registration of that
callback is outside this module
[VERIFY: src/Packetery/Module/Checkout/CheckoutSettings.php#actionCreateSettingsAjax].
