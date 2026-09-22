---
title: "woocommerce — packetery-module-blocks module"
repo: woocommerce
module: packetery-module-blocks
generated-by: skill:generate-docs@0.3.5
source-commit: 43b25221
last-generated: 2026-09-22
covers: [src/Packetery/Module/Blocks]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-module-blocks, type-reference]
---

Repo: woocommerce · Module: packetery-module-blocks · Type: reference · Status: current

## packetery-module-blocks: purpose

packetery-module-blocks puts the Packeta widget into the block checkout of WooCommerce. The block
checkout is a React application, so the plugin cannot render its fields with a template. The module
therefore registers one integration of WooCommerce Blocks and gives it the script of the widget
[VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#initialize]. The module holds 2 files,
124 lines of logic and 11 public methods.

packetery-module-blocks also keeps the selection of the customer in the WooCommerce session, because
the block checkout sends it through the Store API and not through a form
[VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#saveShippingAndPaymentMethodsToSession].

> ⚠ add business context (elicitation)

## packetery-module-blocks: public interface

packetery-module-blocks exposes the integration of the block checkout and its hooks.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Block registration | `registerCheckoutBlock` | Registers the Packeta integration in the block registry of WooCommerce | [VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#registerCheckoutBlock] |
| Data attributes | `register` | Adds the Packeta block to the blocks that may carry data attributes | [VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#register] |
| Session write | `saveShippingAndPaymentMethodsToSession` | Stores the shipping method and the payment method of the block checkout and recomputes the cart | [VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#saveShippingAndPaymentMethodsToSession] |
| Store API callback | `orderUpdateCallback` | Registers the update callback of the Store API when WooCommerce offers one | [VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#orderUpdateCallback] |
| Integration name | `get_name` | Gives the name of the integration to WooCommerce Blocks | [VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#get_name] |
| Script registration | `initialize` | Registers the block script and takes its version from the build artefact | [VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#initialize] |
| Script handles | `get_script_handles`, `get_editor_script_handles` | Give the script handle to the front end and to the editor | [VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#get_script_handles] |
| Script data | `get_script_data` | Gives the widget settings of the checkout module to the browser | [VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#get_script_data] |


The module registers two filters for the data attributes: the current one and the name that
WooCommerce is expected to use later
[VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#register]. The version of the script comes from
a protected method, which gives the file time only in the debug mode of WordPress and when the file
exists, and the plugin version in every other case
[VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#get_file_version].

## packetery-module-blocks: dependencies

packetery-module-blocks depends on the checkout module for its data and on WooCommerce Blocks for
its interface. Structured lines:

references → packetery-module-root
references → packetery-module-checkout
references → packetery-module-framework

The settings that the block script receives come from `packetery-module-checkout`, and this module
only hands them over [VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#get_script_data]. The
session write and the cart recomputation go through the adapters of `packetery-module-framework`
[VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#saveShippingAndPaymentMethodsToSession]. The
script version falls back to the version constant of `packetery-module-root`
[VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#get_file_version]. The hook module calls
the registration of this module on the front end, and it calls the Store API callback on its own
hook, because that call cannot move into this module
[VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#orderUpdateCallback]. The script of the block
itself is a build artefact of the plugin and no PHP of this module renders it.

## packetery-module-blocks: known limitations

packetery-module-blocks has these limitations evidenced in the code. The integration interface of
WooCommerce Blocks is loaded with a manual include from two possible paths, because the autoloader
of the shop does not always offer it
[VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#IntegrationInterface]. A third path of a
future WooCommerce release would need another change.

The Store API callback is registered only when the function of WooCommerce exists, and an older shop
gets no callback and no message
[VERIFY: src/Packetery/Module/Blocks/BlockHooks.php#orderUpdateCallback]. The block name and the two
session keys are fixed strings of the module
[VERIFY: src/Packetery/Module/Blocks/WidgetIntegration.php#INTEGRATION_NAME]. The module contains no
TODO comment and no FIXME comment.
