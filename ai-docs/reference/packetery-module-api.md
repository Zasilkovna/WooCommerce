---
title: "woocommerce — packetery-module-api module"
repo: woocommerce
module: packetery-module-api
generated-by: skill:generate-docs@0.3.5
source-commit: 2c742814
last-generated: 2026-09-22
covers: [src/Packetery/Module/Api]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-module-api, type-reference]
---

Repo: woocommerce · Module: packetery-module-api · Type: reference · Status: current

## packetery-module-api: purpose

packetery-module-api gives the browser six internal REST routes of the plugin. The checkout routes
keep what the customer selected in the Packeta widget, because the WooCommerce session is not
available at that moment [VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#registerRoutes].
The order routes save what the administrator writes in the modal windows of the order detail
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal].

packetery-module-api serves the plugin alone. Every route lives in one REST namespace of Packeta,
and no route is part of the public API of the shop
[VERIFY: src/Packetery/Module/Api/BaseRouter.php#registerRoute]. The routers also build the URL of
each route for the scripts of the plugin
[VERIFY: src/Packetery/Module/Api/Internal/CheckoutRouter.php#getSaveSelectedPickupPointUrl]. The
module holds 6 files, 391 lines of logic and 21 public methods.

> ⚠ add business context (elicitation)

## packetery-module-api: public interface

packetery-module-api exposes six REST routes in the namespace `packeta/internal`. Every route
accepts the editable methods of WordPress, which are `POST`, `PUT` and `PATCH`.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Route registration | `registerRoutes` | Registers the routes of the two controllers at one place | [VERIFY: src/Packetery/Module/Api/Registrar.php#registerRoutes] |
| Pickup point | `/checkout/save-selected-pickup-point` | Saves the pickup point of the widget for the selected rate | [VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#saveSelectedPickupPoint] |
| Validated address | `/checkout/save-validated-address` | Saves the address that the customer validated in the widget | [VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#saveValidatedAddress] |
| Car delivery | `/checkout/save-car-delivery-details` | Saves the car delivery selection of the customer | [VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#saveCarDeliveryDetails] |
| Saved data removal | `/checkout/remove-saved-data` | Removes the data of one carrier, or all stored checkout data | [VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#removeSavedData] |
| Order modal | `/order/save-modal` | Validates and saves the weight, the size, the value and the delivery date of an order | [VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal] |
| Stored until | `/order/save-stored-until` | Sends a longer storage time to Packeta and saves it on the order | [VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveStoredUntil] |
| Route URL | `getSaveModalUrl`, `getSaveStoredUntilUrl` | Build the URL that the admin scripts call | [VERIFY: src/Packetery/Module/Api/Internal/OrderRouter.php#getSaveModalUrl] |
| Route path | `getRoute` | Builds the path of a route from the base of the router | [VERIFY: src/Packetery/Module/Api/BaseRouter.php#getRoute] |

The two order routes need the `edit_posts` capability, and they check no nonce
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#registerRoutes]. The four checkout
routes have an open permission callback, so any visitor can call them
[VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#registerRoutes].

## packetery-module-api: data model

packetery-module-api owns no storage of its own. The checkout routes write to the transient of the
checkout module, and the order routes write to the order entity.

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| checkout data | `packetery_rate_id` | request key | The key under which the routes store the selection of one carrier | [VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#RATE_ID] |
| checkout data | pickup point attributes | request body | The route takes the attribute list of the order module and stores the values it knows | [VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php:170] |
| order | `packeteryWeight`, `packeteryOriginalWeight` | request body | Weight of the packet and the weight that WooCommerce computed | [VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal] |
| order | `packeteryLength`, `packeteryWidth`, `packeteryHeight` | request body | Size of the packet. The module converts the values to millimetres | [VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal] |
| order | `packeteryCOD`, `packeteryValue`, `hasPacketeryAdultContent`, `packeteryDeliverOn` | request body | Manual values of the packet that the administrator can change | [VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal] |
| order | `packeteryStoredUntil` | request body | New storage date of the packet | [VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveStoredUntil] |

The modal route answers with the new values, with the state of the order and with the HTML fragment
of the grid cell, so the admin page needs no reload
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal]. The storage date route
answers with the new date and with the state of the order, and it sends no fragment
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveStoredUntil]. A validation error, an
order that does not load, a failed save and a fault of the Packeta API each give the status code
400 with its own error code
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveStoredUntil].

## packetery-module-api: dependencies

packetery-module-api depends on the modules that own the data it writes. Structured lines:

references → packetery-core
references → packetery-module-order
references → packetery-module-checkout
references → packetery-module-options
references → packetery-module-forms
references → packetery-module-framework
references → packetery-module-exception

The checkout routes take the attribute keys of `packetery-module-order` and write through the
storage of `packetery-module-checkout`
[VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php:170]. The order routes validate
with the forms of `packetery-module-forms` and with the order validator of `packetery-core`, and
they save through the order repository
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal]. The size of the packet
goes through the unit conversion of `packetery-module-options`
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal]. The storage date leaves
the shop through the packet operation of `packetery-module-order`, which calls Packeta
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveStoredUntil].

## packetery-module-api: known limitations

packetery-module-api has these limitations evidenced in the code. The four checkout routes accept a
request from any visitor, because their permission callback always returns true
[VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#registerRoutes]. A request with an
invalid data structure is not stored, and the route still answers with the status code 200, so the
caller learns nothing about the result
[VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php:170].

The two order routes check the `edit_posts` capability and no nonce
[VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#registerRoutes]. The key of the
stored checkout data is a hardcoded request parameter
[VERIFY: src/Packetery/Module/Api/Internal/CheckoutController.php#RATE_ID]. The module contains no
TODO comment and no FIXME comment.
