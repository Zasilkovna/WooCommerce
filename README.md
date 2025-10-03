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

##### Customization

###### Custom configuration for cache and log directories

By default, the Packeta plugin stores its cache and log files in the `wp-content/plugins/packeta/temp` directory.
If you need to store these files in a different location, you can configure a custom cache directory.

**How to configure a custom cache directory:**

1. **Add the configuration** to your WordPress `wp-config.php` file, for example:
	```php
	define('PACKETERY_CACHE_BASE_PATH', '/var/www/public_html/wp-content/packeta');
	```

2. **Check permissions**:
	- The plugin uses two folders inside the main one: `cache` and `log`.
	- If the main folder or these folders do not exist, the plugin tries to create them with permissions 0775.
	- The web server needs write access to both the main folder and subfolders.
	- Contact your system administrator if you're unsure about permissions.

#### Filters

WP Filters are used to easily alter preselected system behaviors.

To register filter edit wc-includes/functions.php and add your PHP code after all PHP file includes.

##### Order status filtering

To filter additional orders from Packeta order list when applying Packeta filter, use following sample code.
Parameter $queryObject is nullable since plugin version 1.6.0.

```php
add_filter( 'packetery_exclude_orders_with_status', function (array $statuses): array {
	$statuses[] = 'wc-cancelled';
	return $statuses;
} );
```

##### Packeta price filter

To convert prices at correct moments the Packeta plugin uses WOOCS filter as only solution.
To support other currency switchers please add price converting filter.
For example, to support active CURCY - Multi Currency for WooCommerce plugin, paste following code to ```wp-includes/functions.php``` after all file includes.

```php
add_filter( 'packetery_price', function ( float $price ): float {
	return (float) wmc_get_price( $price );
} );
```

##### Filter altering packet attributes

For example, to set the weight of all packets to 1.5 kg, you can use the following code inserted into ```wp-includes/functions.php```.

```php
add_filter( 'packeta_create_packet', function ( array $createPacketData ): array {
	$createPacketData['weight'] = 1.5;
	return $createPacketData;
} );
```

