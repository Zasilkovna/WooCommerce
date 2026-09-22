---
title: "woocommerce — packetery-module-hooks module"
repo: woocommerce
module: packetery-module-hooks
generated-by: skill:generate-docs@0.3.5
source-commit: e4971ab0
last-generated: 2026-09-22
covers: [src/Packetery/Module/Hooks]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-hooks, type-reference]
---

Repo: woocommerce · Module: packetery-module-hooks · Type: reference · Status: current

## packetery-module-hooks: purpose

packetery-module-hooks connects every part of the Packeta plugin to WordPress and to WooCommerce.
One class holds that map, and it decides what to register from the context of the request
[VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#register]. The other modules keep their
callbacks, and most of them get their hooks from here. A reader who looks for the place where a
callback becomes active starts in this module.

packetery-module-hooks also holds the lifecycle callbacks of the plugin. These are the translation,
the compatibility declaration, the links of the plugin list and the packet actions of a query
parameter [VERIFY: src/Packetery/Module/Hooks/PluginHooks.php#handleActions]. The module holds 3
files, 562 lines of logic and 16 public methods.

> ⚠ add business context (elicitation)

## packetery-module-hooks: public interface

packetery-module-hooks exposes one entry point and the callbacks that WordPress calls directly.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Entry point | `register` | Registers the hooks of the whole plugin for this request | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#register] |
| Admin hooks | `registerBackEnd` | Registers the grids, the metaboxes, the modal windows and the admin actions | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd] |
| Front end hooks | `registerFrontEnd` | Registers the checkout, the assets, the cart fees and the block callbacks | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerFrontEnd] |
| Menu pages | `addMenuPages` | Adds the dashboard, the settings, the carriers, the label pages and the log | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#addMenuPages] |
| Shipping methods | `addShippingMethods` | Adds the Packeta method to the method list of WooCommerce | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#addShippingMethods] |
| Plugin list links | `addLinksToPluginGrid` | Adds the settings link and the documentation link of the plugin | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#addLinksToPluginGrid] |
| Activation | `activatePlugin`, `redirectAfterActivation` | Mark the activation and open the dashboard once | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#activatePlugin] |
| Packet actions | `handleActions` | Runs the submit, the claim and the cancel action of a packet from a query parameter | [VERIFY: src/Packetery/Module/Hooks/PluginHooks.php#handleActions] |
| Translation | `loadTranslation` | Loads the translation file of the plugin for the current locale | [VERIFY: src/Packetery/Module/Hooks/PluginHooks.php#loadTranslation] |
| Order save | `updateOrder` | Saves the Packeta fields of an order, or deletes the Packeta row when the order lost the method | [VERIFY: src/Packetery/Module/Hooks/UpdateOrderHook.php#updateOrder] |

## packetery-module-hooks: registration map

packetery-module-hooks registers a different set of hooks for each context. The plugin first tests
WooCommerce. Without WooCommerce the module shows one admin notice and registers nothing more
[VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#register].

| Context | What the module registers | Anchor |
|---|---|---|
| Every request | Translation, HPOS compatibility, upgrade check, REST routes, cron service, packet auto submission, status synchronisation, order save, shipping method list | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#register] |
| Plugin lifecycle | Activation hook, deactivation hook that stops the scheduled actions | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#activatePlugin] |
| Admin, AJAX | The settings callback of the widget under `wp_ajax_get_settings` and `wp_ajax_nopriv_get_settings`, and nothing else | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd] |
| Admin, order grid | Filter links, order type select, columns, sortable columns and the column content | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd] |
| Admin, bulk actions | The bulk actions of the order grid with priority 20, and their handlers | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd] |
| Admin, actions | Label print, handover protocol, settings export, log deletion and the packet actions, all on `admin_init` | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd] |
| Admin, screens | Metaboxes, modal windows, the product and category grids, the product data tab and the dashboard widget | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#addMenuPages] |
| Front end | Checkout hooks, front assets, cart fees with priority 20, and the guest session migration | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerFrontEnd] |
| Front end, no AJAX | Order detail of the customer and the block checkout callbacks | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerFrontEnd] |
| Email | The footer callback of the configured email hook, when the shop enables the automatic insertion | [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#register] |

The order grid exists in two shapes. The module registers the classic shape and, from WooCommerce
7.9.0, the shape of the orders page of the High Performance Order Storage
[VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd]. Admin notices of the plugin
run with a very low priority, so they stand above the notices of other plugins
[VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#MIN_LISTENER_PRIORITY].

## packetery-module-hooks: dependencies

packetery-module-hooks depends on every module that has a callback. Structured lines:

references → packetery-core
references → packetery-module-root
references → packetery-module-order
references → packetery-module-checkout
references → packetery-module-carrier
references → packetery-module-options
references → packetery-module-api
references → packetery-module-shipping
references → packetery-module-views

The module calls the registration method of the modules that keep their own map. These are the cron
service, the checkout, the metaboxes, the modal windows, the settings page and the carrier settings
page [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#register]. The module reads the email
hook and the insertion setting from `packetery-module-options`, so a part of the map comes from the
settings of the shop [VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#register]. The order save
callback uses the order repository of `packetery-module-order` and the method test of
`packetery-module-shipping` [VERIFY: src/Packetery/Module/Hooks/UpdateOrderHook.php#updateOrder].

## packetery-module-hooks: known limitations

packetery-module-hooks has these limitations evidenced in the code. The module holds the map of the
whole plugin in one class, so every new callback of any module changes this file
[VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd]. The version of WooCommerce
that brings the second shape of the order grid is a hardcoded string
[VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerBackEnd]. The screen identifier of
that grid is a hardcoded string of the same method.

The order save callback runs once for each request, and a static variable holds that state, because
WooCommerce can save an order more than one time in a request
[VERIFY: src/Packetery/Module/Hooks/UpdateOrderHook.php#updateOrder]. An order that loses the
Packeta method also loses its Packeta row in the same callback. One block callback cannot move to
the block module, and a comment says so
[VERIFY: src/Packetery/Module/Hooks/HookRegistrar.php#registerFrontEnd]. The module contains no TODO
comment and no FIXME comment.
