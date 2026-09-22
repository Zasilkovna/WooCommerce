---
title: "woocommerce — overview"
repo: woocommerce
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: 4034cda7
last-generated: 2026-09-22
covers: [packeta.php, src/Packetery/Module/Views, src/Packetery/Module/Forms, src/Packetery/Module/Log, src/Packetery/Module/Framework, src/Packetery/Module/Product, src/Packetery/Module/ProductCategory, src/Packetery/Module/Email, src/Packetery/Module/Dashboard, src/Packetery/Module/Labels, src/Packetery/Module/CustomsDeclaration, src/Packetery/Module/EntityFactory, src/Packetery/Module/Blocks, src/Packetery/Module/DiagnosticsLogger, src/Packetery/Module/Upgrade, src/Packetery/Module/Exception, src/Packetery/Module/Payment]
confidence: draft
tags: [ai-generated, repo-woocommerce, type-overview]
---

Repo: woocommerce · Module: — · Type: overview · Status: current

## woocommerce: what the repo does

woocommerce is a WordPress plugin that connects a WooCommerce shop to Packeta. The plugin adds one
shipping method for each Packeta carrier, so the customer selects a pickup point or an address in
the Packeta widget during the checkout
[VERIFY: src/Packetery/Module/Checkout/CheckoutSettings.php#createSettings]. The shop then sends the
order to Packeta as a packet, prints its label and reads its delivery status back
[VERIFY: src/Packetery/Module/Order/PacketSubmitter.php#submitPacket].

woocommerce keeps the Packeta data of an order, the carrier list and the plugin settings in its own
tables and options [VERIFY: src/Packetery/Module/Order/Repository.php#createOrAlterTable]. The
plugin supports the classic checkout and the block checkout, the classic order storage and the High
Performance Order Storage of WooCommerce
[VERIFY: src/Packetery/Module/ModuleHelper.php#isHposEnabled].

> ⚠ add business context (elicitation)

## woocommerce: modules

woocommerce consists of 25 modules. Nine of them passed the threshold for a standalone `reference/`
document, and the others are summarised on this page.

| Module | Path | Lines of logic | Document |
|---|---|---|---|
| packetery-core | `src/Packetery/Core` | 2473 | [reference/packetery-core.md](reference/packetery-core.md) |
| packetery-module-root | `src/Packetery/Module` | 1833 | [reference/packetery-module-root.md](reference/packetery-module-root.md) |
| packetery-module-order | `src/Packetery/Module/Order` | 5101 | [reference/packetery-module-order.md](reference/packetery-module-order.md) |
| packetery-module-checkout | `src/Packetery/Module/Checkout` | 2162 | [reference/packetery-module-checkout.md](reference/packetery-module-checkout.md) |
| packetery-module-carrier | `src/Packetery/Module/Carrier` | 1721 | [reference/packetery-module-carrier.md](reference/packetery-module-carrier.md) |
| packetery-module-options | `src/Packetery/Module/Options` | 1593 | [reference/packetery-module-options.md](reference/packetery-module-options.md) |
| packetery-module-shipping | `src/Packetery/Module/Shipping` | 1521 | [reference/packetery-module-shipping.md](reference/packetery-module-shipping.md) |
| packetery-module-hooks | `src/Packetery/Module/Hooks` | 562 | [reference/packetery-module-hooks.md](reference/packetery-module-hooks.md) |
| packetery-module-api | `src/Packetery/Module/Api` | 391 | [reference/packetery-module-api.md](reference/packetery-module-api.md) |
| 16 more modules | `src/Packetery/Module/*` | 5715 together | below — under the threshold or not documented yet |

## woocommerce: startup and configuration

woocommerce starts from the main plugin file, which builds the service container and calls the
plugin class [VERIFY: packeta.php#packetaPlugin]. The plugin class hands the work to the hook module, which
registers every callback of the request [VERIFY: src/Packetery/Module/Plugin.php#run]. The upgrade
runs on the `init` action, and it creates the tables of the plugin when the stored version differs
from the version of the code [VERIFY: src/Packetery/Module/Upgrade.php#check].

Settings that affect the start of the plugin:

| Key | Read in | Where the value lives | Anchor |
|---|---|---|---|
| `packetery` | `src/Packetery/Module/Options/OptionsProvider.php` | [VERIFY: src/Packetery/Module/Options/OptionNames.php:11] | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getAllOptions] |
| `packetery_version` | `src/Packetery/Module/Upgrade.php` | [VERIFY: src/Packetery/Module/Options/OptionNames.php#VERSION] | [VERIFY: src/Packetery/Module/Upgrade.php#check] |
| `packetery_advanced` | `src/Packetery/Module/Options/OptionsProvider.php` | [VERIFY: src/Packetery/Module/Options/OptionNames.php#PACKETERY_ADVANCED] | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#isWcCarrierConfigEnabled] |
| `packeta_feature_flags` | `src/Packetery/Module/Options/OptionsProvider.php` | [VERIFY: src/Packetery/Module/Options/OptionNames.php#FEATURE_FLAGS] | [VERIFY: src/Packetery/Module/Options/OptionNames.php#FEATURE_FLAGS_ERROR_COUNTER] |

The API password and the sender of the shop live in the option `packetery`, and the plugin derives
the API key from the password [VERIFY: src/Packetery/Module/Options/Page.php#sanitizePacketeryOptions].
Never copy those values into a document.

## woocommerce: platform and presentation modules

woocommerce groups its platform layer into five modules: packetery-module-framework,
packetery-module-views, packetery-module-forms, packetery-module-blocks and packetery-module-email.
None of them passed the threshold for a standalone document, or they hold no Packeta logic of their
own.

| Module | Behaviour | Anchor |
|---|---|---|
| packetery-module-framework | Wraps the functions of WordPress and of WooCommerce in two adapters and a set of traits, so the other modules call no global function | [VERIFY: src/Packetery/Module/Framework/WpAdapter.php#WpAdapter] |
| packetery-module-views | Renders the templates of the admin screens, of the front end and of the email, and it registers the scripts and the styles | [VERIFY: src/Packetery/Module/Views/AssetManager.php#AssetManager] |
| packetery-module-forms | Builds the admin forms of the plugin with Nette Forms: the carrier settings, the currency rates, the log filter and the bug report | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#CarrierFormFactory] |
| packetery-module-blocks | Integrates the Packeta widget into the block checkout of WooCommerce | [VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#WidgetIntegration] |
| packetery-module-email | Gives the shortcodes that put the tracking information into an email, and it sends the bug report | [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#register] |

The adapters of packetery-module-framework are the lowest layer of the plugin, and they depend on
no other module [VERIFY: src/Packetery/Module/Framework/WcAdapter.php#WcAdapter]. The email footer
of the shop gets the delivery information through one view
[VERIFY: src/Packetery/Module/Views/ViewMail.php#renderEmailFooter].

## woocommerce: catalogue and shipment modules

woocommerce groups the smaller parts of the order path into seven modules:
packetery-module-product, packetery-module-productcategory, packetery-module-labels,
packetery-module-customsdeclaration, packetery-module-entityfactory, packetery-module-payment and
packetery-module-exception.

| Module | Behaviour | Anchor |
|---|---|---|
| packetery-module-product | Adds the Packeta tab to a product, and it keeps the age verification and the disallowed carriers in the product metadata | [VERIFY: src/Packetery/Module/Product/DataTab.php#DataTab] |
| packetery-module-productcategory | Does the same for a product category, with its own form fields and grid column | [VERIFY: src/Packetery/Module/ProductCategory/FormFields.php#FormFields] |
| packetery-module-labels | Reads the courier numbers of the packets and builds the parameters of a label print | [VERIFY: src/Packetery/Module/Labels/CarrierLabelService.php#getPacketaPacketIdsWithCourierNumbers] |
| packetery-module-customsdeclaration | Owns the two tables of the customs declaration of an order and of its items | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#Repository] |
| packetery-module-entityfactory | Builds the entities of the core module from the data of WordPress and of the API | [VERIFY: src/Packetery/Module/EntityFactory/SizeFactory.php#SizeFactory] |
| packetery-module-payment | Answers whether a payment method is cash on delivery | [VERIFY: src/Packetery/Module/Payment/PaymentHelper.php#isCodPaymentMethod] |
| packetery-module-exception | Holds the exception classes of the plugin, and it carries no logic | [VERIFY: src/Packetery/Module/Exception/InvalidCarrierException.php#InvalidCarrierException] |

## woocommerce: logging and maintenance modules

woocommerce groups the maintenance of the plugin into four modules: packetery-module-log,
packetery-module-diagnosticslogger, packetery-module-dashboard and packetery-module-upgrade.

| Module | Behaviour | Anchor |
|---|---|---|
| packetery-module-log | Owns the log table of the plugin, writes every API result into it and shows the log on an admin page | [VERIFY: src/Packetery/Module/Log/Repository.php#createTable] |
| packetery-module-diagnosticslogger | Writes a diagnostic file when the shop enables the diagnostic logging | [VERIFY: src/Packetery/Module/DiagnosticsLogger/DiagnosticsLogger.php#getPacketaLogPath] |
| packetery-module-dashboard | Gives the dashboard page of the plugin, which is the parent of every other Packeta page | [VERIFY: src/Packetery/Module/Dashboard/DashboardPage.php#register] |
| packetery-module-upgrade | Holds the one migration that belongs to a single plugin version | [VERIFY: src/Packetery/Module/Upgrade/Version_1_4_2.php#run] |

The log module implements the logger interface of the core module, so every module writes through
one service [VERIFY: src/Packetery/Module/Log/DbLogger.php#DbLogger]. The log page is a child of the
dashboard page [VERIFY: src/Packetery/Module/Log/Page.php#register].
