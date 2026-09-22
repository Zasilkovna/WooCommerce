---
title: "woocommerce — packetery-core module"
repo: woocommerce
module: packetery-core
generated-by: skill:generate-docs@0.3.5
source-commit: 6791c048
last-generated: 2026-09-22
covers: [src/Packetery/Core]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-core, type-reference]
---

Repo: woocommerce · Module: packetery-core · Type: reference · Status: current

## packetery-core: purpose

packetery-core is the namespace `Packetery\Core`. The module holds the shipping domain of the
plugin and the clients that speak to the Packeta API. The module keeps the entities of a shipment, the rules that decide whether a shipment can be
submitted, and the transport code that sends it. packetery-core knows nothing about WordPress or
WooCommerce: it declares the services it needs as interfaces, and another module supplies the
implementations [VERIFY: src/Packetery/Core/Interfaces/IWebRequestClient.php#post]. The module
carries no persistence layer, no hooks and no user interface.

The separation has one practical effect. A change of the Packeta API contract stays inside
packetery-core, and a change of the WordPress integration stays outside it. The SOAP client holds the request class and the response class of every
API operation, and the modules of the plugin call those operations by name
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#createPacket].

> ⚠ add business context (elicitation)

## packetery-core: public interface

packetery-core exposes its behaviour as PHP classes. The module registers no route and no command.
The table lists the classes that other modules construct and call.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| `Api\Soap\Client` | `createPacket( array $requestData )` | Sends one shipment to the Packeta SOAP API and returns the created packet or a fault. | [VERIFY: src/Packetery/Core/Api/Soap/Client.php#createPacket] |
| `Api\Soap\Client` | `packetStatus( Request\PacketStatus $request )` | Reads the current delivery status of one packet. | [VERIFY: src/Packetery/Core/Api/Soap/Client.php#packetStatus] |
| `Api\Soap\Client` | `packetsLabelsPdf( $request )` | Returns label documents for a set of packets as PDF data. | [VERIFY: src/Packetery/Core/Api/Soap/Client.php#packetsLabelsPdf] |
| `Api\Rest\PickupPointValidate` | `validate( $request )` | Validates a chosen pickup point against the widget API. | [VERIFY: src/Packetery/Core/Api/Rest/PickupPointValidate.php#validate] |
| `Validator\Order` | `isValid( $order )` | Decides whether an order has the data that a submission needs. | [VERIFY: src/Packetery/Core/Validator/Order.php#isValid] |
| `Rounder` | `round( float $amount, int $roundingType, int $precision )` | Rounds a value up, down or not at all, by the rounding type constants. | [VERIFY: src/Packetery/Core/Rounder.php#ROUNDING_TYPES] |
| `CoreHelper` | `getTrackingUrl( $packetId )` | Builds the public tracking address of one packet. | [VERIFY: src/Packetery/Core/CoreHelper.php#getTrackingUrl] |

The SOAP client reports a failure in two forms. A fault of the API is turned into a fault
identifier, and a rejected request carries the validation errors of the API
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#getValidationErrors]. The validator does not throw:
it collects translation keys such as `ERROR_TRANSLATION_KEY_WEIGHT`, and the calling module decides
how to show them [VERIFY: src/Packetery/Core/Validator/Order.php#ERROR_TRANSLATION_KEY_WEIGHT].

## packetery-core: dependencies

packetery-core depends on the Packeta API and on interfaces that another module implements.
Structured lines:

calls → packeta-api (sync, SOAP/XML)
calls → packeta-widget-api (sync, REST/JSON)
called from → woocommerce

packetery-core sends shipment data, label requests and status queries to the SOAP API of Packeta
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#createShipment]. The module reads the WSDL address
and the API password from its constructor, so the values live outside the module
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#setApiPassword].

packetery-core declares four interfaces. Three of them get their implementation outside the module:
`IWebRequestClient` performs the REST call
[VERIFY: src/Packetery/Core/Interfaces/IWebRequestClient.php#post], `ILogger` records what happened
[VERIFY: src/Packetery/Core/Log/ILogger.php#add], and `ValidatorTranslationsInterface` gives the
texts of the validation errors
[VERIFY: src/Packetery/Core/Interfaces/ValidatorTranslationsInterface.php#get]. The fourth,
`ILabelResponse`, has its implementation inside the module
[VERIFY: src/Packetery/Core/Api/Soap/ILabelResponse.php#ILabelResponse]. The WordPress module supplies the
other three, which keeps the HTTP stack and the database out of packetery-core.

## packetery-core: data model

packetery-core works with the entities of one shipment. The entities are plain PHP objects. The
module stores nothing: another module maps them to the database of WordPress.

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| Order | `finalWeight` | float, nullable | The weight that the submission uses when it is set. | [VERIFY: src/Packetery/Core/Entity/Order.php#getFinalWeight] |
| Order | `pickupPoint` | PickupPoint, nullable | Set for a pickup point delivery, empty for an address delivery. | [VERIFY: src/Packetery/Core/Entity/Order.php#getPickupPoint] |
| Order | `customsDeclaration` | CustomsDeclaration, nullable | Required for a shipment that leaves the customs area. | [VERIFY: src/Packetery/Core/Entity/Order.php#getCustomsDeclaration] |
| PacketStatus | `DELIVERED` | string constant | One of the delivery states that the API reports. | [VERIFY: src/Packetery/Core/Entity/PacketStatus.php#DELIVERED] |
| Carrier | `maxWeight` | float | The weight limit that the carrier accepts. | [VERIFY: src/Packetery/Core/Entity/Carrier.php#getMaxWeight] |
| Size | — | entity | Holds length, width and height of one shipment. | [VERIFY: src/Packetery/Core/Entity/Size.php#Size] |

The pickup point providers build the vendor structure that the widget needs. One compound provider
holds several vendor codes of one country
[VERIFY: src/Packetery/Core/PickupPointProvider/CompoundProvider.php#CompoundProvider].

## packetery-core: external links

packetery-core communicates outside the repository with two services of Packeta.

calls → packeta-api (sync, SOAP/XML)
calls → packeta-widget-api (sync, REST/JSON)

The SOAP client creates packets, cancels them, reads their status and downloads label and barcode
documents [VERIFY: src/Packetery/Core/Api/Soap/Client.php#cancelPacket]. The address of the service
comes from the constructor argument `wsdlUrl`, so the module holds no endpoint value
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#__construct].

The REST client validates a pickup point that the customer selected in the widget. The endpoint is
a constant of the class, and the API key arrives through a factory method
[VERIFY: src/Packetery/Core/Api/Rest/PickupPointValidate.php#createWithValidApiKey]. The request
and the response have their own classes, so the payload shape is visible in the code
[VERIFY: src/Packetery/Core/Api/Rest/PickupPointValidateRequest.php#PickupPointValidateRequest].

## packetery-core: known limitations

packetery-core has these limitations evidenced in the code.

The compound pickup point provider ignores vendor identifiers, which the code records as an open
point [VERIFY: src/Packetery/Core/PickupPointProvider/CompoundProvider.php#CompoundProvider]. The
factory that builds a compound carrier collection does not take age verification into account, so a
pickup point that cannot verify age is treated like any other
[VERIFY: src/Packetery/Core/PickupPointProvider/CompoundCarrierCollectionFactory.php#CompoundCarrierCollectionFactory].

The SOAP client has no retry and no timeout of its own: it relies on the SOAP extension of PHP
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#createStorageFile]. The order validator checks
presence and shape of the data, not the business rules of the carrier, so a valid order can still
be rejected by the API [VERIFY: src/Packetery/Core/Validator/Order.php#isValid].
