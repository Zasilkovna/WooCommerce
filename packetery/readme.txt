=== Packeta ===
Contributors: packeta
Tags: WooCommerce, shipping
Requires at least: 5.3
Tested up to: 5.7.2
Stable tag: 5.7.2
Requires PHP: 7.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

With the help of our official plugin, You can choose pickup points of Packeta and it's external carriers in all of Europe, or utilize address delivery for 25 countries in the European Union, straight from the cart in Your eshop. You can submit all of Your orders to Packeta with one click.

== Description ==

= Plugin functions: =

* the ability to choose a pickup place in Your cart using our widget v6
* delivery to Packeta pickup places (Czech republic, Slovakian republic, Hungary and Romania)
* delivery to pickup places of carriers all around Europe
* the ability to add/modify the packet weight and dimensions before submitting the packet to Packeta
* automatic sending of orders to Packeta with one click
* each delivery sent to Packeta will automatically show the tracking number with a link to a website with the shipment tracking
* the printing of labels, including direct carrier labels

= You can look forward to: =

* automatically updated information on the current packet status
* the ability to automatically change the order status according to the packet status
* the filling out of customs declarations and shipping of packets to countries outside of EU
* the creation of claim assistant packets
* printing of shipment lists
* the ability to change the pickup place of an already existing packet
* the option to choose a pickup place during the manual creation of the packet in administration

== Installation ==

* You can install the plugin in Your Wordpress administration: Plugins->Plugin installation->Upload plugin
* Activate the plugin in the WordPress menu "Plugins"

= Setting up of the module in WordPress Administration: =

* In the Packeta>Settings menu. Fill in the API password, sender, pick the label format and choose the payment method for cash on delivery.
* In the Packeta>Carrier settings menu, first update the list of carriers and countries, by clicking on the "Update the carrier list" button.
* In the Packeta>Carrier settings menu, choose the country, to which You want to deliver packets using Packeta and press the "Set up" button
* For each carrier fill out the following:
 * the name of the carrier in Your cart
 * the weight parameters (at least one)
 * payment for cash on delivery option (it is not required to fill out, if You don't charge a cash on delivery fee)
 * the limit for free delivery
* You can set the carrier as active by ticking the "Active carrier" box
* Save the changes by clicking the "Save changes" button. Each carrier settings have to be saved separately.
* In the WooCommerce>Settings>Delivery menu, either add to an existing zone or create a new one and as the delivery service choose the "Packeta Delivery Service" option
* If Your e-shop offers products which are considered "Adult only", then in the product details in the "Packeta" tab tick the "Age verification 18+" option. If there is at least one product in the order marked as needing age verification, then this information will be sent to Packeta during the packet creation. Age verification can be used only for packets sent to Packeta pickup places (Czech republic, Slovakian republic, Hungary and Romania).

== Frequently Asked Questions ==

= Is the plugin free? =

Yes. All functions of our plugin are completely free. No need to purchase any premium extensions.

= What are the minimal required versions of WordPress and PHP? =

In order to be able to use modern development procedures and continue to expand the functions of the plugin, it is necessary to run the plugin on WordPress 5.3+ and PHP 7.2 - 7.4. The plugin does not currently support PHP 8

= I'm missing a feature I would like to see, what should I do? =

We are constantly working on adding new features. You can find a list of features we are currently working on in the "You can look forward to" chapter. If there is a feature You would like to see added, that is missing in our list, then please contact us at technicka.podpora@zasilkovna.cz

= I have found a mistake in the plugin or need help with the installation or setting up of the plugin. =

Please contact us at technicka.podpora@zasilkovna.cz .

== Changelog ==
= 1.0.1 =
* Bugfix: Carrier name input width css rule to cover all carriers.

= 1.0.0 =
* Initial version
