=== Packeta ===
Contributors: packeta
Tags: WooCommerce, shipping
Requires at least: 5.5
Tested up to: 6.8.2
Stable tag: 2.1.2
Requires PHP: 7.2
WC requires at least: 5.1
WC tested up to: 10.1.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Official plugin for selecting Packeta pickup points or address delivery and submitting orders directly from your e-shop.

== Description ==

This is the official plugin, that allows you to choose pickup points of Packeta and its external carriers in all of Europe, or utilize address delivery to 25 countries in the European Union, straight from the cart in your e-shop. Furthermore, you can also submit all your orders to Packeta with just one click.

= Plugin functions: =

* integration of widget v6 for selection of pickup points in the e-shop cart
* address validation in the cart for address delivery with our address-picking widget (Czech Republic, Slovakia)
* delivery to pickup points of Packeta (Czech Republic, Slovakia, Hungary, and Romania)
* delivery to pickup points of external carriers all over Europe
* the ability to fill in/change the weight and dimensions of the shipment before submitting it to Packeta
* the automatic submission of orders to Packeta
* after submitting an order to Packeta, each order gets an order number, which acts as a link to a URL with the tracking of the parcel
* label printing, including direct labels
* age verification (18+) can be configured individually for each product. The order will then require the customer to verify his age during the parcel pickup
* printing of the list of parcels
* support for High-Performance order storage (since WooCommerce version 7.9.0)
* support for block checkout
* in shipping zones, it is possible to set shipping methods for individual Packeta carriers
* tracking the status of shipments and automatic change of order status
* possibility to set maximum dimensions of the shipment for each carrier
* filling in customs declarations and sending shipments outside the EU
* possibility to create a complaint assistant for the delivered shipment

== Installation ==

* You can install the plugin either in your WordPress administration: Plugins->Plugin installation->Upload plugin or upload the "packetery" folder into the /wp-content/plugins/ 
* Activate the plugin in the WordPress menu "Plugins"
* Set up the plugin according to our user documentation
* If you update the Packeta plugin manually, you first need to completely delete the "packeta" folder and then upload the folder with the new version of the plugin.
  You should definitely not upgrade by copying the new version to the original folder.
  This could cause the original version to merge with the new one, which can cause the plugin to become completely non-functional.

== Frequently Asked Questions ==

= Is the plugin free? =

Yes. All functions of our plugin are completely free. No need to purchase any premium extensions.

= What are the minimum required versions of WordPress and PHP? =

In order to be able to use modern development procedures and continue to expand the functions of the plugin, it is necessary to run the plugin on WordPress 5.5+ and PHP 7.2 - 8.4.

= I'm missing a feature I would like to see, what should I do? =

We are constantly working on adding new features. If there is a feature you would like to see added, that is missing in our list, then please contact us at e-commerce.support@packeta.com

= I have found a mistake in the plugin or need help with the installation or set up of the plugin. =

Please contact us at e-commerce.support@packeta.com .

== Changelog ==
= 2.1.2 =
Fixed: Plugin no longer crashes on the orders list when a refunded order is present.

= 2.1.1 =
Fixed: Removing all transients and options upon uninstall.
Updated: Checkout data transient lifetime raised to the default value of WooCommerce.
Added: Backend checkout data validation when using block checkout.

= 2.1 =
Added: New Home page with plugin setup wizard.
Added: Tutorials (setup, order detail, orders list, customs declaration).
Added: Option to force database tables creation/update (do_action('packeta_create_tables')).
Added: New setting option: Hide Packeta logo in widget.
Updated: Plugin uninstall no longer deletes data and settings (removed only if PACKETERY_REMOVE_ALL_DATA=true is defined); a warning is shown on deactivation.
Updated: Bulk printing of carrier labels – for external carriers only their labels can be generated.
Updated: Final shipment statuses (e.g. delivered, canceled) are no longer displayed.
Fixed: Missing widget in order detail for universal shipping zone.

= 2.0.11 =
Fixed: Correct loading of shipping methods in case of premature payment plugin queries.

= 2.0.10 =
Fixed: Introduced carrier and shipping method caching to prevent excessive memory usage and performance problems.

[See changelog for all versions](https://raw.githubusercontent.com/Zasilkovna/WooCommerce/main/changelog.txt)
