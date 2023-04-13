# Module for WordPress/WooCommerce

This is the official plugin, that allows you to choose pickup points of Packeta and its external carriers in all of Europe, or utilize address delivery to 25 countries in the European Union, straight from the cart in your e-shop. Furthermore, you can also submit all your orders to Packeta with just one click.

### Download module

[https://wordpress.org/plugins/packeta](https://wordpress.org/plugins/packeta)

#### Supported languages:

- czech
- slovak
- english

#### Supported versions

- PHP: 7.2 - 8.0
- WordPress 5.3+
- WooCommerce 4.5+

#### Functions provided

- Integration of [widget v6](https://widget.packeta.com/v6) for selection of pickup points in the e-shop cart.
- address validation in the cart for address delivery with our widget HD
- delivery to pickup-points of Packeta (Czech republic, Slovakia, Hungary and Romania)
- delivery to pickup points of external carriers all over Europe
- the ability to fill in/change the weight and dimensions of the order before submitting it to Packeta
- the automatic submission of orders to Packeta in one click
- after submitting an order to Packeta, each order gets an order number which acts as a link to a website with the tracking of the parcel
- label printing, including direct labels
- age verification for 18+, which can be setz up for every product. The order will then require the customer to verify his age during the parcel pickup
- printing of the list of parcels

#### Filters

WP Filters are used to easily alter preselected system behaviors.

To register filter edit wc-includes/functions.php and add your PHP code after all PHP file includes.

##### Order status filtering

To filter additional orders from Packeta order list when applying Packeta filter, use following sample code.

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
##### Filter shipping rate cost

To update packeta shipping rate cost in checkout, you can use the following code inserted into ```wp-includes/functions.php```.

```
add_filter( 'packeta_shipping_price', function( $price, $filterParameters ) {
	return $price * 1.5;
});
```

In the `$filterParameters` variable, there are available following keys:
* `carrier_id` - Either numeric carrier id from the official feed, `zpointxx` for all Packeta pickup points, `xxzpoint` for internal Packeta pickup points, `xxzbox` for Z-BOXes, or `czalzabox` for AlzaBoxes in Czech Republic, where `xx` is lowercase two-letter country code of country with Packeta pickup points.
* `cart_price_including_tax` - Cart price including tax.
* `free_shipping_limit` - Free shipping limit.
* `weight_limits` - Array of weight limits used by internal method to compute the price.

##### Checkout widget language filter

Since 1.4.2. To set widget language in checkout, you can use the following code inserted into ```wp-includes/functions.php```.

```
add_filter( 'packeta_widget_language', static function ( string $language ): string {
	return 'hu';
} );
```

You can find description of the attributes in the [official documentation](https://docs.packetery.com/03-creating-packets/06-packetery-api-reference.html#toc-packetattributes).

## Credits

* 10up and their [WordPress.org Plugin Deploy](https://github.com/10up/action-wordpress-plugin-deploy) and [WordPress.org Plugin Readme/Assets Update](https://github.com/10up/action-wordpress-plugin-asset-update) Github Actions
