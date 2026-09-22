---
title: "woocommerce — overview"
repo: woocommerce
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [packeta.php, src/Packetery/Module/Framework, src/Packetery/Module/EntityFactory, src/Packetery/Module/Blocks, src/Packetery/Module/DiagnosticsLogger, src/Packetery/Module/Upgrade, src/Packetery/Module/Exception, src/Packetery/Module/Payment]
confidence: reviewed
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

woocommerce consists of 25 modules. Eighteen of them have a document in `reference/`, and
[index.md](index.md) lists those documents. The seven modules below stayed under the threshold for
a document of their own, and this page is their home.

| Module | Path | Lines of logic | Document |
|---|---|---|---|
| packetery-module-framework | `src/Packetery/Module/Framework` | 537 | below — the platform layer |
| packetery-module-entityfactory | `src/Packetery/Module/EntityFactory` | 142 | below — the platform layer |
| packetery-module-blocks | `src/Packetery/Module/Blocks` | 124 | below — the platform layer |
| packetery-module-diagnosticslogger | `src/Packetery/Module/DiagnosticsLogger` | 65 | below — the small modules |
| packetery-module-upgrade | `src/Packetery/Module/Upgrade` | 44 | below — the small modules |
| packetery-module-exception | `src/Packetery/Module/Exception` | 25 | below — the small modules |
| packetery-module-payment | `src/Packetery/Module/Payment` | 16 | below — the small modules |

## woocommerce: startup and configuration

woocommerce starts from the main plugin file, which builds the service container and calls the
plugin class [VERIFY: packeta.php#packetaPlugin]. The plugin class hands the work to the hook module,
which registers every callback of the request
[VERIFY: src/Packetery/Module/Plugin.php#run]. The upgrade runs on the `init` action, and it creates
the tables of the plugin when the stored version differs from the version of the code
[VERIFY: src/Packetery/Module/Upgrade.php#check].

Settings that affect the start of the plugin:

| Key | Read in | Where the value lives | Anchor |
|---|---|---|---|
| `packetery` | `src/Packetery/Module/Options/OptionsProvider.php` | [VERIFY: src/Packetery/Module/Options/OptionNames.php:11] | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getAllOptions] |
| `packetery_version` | `src/Packetery/Module/Upgrade.php` | [VERIFY: src/Packetery/Module/Options/OptionNames.php#VERSION] | [VERIFY: src/Packetery/Module/Upgrade.php#check] |
| `packetery_advanced` | `src/Packetery/Module/Options/OptionsProvider.php` | [VERIFY: src/Packetery/Module/Options/OptionNames.php#PACKETERY_ADVANCED] | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#isWcCarrierConfigEnabled] |
| `packeta_feature_flags` | `src/Packetery/Module/Uninstaller.php` | [VERIFY: src/Packetery/Module/Options/OptionNames.php#FEATURE_FLAGS] | [VERIFY: src/Packetery/Module/Uninstaller.php#cleanUp] |

The API password and the sender of the shop live in the option `packetery`, and the plugin derives
the API key from the password
[VERIFY: src/Packetery/Module/Options/Page.php#sanitizePacketeryOptions]. This documentation names
those keys and never carries their values.

## woocommerce: platform modules

woocommerce groups its platform layer into three modules: packetery-module-framework,
packetery-module-entityfactory and packetery-module-blocks. They hold no Packeta business rule of
their own, and the modules with a document use them.

| Module | Behaviour | Anchor |
|---|---|---|
| packetery-module-framework | Wraps the functions of WordPress and of WooCommerce in two adapters and a set of traits, which most other modules use instead of a global function | [VERIFY: src/Packetery/Module/Framework/WpAdapter.php#WpAdapter] |
| packetery-module-entityfactory | Builds the entities of the domain module from the data of WordPress and of the API | [VERIFY: src/Packetery/Module/EntityFactory/SizeFactory.php#SizeFactory] |
| packetery-module-blocks | Integrates the Packeta widget into the block checkout of WooCommerce | [VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#WidgetIntegration] |

The adapters of packetery-module-framework are the lowest layer of the plugin, and they depend on no
other module [VERIFY: src/Packetery/Module/Framework/WcAdapter.php#WcAdapter].

## woocommerce: small modules

woocommerce keeps four more modules that hold a few classes each: packetery-module-diagnosticslogger,
packetery-module-upgrade, packetery-module-exception and packetery-module-payment.

| Module | Behaviour | Anchor |
|---|---|---|
| packetery-module-diagnosticslogger | Writes a diagnostic file when the shop enables the diagnostic logging | [VERIFY: src/Packetery/Module/DiagnosticsLogger/DiagnosticsLogger.php#getPacketaLogPath] |
| packetery-module-upgrade | Holds the one migration that belongs to a single plugin version | [VERIFY: src/Packetery/Module/Upgrade/Version_1_4_2.php#run] |
| packetery-module-exception | Holds the exception classes of the plugin modules, and it carries no logic; the domain module has its own API exceptions | [VERIFY: src/Packetery/Module/Exception/InvalidCarrierException.php#InvalidCarrierException] |
| packetery-module-payment | Answers whether a payment method is cash on delivery | [VERIFY: src/Packetery/Module/Payment/PaymentHelper.php#isCodPaymentMethod] |

The upgrade module holds only the migration of one version. The upgrade of the schema and of the
settings lives in `packetery-module-root` [VERIFY: src/Packetery/Module/Upgrade.php#runCreateTables].
