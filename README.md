# Module for WordPress/WooCommerce

This is the official plugin, that allows you to choose pickup points of Packeta and its external carriers in all of Europe, or utilize address delivery to 25 countries in the European Union, straight from the cart in your e-shop. Furthermore, you can also submit all your orders to Packeta with just one click.

### Download module

[https://wordpress.org/plugins/packeta](https://wordpress.org/plugins/packeta)

#### Supported languages:

- czech
- slovak
- english

#### Supported versions

- PHP: 7.2 - 8.4
- WordPress 5.5+
- WooCommerce 5.1+

#### Functions provided

- integration of [widget v6](https://widget.packeta.com/v6) for selection of pickup points in the e-shop cart
- address validation in the cart for address delivery with our address-picking widget (Czech Republic, Slovakia)
- delivery to pickup points of Packeta (Czech Republic, Slovakia, Hungary, and Romania)
- delivery to pickup points of external carriers all over Europe
- the ability to fill in/change the weight and dimensions of the shipment before submitting it to Packeta
- the automatic submission of orders to Packeta
- after submitting an order to Packeta, each order gets an order number, which acts as a link to a URL with the tracking of the parcel
- label printing, including direct labels
- age verification (18+) can be configured individually for each product. The order will then require the customer to verify his age during the parcel pickup
- printing of the list of parcels
- support for High-Performance order storage (since WooCommerce version 7.9.0)
- support for block checkout
- in shipping zones, it is possible to set shipping methods for individual Packeta carriers
- tracking the status of shipments and automatic change of order status
- possibility to set maximum dimensions of the shipment for each carrier
- filling in customs declarations and sending shipments outside the EU
- possibility to create a complaint assistant for the delivered shipment

#### Installation

* You can install the plugin either in your WordPress administration: Plugins->Plugin installation->Upload plugin or upload the "packetery" folder into the /wp-content/plugins/
* Activate the plugin in the WordPress menu "Plugins"
* Set up the plugin according to our user documentation
* If you update the Packeta plugin manually, you first need to completely delete the "packeta" folder and then upload the folder with the new version of the plugin.

	You should definitely not upgrade by copying the new version to the original folder.
	This could cause the original version to merge with the new one, which can cause the plugin to become completely non-functional.

#### Filters

WP Filters are used to easily alter preselected system behaviors.

To register filter edit wc-includes/functions.php and add your PHP code after all PHP file includes.

##### Order status filtering

To filter additional orders from Packeta order list when applying Packeta filter, use following sample code.
Parameter $queryObject is nullable since plugin version 1.6.0.

```
add_filter( 'packetery_exclude_orders_with_status', function (array $statuses): array {
	$statuses[] = 'wc-cancelled';
	return $statuses;
} );
```

##### Packeta price filter

To convert prices at correct moments the Packeta plugin uses WOOCS filter as only solution.
To support other currency switchers please add price converting filter.
For example, to support active CURCY - Multi Currency for WooCommerce plugin, paste following code to ```wp-includes/functions.php``` after all file includes.

```
add_filter( 'packetery_price', function ( float $price ): float {
	return (float) wmc_get_price( $price );
} );
```

##### Filter altering packet attributes

For example, to set the weight of all packets to 1.5 kg, you can use the following code inserted into ```wp-includes/functions.php```.

```
add_filter( 'packeta_create_packet', function ( array $createPacketData ): array {
	$createPacketData['weight'] = 1.5;
	return $createPacketData;
} );
```

You can find description of the attributes in the [official documentation](https://docs.packetery.com/03-creating-packets/06-packetery-api-reference.html#toc-packetattributes).

##### Filter shipping rate cost

To update Packeta shipping rate cost in checkout, you can use the following code inserted into ```wp-includes/functions.php```.

```
add_filter( 'packeta_shipping_price', function ( $price, $filterParameters ) {
	$order_price = (float) WC()->cart->get_cart_contents_total() + (float) WC()->cart->get_cart_contents_tax();

	if ( ! empty( $filterParameters['free_shipping_limit'] ) && $order_price >= $filterParameters['free_shipping_limit'] ) {
		return 0;
	}

	if ( $filterParameters['carrier_id'] === 'zpointcz' ) {
		if ( $order_price > 300 ) {
			return 70;
		}
		if ( $order_price > 50 ) {
			return 75;
		}
	} elseif ( $filterParameters['carrier_id'] === '106' ) {
		if ( $order_price > 300 ) {
			return 110;
		}
		if ( $order_price > 50 ) {
			return 120;
		}
	} else {
		$firstWeightRule = array_shift( $filterParameters['weight_limits'] );

		return round( $firstWeightRule['price'] * 1.5, 2 );
	}

	return $price;
}, 20, 2 );
```

In the `$filterParameters` variable, there are available following keys:
* `carrier_id` - Either numeric carrier id from the official feed, `zpointxx` for all Packeta pickup points, `xxzpoint` for internal Packeta pickup points, or `xxzbox` for Z-BOXes, where `xx` is lowercase two-letter country code of country with Packeta pickup points.
* `free_shipping_limit` - Free shipping limit.
* `pricing_type` - Tells what limits are used to calculate checkout shipping method price. It is calculated either by weight or by product value.
* `weight_limits` - Array of weight limits used by internal method to compute the price.
* `product_value_limits` - Array of product value limits used by internal method to compute the price.

##### Checkout widget language filter

Since 1.4.2. To set widget language in checkout, you can use the following code inserted into ```wp-includes/functions.php```.

```
add_filter( 'packeta_widget_language', static function ( string $language ): string {
	return 'hu';
} );
```
##### Checkout widget weight filter

Since 1.6.3. If you would like to set the weight passed to the checkout widget, you can use the following code by placing it into ```wp-includes/functions.php```.

```
function packeta_widget_weight($weight) {
	return 1;
}

add_filter( ‘packeta_widget_weight’, ‘packeta_widget_weight’);
```

##### Filter to modify information about Packeta pickup point or validated address in e-mail

To modify this HTML, you can use `packeta_email_footer` filter, for example to render pickup point name or simple address only:

```
add_filter( 'packeta_email_footer', 'packeta_email_footer', 20, 2 );
function packeta_email_footer( string $footerHtml, array $templateParams ) {
	if ( $templateParams['pickupPoint'] && $templateParams['displayPickupPointInfo'] ) {
		$pickupPoint = $templateParams['pickupPoint'];
		$footerHtml  = '<p>' . htmlspecialchars( $pickupPoint->getName() ) . '</p>';
	} elseif ( $templateParams['validatedDeliveryAddress'] ) {
		$address    = $templateParams['validatedDeliveryAddress'];
		$footerHtml = '<p>' . htmlspecialchars( $address->getFullAddress() ) . '</p>';
	}

	return $footerHtml;
}
```

Available keys in the variable `$templateParams` are:
* `displayPickupPointInfo` - true if option "Replace shipping address with pickup point address" is not set or WooCommerce is set to ship to billing address only
* `pickupPoint` - \Packetery\Core\Entity\PickupPoint object if applicable
* `validatedDeliveryAddress` - \Packetery\Core\Entity\Address object if applicable
* `isExternalCarrier` - true if selected delivery option is not one of Packeta Pick-up Points
* `translations` - to examine the content, export this field during filter development

## Credits

* 10up and their [WordPress.org Plugin Deploy](https://github.com/10up/action-wordpress-plugin-deploy) and [WordPress.org Plugin Readme/Assets Update](https://github.com/10up/action-wordpress-plugin-asset-update) Github Actions
