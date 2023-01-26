=== Packeta ===
Contributors: packeta
Tags: WooCommerce, shipping
Requires at least: 5.3
Tested up to: 6.0
Stable tag: 1.4
Requires PHP: 7.2
WC requires at least: 4.5
WC tested up to: 6.5.1
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

== Frequently Asked Questions ==

= Is the plugin free? =

Yes. All functions of our plugin are completely free. No need to purchase any premium extensions.

= What are the minimum required versions of WordPress and PHP? =

In order to be able to use modern development procedures and continue to expand the functions of the plugin, it is necessary to run the plugin on WordPress 5.3+ and PHP 7.2 - 8.0.

= I'm missing a feature I would like to see, what should I do? =

We are constantly working on adding new features. You can find a list of features we are currently working on in the "You can look forward to" chapter. If there is a feature you would like to see added, that is missing in our list, then please contact us at technicka.podpora@zasilkovna.cz

= I have found a mistake in the plugin or need help with the installation or set up of the plugin. =

Please contact us at technicka.podpora@zasilkovna.cz .

== Changelog ==
= 1.4 =
Fixed: Performance optimization for loading the list of orders
Fixed: Label print edge case error
Fixed: Woocommerce Currency Switcher compatibility
Fixed: ManageWP Worker plugin hack fix to be compatible with ManageWP service
Updated: Support for Divi templates made more universal
Updated: Packaging weight plugin option re-labeled
Updated: removal of widget button container class "place-order", providing compatibility with WP Germanized plugin
Added: Possibility to alter optional packet parameters before submission to Packeta API
Added: Custom order number support
Added: Filter for altering packet data
Added: Compatibility with Back In Stock Notifier plugin
Added: Age verification fee
Added: Possibility to cancel order submission to Packeta
Added: Shipping checkout rate per product limitation
Added: Order - log relation and related user interface
Added: Dimensions (length, width, height) to edit modal panel at order list
Added: Multisite support
Added: Packeta log auto-deletion via cron
Added: Dashboard support widget
Added: Tax to COD surcharge and Age verification fee
Added: Stockie theme support
Added: Calculation of exceeding the Free shipping limit without discount from coupons
Added: Free shipping limit is triggered by cart price including VAT
Added: Support for payment plugins of 3rd parties e.g. TORET GoPay etc.
Added: Packeta button in main menu moved by default to the last position. Final order can be affected by other installed plugins.
Added: Support for CURCY - Multi Currency for Woocommerce plugin
Added: Limited support for "Multivendor Marketplace Solution for WooCommerce - WC Marketplace" (WCMP) plugin
Added: Packet status synchronization
Added: Filter to exclude order statuses when filtering orders
Added: Price conversion filter

= 1.3.2 =
* Fixed: Other shipping methods support

= 1.3.1 =
* Fixed: Order product loading for age verification service
* Updated: For each order is a separate Packeta log
* Added: Plugin options export extended with installed plugins
* Added: Admin order grid weight column

= 1.3.0 =
* Fixed: Some Elementor and some Divi checkouts are now supported
* Fixed: Plugin translations
* Added: currency-switcher.com compatibility
* Added: API Log - added packetAttributes if the packet was successfully sent
* Added: Possibility to choose widget button checkout location in plugin settings
* Updated: Checkout widget button after transport methods styling updated

= 1.2.6 =
* Added: New plugin setting that enables replacing shipping address with pickup point address
* Updated: Label options naming in Packeta plugin options page
* Updated: Show notification when log page doesn’t contain any logs
* Updated: Minor code issues resolved
* Fixed: Actions run on deleted order now give proper error messages
* Fixed: Slovak and czech translations related to label printing
* Fixed: Plugin now ignores empty order item product when calculating order weight
* Fixed: The display of filter links has been limited to the order overview page
* Fixed: Delete order from custom table after deleting woocommerce order
* Fixed: Delete all custom table records linked to permanently deleted orders
* Fixed: Carrier weight rule duplicate validation
* Fixed: Order modal weight field validation

= 1.2.5 =
* Fixed: Packeta plugin is now compatible with Plugins making javascripts load asynchronously in checkout

= 1.2.4 =
* Updated: new HD widget library URL

