---
title: "woocommerce — dependencies and external links"
repo: woocommerce
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: 4034cda7
last-generated: 2026-09-22
covers: [composer.json, packeta.php, src/Packetery/Core/Api, src/Packetery/Module/Carrier/Downloader.php]
confidence: draft
tags: [ai-generated, repo-woocommerce, type-dependencies]
---

Repo: woocommerce · Module: — · Type: dependencies · Status: current

## woocommerce: links outside the repo

woocommerce communicates with three services outside its own code, and all three belong to Packeta.

calls → packeta-soap-api (sync, SOAP)
calls → packeta-widget (sync, REST)
calls → packeta-pickup-point-api (sync, REST)

| Counterpart | Direction | Sync/async | Protocol | Where in code | Anchor |
|---|---|---|---|---|---|
| packeta-soap-api | calls | sync | SOAP | `src/Packetery/Core/Api/Soap/Client.php` | [VERIFY: src/Packetery/Core/Api/Soap/Client.php#createPacket] |
| packeta-widget | calls | sync | REST and HTTPS | `src/Packetery/Core/Api/Rest/PickupPointValidate.php` | [VERIFY: src/Packetery/Core/Api/Rest/PickupPointValidate.php#validate] |
| packeta-pickup-point-api | calls | sync | REST | `src/Packetery/Module/Carrier/Downloader.php` | [VERIFY: src/Packetery/Module/Carrier/Downloader.php#run] |
| packeta-widget | calls | sync | HTTPS | `src/Packetery/Module/Checkout/CheckoutSettings.php` | [VERIFY: src/Packetery/Module/Checkout/CheckoutSettings.php#createSettings] |
| packeta-widget | calls | sync | REST | `src/Packetery/Module/Order/PickupPointValidator.php` | [VERIFY: src/Packetery/Module/Order/PickupPointValidator.php#validate] |

The plugin creates, cancels and tracks packets over `packeta-soap-api`, and it downloads the label
documents and the handover protocol over the same service
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#packetsLabelsPdf]. It validates a selected pickup
point over `packeta-widget` [VERIFY: src/Packetery/Module/Order/PickupPointValidator.php#validate].
It downloads the carrier list of the shop over `packeta-pickup-point-api`, which is a different host
[VERIFY: src/Packetery/Module/Carrier/Downloader.php#API_URL]. The browser of the customer loads
`packeta-widget`, and the plugin gives it the settings and the translations
[VERIFY: src/Packetery/Module/Checkout/CheckoutSettings.php#createSettings]. When a Packeta service
answers with a fault, the plugin writes the fault to the log and to the order, and it does not retry
[VERIFY: src/Packetery/Module/Order/PacketSubmitter.php#submitPacket].

## woocommerce: links between modules

woocommerce holds together through these internal links. Every link is a PHP call inside one
process, so no link carries a protocol.

| From module | To module | Link | Anchor |
|---|---|---|---|
| packetery-module-root | packetery-module-hooks | references | [VERIFY: src/Packetery/Module/Plugin.php#run] |
| packetery-module-hooks | packetery-module-checkout | references | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerFrontEnd] |
| packetery-module-hooks | packetery-module-order | references | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd] |
| packetery-module-shipping | packetery-module-checkout | references | [VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#calculate_shipping] |
| packetery-module-checkout | packetery-module-carrier | references | [VERIFY: src/Packetery/Module/Checkout/ShippingRateFactory.php#canCreateShippingRate] |
| packetery-module-checkout | packetery-module-order | references | [VERIFY: src/Packetery/Module/Checkout/OrderUpdater.php#actionUpdateOrder] |
| packetery-module-order | packetery-core | references | [VERIFY: src/Packetery/Module/Order/PacketSubmitter.php#submitPacket] |
| packetery-module-carrier | packetery-core | references | [VERIFY: src/Packetery/Module/Carrier/PacketaPickupPointsConfig.php#getVendorCarriers] |
| packetery-module-api | packetery-module-order | references | [VERIFY: src/Packetery/Module/Api/Internal/OrderController.php#saveModal] |
| Most modules | packetery-module-framework | references | [VERIFY: src/Packetery/Module/Framework/WcAdapter.php#WcAdapter] |

The domain module `packetery-core` references no other module of the repository. It declares the
interfaces that it needs, and `packetery-module-root` supplies the implementations
[VERIFY: src/Packetery/Module/WebRequestClient.php#post].

## woocommerce: environment configuration

woocommerce reads most values of its links from the settings of the shop. The address of the SOAP
service, the address of the tracking page and the environment of the widget live in the container
configuration of the plugin, which stands outside `src/Packetery`
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#__construct]. Keys, not values:

| Key | Points at | Where the value lives | Anchor |
|---|---|---|---|
| `api_password` | packeta-soap-api | [VERIFY: src/Packetery/Module/Options/OptionNames.php:11] | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#get_api_password] |
| `api_key` | packeta-widget and packeta-pickup-point-api | [VERIFY: src/Packetery/Module/Carrier/Downloader.php#download_json] | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#get_api_key] |
| `sender` | packeta-soap-api | [VERIFY: src/Packetery/Module/Options/OptionNames.php:11] | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#get_sender] |
| `WIDGET_URL_PRODUCTION`, `WIDGET_URL_STAGE` | packeta-widget | [VERIFY: src/Packetery/Module/WidgetUrlResolver.php#WIDGET_URL_PRODUCTION] | [VERIFY: src/Packetery/Module/WidgetUrlResolver.php#getUrl] |
| `API_URL` | packeta-pickup-point-api | [VERIFY: src/Packetery/Module/Carrier/Downloader.php#API_URL] | [VERIFY: src/Packetery/Module/Carrier/Downloader.php#run] |

The address of the SOAP service and the API password come to the client as constructor arguments,
so neither value lives in the domain module
[VERIFY: src/Packetery/Core/Api/Soap/Client.php#__construct].

## woocommerce: platform requirements

woocommerce needs WordPress with WooCommerce, and it stops its registration when WooCommerce is not
active [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php:375]. The plugin needs
the SOAP extension of PHP for the packet operations, and the settings page says so when the
extension is missing [VERIFY: src/Packetery/Module/Options/Page.php#render].

| Requirement | Used for | Anchor |
|---|---|---|
| WooCommerce | Orders, cart, shipping zones and the payment gateways | [VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#isPacketaMethod] |
| Action Scheduler | The five scheduled jobs of the plugin | [VERIFY: src/Packetery/Module/CronService.php#deactivate] |
| SOAP extension of PHP | Every packet operation | [VERIFY: src/Packetery/Core/Api/Soap/Client.php#createPacket] |
| Nette Forms and Latte | The admin forms and the templates, vendored under the plugin namespace | [VERIFY: src/Packetery/Module/FormFactory.php#create] |
| WooCommerce Blocks | The block checkout integration | [VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#WidgetIntegration] |

## woocommerce: aliases

woocommerce is the repository name and the service id of this documentation. The module ids follow
the namespace, and the manifest carries an alias for each module that has a document of its own
[VERIFY: src/Packetery/Module/Plugin.php#getAppIdentity].

| Canonical id | Also known as | Anchor |
|---|---|---|
| woocommerce | Packeta, the plugin name that WordPress shows | [VERIFY: packeta.php#packetaPlugin] |
| packetery-core | `Packetery\Core` | [VERIFY: src/Packetery/Core/CoreHelper.php#getTrackingUrl] |
| packetery-module-root | `Packetery\Module` | [VERIFY: src/Packetery/Module/Plugin.php#run] |
| packeta-soap-api | The SOAP API of Packeta | [VERIFY: src/Packetery/Core/Api/Soap/Client.php#createPacket] |
| packeta-widget | The widget library and the REST API that validates a pickup point; prestashop uses the same name | [VERIFY: src/Packetery/Core/Api/Rest/PickupPointValidate.php#validate] |
| packeta-pickup-point-api | The REST API that serves the carrier feed | [VERIFY: src/Packetery/Module/Carrier/Downloader.php#API_URL] |
