=== Packeta ===
Contributors: packeta
Tags: WooCommerce, shipping
Requires at least: 5.3
Tested up to: 6.3
Stable tag: 1.6.4
Requires PHP: 7.2
WC requires at least: 4.5
WC tested up to: 8.0.0
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

In order to be able to use modern development procedures and continue to expand the functions of the plugin, it is necessary to run the plugin on WordPress 5.3+ and PHP 7.2 - 8.0.

= I'm missing a feature I would like to see, what should I do? =

We are constantly working on adding new features. You can find a list of features we are currently working on in the "You can look forward to" chapter. If there is a feature you would like to see added, that is missing in our list, then please contact us at technicka.podpora@zasilkovna.cz

= I have found a mistake in the plugin or need help with the installation or set up of the plugin. =

Please contact us at technicka.podpora@zasilkovna.cz .

== Changelog ==
== 1.6.4 =
Fixed: A user, who was not logged in, could be shown information about a selected pickup point after loading into the checkout, even if the user had not yet selected a pickup point.
= 1.6.3 =
Added: packeta_widget_weight filter
Added: In the case of free shipping, the information "Free" is now displayed after the name of the shipping method.

= 1.6.2 =
Fixed: In some cases (depending on the hosting settings), the value of the shipment or COD for the sent shipment was incorrectly displayed in the API Log.
Fixed: In order emails or on the thank-you page, the header "Packeta" is no longer displayed, if no information about pickup points is available.
Fixed: When sending some types of emails (e.g. forgotten password), an error occurred while sending them.
Update: Parameters passed to the Orders endpoint response were moved from order_meta directly to the shipping method. At the same time, a new parameter “point_name” was added.

= 1.6.1 =
Added: New filters have been added to display information in e-mail. In the plugin settings, it is possible to select the filter that will be used. Emails now show a tracking number with a link to track your shipment online.
Fixed: Treated the situation when a country where we do not have internal delivery points is selected for the order.

= 1.6.0 =
Added: High Performance Order Storage (Custom Order Table) feature support.
Added: Claim assistant support.
Added: Customs declarations support.
Added: REST API support - Packeta specific information added to shop order object.
Added: Selected pickup point or validated address is saved using AJAX, which increases compatibility with non-standard templates and plugins.
Added: Ability to disallow checkout payment methods in carrier settings
Added: Possibility to prepare packet labels on order detail.
Updated: Surname is no longer required to send a shipment to Packeta. If the order does not meet the conditions for sending shipment, specific validation errors are now listed in the Packeta log.
Updated: Periodic tasks for deleting log records and synchronizing shipment states were implemented asynchronously using ActionScheduler. You can now find all scheduled actions in WooCommerce - Status - Scheduled Actions.
Updated: Carrier property in Order entity made non-nullable.
Updated: Automatic prefixing of vendor dependencies.
Fixed: Correct logging of errors that may occur when updating the table of orders or carriers.
Fixed: Repeated packet synchronization now works correctly.
Fixed: Packet auto submitter now correctly handles orders with empty payment methods.
Fixed: API error on order grid no longer shows for forced packet cancellation.
Fixed: Checkout validation no longer triggers warning for empty checkout data.
Fixed: Sender validation error no longer triggers more than once during fresh plugin setup.
Fixed: Several minor bugs.

= 1.5.4 =
Fixed: An error that would occur when opening order detail for orders to external carriers pickup points after changing the delivery address.
Added: The list of parameters, which are being logged into the console of the web browser now also contains the API key.

= 1.5.3 =
Added: A filter to modify the HTML generated for use in email.
Updated: When changing the delivery point in the administration, we will not calculate and display the shipping price in the widget, because it is already stored in the order.
Fixed: The shipment number is now a string so that API interaction works correctly when using 32-bit PHP.
Fixed: Proper indentation of the Packeta header from WordPress notices.