= 1.2.3 =
* Added: Packeta admin menu icon
* Added: Link to documentation to show on "Installed plugins" page
* Fixed: Packeta plugin no longer throws error during upgrade with DEBUG_MODE enabled

= 1.2.2 =
* Fixed: Plugin main file directory retrieval updated due to renaming packetery.php to packeta.php
* Fixed: Removed tracy examples with errors to pass wordpress.org sniffer

= 1.2.1 =
* Updated: First version to be released at wordpress.org

= 1.2.0 =
* Added: Primary key for carrier table
* Updated: Packeta order meta data moved from posts to custom table
* Updated: Logger uses custom database table
* Updated: Only 3 decimal places are accepted for order weight
* Updated: JavaScript and CSS files are now loading conditionally
* Fixed: Packeta checkout validators now trigger only if Packeta shipping is selected
* Fixed: Label print page now shows the correct number of labels that will be printed
* Fixed: Widget button now shows even if no country was selected on checkout page load
* Fixed: Javascript dependencies added where missing
* Fixed: Non-Packeta order submission to Packeta API no longer creates PHP error
* Fixed: Label printing now accepts trashed orders
* Fixed: Packeta order modal now dynamically calculates weight if no weight is provided
* Fixed: Packeta logger now supports emote characters
* Fixed: Deactivating WooCommerce plugin while having Packeta plugin activated no longer crashes the entire site

= 1.1.1 =
* Fixed: Overweight orders now never have shipping for free
* Fixed: Packet API submissions now always include order currency

= 1.1.0 =
* Added: Information about the count of printed labels and "back" link added to label offset setting form
* Added: Possibility to print the same labels again in a single session in the label print page
* Added: Possibility to print single label from order list
* Added: Possibility to submit single order to Packeta from order list
* Added: Admin pickup point picker in order detail
* Added: Packaging weight in plugin settings
* Added: Checkout address validation
* Added: Possibility to edit order weight in order list
* Added: List of active carriers added to carrier settings section
* Added: Shipment lists printing
* Added: Sender verification in plugin settings
* Added: SK translation
* Updated: Carrier settings interface
* Updated: Packetery buttons in order list
* Updated: Flash messages are always first in message stack

= 1.0.7 =
* Fixed: The label print page displays a message to the user if no suitable orders are selected
* Fixed: Some environments caused error due PHPDocs being removed by OPCache
* Fixed: Order list filters can now be combined

= 1.0.6 =
* Added: Default cash-on-delivery surcharge
* Added: Cash-on-delivery surcharge was separated from shipping cost and is shown in order fees during checkout
* Updated: Carrier settings page errors highlighted
* Fixed: Only available payment methods are available for selection in plugin settings

= 1.0.5 =
* Updated: Sender description
* Fixed: Cash-on-delivery payment method is always available for selection in Packeta plugin settings
* Fixed: Checkout refresh on payment method change happens only if the value really changes

= 1.0.4 =
* Added: If the creation of the carrier table fails, the user is informed and error is logged
* Updated: Settings export expanded to be even more helpful
* Fixed: Inputs in the cart implemented so as not to affect the appearance
* Fixed: Packeta logo CSS in cart made simple and compatible
* Removed: Dependency on intl library

= 1.0.3 =
* Fixed: Use of pickup point method in cart with billing only setting enabled

= 1.0.2 =
* Fixed: Broken relative URLs in multiple places

= 1.0.1 =
* Added: Settings export to help solve various issues
* Updated: Logger no longer deletes older records
* Fixed: User no longer sees messages from other sessions
* Fixed: Logger handles double quotes in error messages
* Fixed: Corrected the count of orders in filtering links
* Fixed: Save carrier's maximum weight correctly as a float
* Fixed: Carrier name input width css rule to cover all carriers
* Fixed: Pickup point id will be stored as a string, because external carriers may require it
* Fixed: Exception handling during CreatePacket API call - faultstring used when no detail property is returned
* Fixed: CLI error - plugin now does not bootstrap in CLI environment
* Fixed: Exception when HTTP response headers are already sent - Plugin now does not bootstrap in such case

= 1.0.0 =
* Initial version
