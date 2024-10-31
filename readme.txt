=== Packeta ===
Contributors: packeta
Tags: WooCommerce, shipping
Requires at least: 5.5
Tested up to: 6.6.2
Stable tag: 1.8.4
Requires PHP: 7.2
WC requires at least: 4.5
WC tested up to: 9.3.3
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
* automatic sending of orders to Packeta with one click
* each delivery sent to Packeta will automatically show the tracking number with a link to a website with the shipment tracking
* the printing of labels, including direct carrier labels
* printing of shipment lists (AWB)
* 18+ age verification setting for your products

= You can look forward to: =

* automatically updated information on the current packet status
* the ability to automatically change the order status according to the packet status
* the filling out of customs declarations and shipping of packets to countries outside of the EU
* the creation of claim assistant packets
* the option to choose a pickup place during the manual creation of the packet in the administration

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

In order to be able to use modern development procedures and continue to expand the functions of the plugin, it is necessary to run the plugin on WordPress 5.5+ and PHP 7.2 - 8.2.

= I'm missing a feature I would like to see, what should I do? =

We are constantly working on adding new features. You can find a list of features we are currently working on in the "You can look forward to" chapter. If there is a feature you would like to see added, that is missing in our list, then please contact us at technicka.podpora@zasilkovna.cz

= I have found a mistake in the plugin or need help with the installation or set up of the plugin. =

Please contact us at technicka.podpora@zasilkovna.cz .

== Changelog ==
= 1.8.4 =
Fixed: Incorrect usage of a helper class causing an error when order with stored API error message is in the order list.

= 1.8.3 =
Added: Possibility to enable Tracy debugger for specific IP addresses.
Fixed: Correct display for Packeta Pick-up Point carrier in countries with external pickup points.

[See changelog for all versions](https://raw.githubusercontent.com/Zasilkovna/WooCommerce/main/changelog.txt)