= 1.5.2 =
Fixed: Displaying an order with a shipping method that doesn't have coupons enabled, with free shipping, and with a coupon used now doesn't throw an error.
Fixed: For international shipping, the wrong shipping currency was being passed to the widget.
Fixed: The plugin stopped working if the delivery country was changed in the order detail to a country not supported by the selected shipping method.
Fixed: Failed communication with the Packeta's internal API will no longer result in an error.

= 1.5.1 =
Updated: Viewing the log and printing labels is now possible with the manage_woocommerce permission, which is part of the "Shop Manager" role.
Updated: We are extending the packeta_shipping_price filter with another parameter (carrier ID, free shipping limit, prices for individual weight limits). Using this filter, you can set a new shipping price, for example, according to the price of the order.
Updated: It is no longer possible to select today's date in the calendar in the "postponed delivery" function.
Fixed: When using a payment plugin with a hyphen in its name (e.g. gopay-inline), our plugin crashed. Now everything works as expected.
Fixed: If the internal API of Packeta is unsuccessfully called (e.g. due to firewall settings), the plugin will no longer crash.

= 1.5.0 =
Added: In the list of orders, an exclamation mark icon is now displayed, which informs about an unsuccessful action (sending/cancelling a shipment, printing a label).
Added: Possibility to automatically open widget when shipping is selected in the checkout.
Added: Automatic order submission.
Added: Possibility to auto-change order status after packet submission and after auto-submission.
Added: Possibility to verify the delivery address in order detail in administration.
Added: It is now possible to round the cash on delivery value when sending a shipment to our system.
Added: It is now possible to send a shipment also from the order details in the administration.
Added: For each category, it is possible to disable specific Packeta carriers.
Added: The plugin now supports free shipping coupons. It is possible to set whether it will also apply to surcharges with specific carriers.
Added: If you do not have a weight set for the products, you can now set a default shipment weight.
Added: If the carrier does not support cash on delivery, it is now not possible to choose a cash on delivery payment at the checkout.
Added: You can now set a planned delivery for the order (the shipment will not be delivered before the specified date).
Added: A planned task to automatically download the list of carriers. The changes made in the list of carriers are now stored in the log.
Updated: The icon for sending the order to Packeta is no longer displayed if all mandatory parameters (e.g. weight) have not been entered for the order.
Updated: Improved compatibility with templates that modify the checkout (e.g. Elementor). In the past it happened that with some more complex third party templates, it was not possible to complete the order.
Updated: The plugin now remembers the filter settings in the list of orders even after some action has been taken (e.g. sending a shipment).
Updated: Some translations have been updated.
Fixed: The information about sending the shipment was written twice in the log. Now it only logs in correctly once.

= 1.4.3 =
* Added: Database server version to options export

= 1.4.2 =
* Added: Shop url in settings export
* Added: Checkout widget language filter packeta_widget_language
* Updated: logging widget options to console, appIdentity update
* Fixed: Product-carrier limitation now works properly (bug found in 1.4.1)
* Added: Packet status tracking options page success message
* Added: Link to a questionnaire at the dashboard
* Fixed: Widget backwards compatibility break
* Updated: External carrier pickup point detail link is no longer displayed
* Fixed: The translations have been modified to load correctly from wordpress.org

= 1.4.1 =
Added: New filter packeta_shipping_price. With the packeta_shipping_price filter, you can adjust the shipping price shown during checkout.
Fixed: checking return type of product get_meta.
Fixed: Product-carrier limitation user interface for product creation.
Fixed: Show all enabled payment methods in plugin settings.
Fixed: Product-carrier limitation.
Fixed: Checkout widget button visibility handling on no customer address filled.
Updated: Filter packeta_create_packet updated to accept array of packet attributes instead of order entity.
Updated: Packet status tracking interface and default values.
Updated: Refactor of function that gets all available packeta shipping methods.
Updated: small edit of translation strings.

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
