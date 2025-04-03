=== Packeta ===
Contributors: packeta
Tags: WooCommerce, shipping
Requires at least: 5.5
Tested up to: 6.7.2
Stable tag: 2.0.2
Requires PHP: 7.2
WC requires at least: 5.1
WC tested up to: 9.7.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

This is the official plugin, that allows you to choose pickup points of Packeta and its external carriers in all of Europe, or utilize address delivery to 25 countries in the European Union, straight from the cart in your e-shop. Furthermore, you can also submit all your orders to Packeta with just one click.

= Plugin functions: =

* the ability to choose a pickup place in your cart using our widget v6
* the ability to change the destination pickup point of an existing order
* the option to allow checkout address validation using our widget HD
* delivery to Packeta pickup places (Czech Republic, Slovakian Republic, Hungary, and Romania)
* delivery to pickup places of carriers all around Europe
* the ability to add/modify the packet weight and dimensions before submitting the packet to Packeta
* automatic sending of orders to Packeta
* each delivery sent to Packeta will automatically show the tracking number with a link to a website with the shipment tracking
* the printing of labels, including direct carrier labels
* printing of shipment lists (AWB)
* 18+ age verification setting for your products
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
= 2.0.2 =
Fixed: Fixed wp-cli compatibility issue.
Fixed: In rare cases, translating the plugin into other languages did not work. However, we are not entirely sure that this fix will successfully resolve the issue.
Fixed: The plugin will no longer display an error if $_POST contains invalid values from another plugin.
Fixed: The plugin will no longer display an error if the WP methods delete_transient() or set_transient() return a different return value than the one specified in the PHPDoc.

= 2.0.1 =
Fixed: In certain situations, there was an error when displaying the "Log" page
Fixed: In the order list, there was an error when displaying the date in the "saved to" column for orders to a pickup point if the e-shop uses shipment tracking

[See changelog for all versions](https://raw.githubusercontent.com/Zasilkovna/WooCommerce/main/changelog.txt)
