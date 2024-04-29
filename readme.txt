=== Packeta ===
Contributors: packeta
Tags: WooCommerce, shipping
Requires at least: 5.3
Tested up to: 6.5.2
Stable tag: 1.7.2
Requires PHP: 7.2
WC requires at least: 4.5
WC tested up to: 8.8.2
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
= 1.7.2 =
Changed: Instead of Guzzle client, we're using WordPress function to fetch feature flags from external API.
Fixed: Filtering payment gateways without shipping calculation.
Added: PHP 8.1-8.2 support.

= 1.7.1 =
Fixed: Malfunctioning import of previous version's settings.
Fixed: Warnings and/or missing country codes in country list.

= 1.7.0 =
Added: Option to set shipping prices including VAT.
Fixed: Fix plugin uninstallation for multisite.
Fixed: The plugin does not respect the user's language setting, but only the language in the global setting.
Added: The display of “Free” for the free shipping method in the checkout can be turned off in the settings.
Added: Age verification with CZ Home delivery carrier.
Fixed: Shipping price and currency are not being passed to the Pickup Points widget.
Updated: Improvements to the shipment information form.
Fixed: Not working saving of the customs declaration if the invoice issue date is incorrect.
Added: Displaying the status of the shipment for the order right after the shipment has been created in the administration.
Minor bug fixes.

= 1.6.4 =
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