You can find description of the attributes in the [official documentation](https://docs.packetery.com/03-creating-packets/06-packetery-api-reference.html#toc-packetattributes).

##### Filter shipping rate cost

To update Packeta shipping rate cost in checkout, you can use the following code inserted into ```wp-includes/functions.php```.

```php
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

```php
add_filter( 'packeta_widget_language', static function ( string $language ): string {
	return 'hu';
} );
```
##### Checkout widget weight filter

Since 1.6.3. If you would like to set the weight passed to the checkout widget, you can use the following code by placing it into ```wp-includes/functions.php```.

```php
function packeta_widget_weight($weight) {
	return 1;
}

add_filter( ‘packeta_widget_weight’, ‘packeta_widget_weight’);
```

##### Filter to modify information about Packeta pickup point or validated address in e-mail

To modify this HTML, you can use `packeta_email_footer` filter, for example to render pickup point name or simple address only:

```php
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

##### Filter to hide "Run options wizard" button at order detail

```php
add_filter( 'packeta_order_detail_show_run_wizard_button', function () {
	return false;
} );
```

##### Filter to customize links on order list page

```php
add_filter( 'packeta_order_grid_links_settings', function ( \Packetery\Module\Order\GridLinksConfig $linkConfig ) {
	$linkConfig->setFilterOrdersToSubmitEnabled( true );
	$linkConfig->setFilterOrdersToSubmitTitle( 'Your title for submit filter' );
	$linkConfig->setFilterOrdersToPrintEnabled( true );
	$linkConfig->setFilterOrdersToPrintTitle( 'Your title for print filter' );
	$linkConfig->setOrderGridRunWizardEnabled( true );
	$linkConfig->setOrderGridRunWizardTitle( 'Your title for run wizard link' );

	return $linkConfig;
} );
```

## Email Templates and Shortcodes

The plugin provides shortcodes that can be used in WooCommerce email templates to display Packeta-related information. All shortcodes require the `order_id` parameter to work.

### Basic Shortcodes

The following shortcodes are available for use in email templates:

| Shortcode                                                                   | Description                                         |
|-----------------------------------------------------------------------------|-----------------------------------------------------|
| `[packeta_tracking_number order_id="<?php echo $order->get_id(); ?>"]`      | Displays the tracking number if available           |
| `[packeta_tracking_url order_id="<?php echo $order->get_id(); ?>"]`         | Displays the tracking URL if available              |
| `[packeta_pickup_point_id order_id="<?php echo $order->get_id(); ?>"]`      | Displays the pickup point ID if available           |
| `[packeta_pickup_point_name order_id="<?php echo $order->get_id(); ?>"]`    | Displays the pickup point name if available         |
| `[packeta_pickup_point_address order_id="<?php echo $order->get_id(); ?>"]` | Displays the full pickup point address if available |
| `[packeta_pickup_point_street order_id="<?php echo $order->get_id(); ?>"]`  | Displays the pickup point street if available       |
| `[packeta_pickup_point_city order_id="<?php echo $order->get_id(); ?>"]`    | Displays the pickup point city if available         |
| `[packeta_pickup_point_zip order_id="<?php echo $order->get_id(); ?>"]`     | Displays the pickup point ZIP if available          |
| `[packeta_pickup_point_country order_id="<?php echo $order->get_id(); ?>"]` | Displays the pickup point country if available      |
| `[packeta_carrier_name order_id="<?php echo $order->get_id(); ?>"]`         | Displays the carrier name if available              |

### Conditional Shortcodes

You can use conditional shortcodes to display content only when certain conditions are met:

#### If Submitted

Use this to display content only when the order has been submitted to Packeta:

```php
[packeta_if_packet_submitted order_id="<?php echo $order->get_id(); ?>"]
	Your tracking number is: [packeta_tracking_number order_id="<?php echo $order->get_id(); ?>"]
	Track your package here: [packeta_tracking_url order_id="<?php echo $order->get_id(); ?>"]
[/packeta_if_packet_submitted]
```

#### If Pickup Point

Use this to display content only when the order uses a pickup point:

```php
[packeta_if_pickup_point order_id="<?php echo $order->get_id(); ?>"]
	Your pickup point is: [packeta_pickup_point_name order_id="<?php echo $order->get_id(); ?>"]
	Address: [packeta_pickup_point_address order_id="<?php echo $order->get_id(); ?>"]
[/packeta_if_pickup_point]
```

#### If Carrier

Use this to display content only when the order uses an external carrier:

```php
[packeta_if_carrier order_id="<?php echo $order->get_id(); ?>"]
	Your order is shipped with: [packeta_carrier_name order_id="<?php echo $order->get_id(); ?>"]
[/packeta_if_carrier]
```

### Usage in Email Templates

You can use these shortcodes in your WooCommerce email templates. To add them to your email templates:

1. Go to WooCommerce > Settings > Emails
2. Click on any email template you want to customize (e.g., "New Order", "Processing Order", etc.)
3. In the "Email content" section, you can add the shortcodes to customize the email content

For example:

```php
<h2>Order Details</h2>
<p>Order <?php echo $order->get_order_number(); ?></p>

[packeta_if_packet_submitted order_id="<?php echo $order->get_id(); ?>"]
	<h3>Shipping Information</h3>
	<p>Your tracking number is: [packeta_tracking_number order_id="<?php echo $order->get_id(); ?>"]</p>
	<p>Track your package: [packeta_tracking_url order_id="<?php echo $order->get_id(); ?>"]</p>
[/packeta_if_packet_submitted]

[packeta_if_pickup_point order_id="<?php echo $order->get_id(); ?>"]
	<h3>Pickup Point Information</h3>
	<p>Name: [packeta_pickup_point_name order_id="<?php echo $order->get_id(); ?>"]</p>
	<p>Address: [packeta_pickup_point_address order_id="<?php echo $order->get_id(); ?>"]</p>
	<p>City: [packeta_pickup_point_city order_id="<?php echo $order->get_id(); ?>"]</p>
	<p>ZIP: [packeta_pickup_point_zip order_id="<?php echo $order->get_id(); ?>"]</p>
	<p>Country: [packeta_pickup_point_country order_id="<?php echo $order->get_id(); ?>"]</p>
[/packeta_if_pickup_point]
```

Note: All shortcodes require the `order_id` parameter to work. If the order is not found or the required data is not available, the shortcodes will return empty strings.

## Credits

* 10up and their [WordPress.org Plugin Deploy](https://github.com/10up/action-wordpress-plugin-deploy) and [WordPress.org Plugin Readme/Assets Update](https://github.com/10up/action-wordpress-plugin-asset-update) Github Actions
