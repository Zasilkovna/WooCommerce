=== Packeta ===
Contributors: packeta
Tags: WooCommerce, shipping
Requires at least: 5.5
Tested up to: 6.8.1
Stable tag: 2.0.7
Requires PHP: 7.2
WC requires at least: 5.1
WC tested up to: 9.9.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

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
= 2.0.7 =
Updated: Enabled age verification for Home delivery Slovakia.

= 2.0.6 =
Fixed: Checkout no longer fails to render in case no valid tax rates are found.

= 2.0.5 =
Updated: The payment method restriction set for the carrier has been implemented in the block checkout.
Fixed: Packeta information is added to emails even when the order status is changed in administration.

[See changelog for all versions](https://raw.githubusercontent.com/Zasilkovna/WooCommerce/main/changelog.txt)
