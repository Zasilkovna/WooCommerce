---
title: "woocommerce — packetery-module-root module"
repo: woocommerce
module: packetery-module-root
generated-by: skill:generate-docs@0.3.5
source-commit: c961aa4f
last-generated: 2026-09-22
covers: [src/Packetery/Module]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-root, type-reference]
---

Repo: woocommerce · Module: packetery-module-root · Type: reference · Status: current

## packetery-module-root: purpose

packetery-module-root holds the classes that stand directly in the plugin namespace, and it starts
the plugin [VERIFY: src/Packetery/Module/Plugin.php#run]. The module owns the tasks that no other
module owns. It schedules the background jobs
[VERIFY: src/Packetery/Module/CronService.php#register], it creates and migrates the database tables
[VERIFY: src/Packetery/Module/Upgrade.php#check], it removes every trace of the plugin at uninstall
[VERIFY: src/Packetery/Module/Uninstaller.php#uninstall], and it wraps the database of WordPress
[VERIFY: src/Packetery/Module/WpdbAdapter.php#dbDelta].

packetery-module-root also holds the helpers that every module uses. These are the admin context
detection [VERIFY: src/Packetery/Module/ContextResolver.php#isOrderGridPage], the flash messages
[VERIFY: src/Packetery/Module/MessageManager.php#flash_message], the widget parameters
[VERIFY: src/Packetery/Module/WidgetOptionsBuilder.php#getCarrierForCheckout] and the unit
conversion [VERIFY: src/Packetery/Module/ModuleHelper.php#convertToMillimeters]. The module holds 28
files, 1833 lines of logic and 126 public methods. The subdirectories of the namespace are separate
modules and this document does not describe them.

> ⚠ add business context (elicitation)

## packetery-module-root: public interface

packetery-module-root exposes the entry point of the plugin and the services that the other modules
take from the container.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Plugin start | `run` | Registers the hooks of the plugin through the hook module | [VERIFY: src/Packetery/Module/Plugin.php#run] |
| Application identity | `getAppIdentity` | Builds the identity string that the plugin sends to Packeta | [VERIFY: src/Packetery/Module/Plugin.php#getAppIdentity] |
| Scheduled jobs | `register`, `deactivate` | Registers the background jobs, and removes them when the shop deactivates the plugin | [VERIFY: src/Packetery/Module/CronService.php#register] |
| Upgrade | `check`, `runCreateTables` | Creates the tables and runs the migration of an older version | [VERIFY: src/Packetery/Module/Upgrade.php#check] |
| Uninstall | `uninstall` | Drops the tables and deletes the options and the transients of the plugin | [VERIFY: src/Packetery/Module/Uninstaller.php#uninstall] |
| Database access | `WpdbAdapter` | Wraps the database of WordPress and holds the table names of the plugin | [VERIFY: src/Packetery/Module/WpdbAdapter.php#getPacketeryPrefix] |
| Order grid queries | `processClauses`, `processHposClauses` | Add the filters and the sorting of Packeta to the order queries of both storages | [VERIFY: src/Packetery/Module/QueryProcessor.php#processClauses] |
| Zone countries | `getCountryCodesForShippingZone` | Gives the country codes of a shipping zone, and expands a continent | [VERIFY: src/Packetery/Module/ShippingZoneRepository.php#getCountryCodesForShippingZone] |
| Widget parameters | `getCarrierForCheckout`, `createPickupPointForAdmin` | Build the parameters of the Packeta widget for the checkout and for the order detail | [VERIFY: src/Packetery/Module/WidgetOptionsBuilder.php#createPickupPointForAdmin] |
| Web requests | `post`, `get` | Send the HTTP requests of the plugin and raise one exception type on a failure | [VERIFY: src/Packetery/Module/WebRequestClient.php#post] |

The module also gives the shared shipping method of Packeta, the form factory with its validators,
the flash message system, the dashboard widget and the transient name constants
[VERIFY: src/Packetery/Module/Transients.php#Transients]. A bridge holds the service container,
because WooCommerce builds a shipping method outside the container
[VERIFY: src/Packetery/Module/CompatibilityBridge.php#getContainer].

## packetery-module-root: scheduled jobs and lifecycle

packetery-module-root schedules five recurring jobs, and it uses the Action Scheduler of
WooCommerce, not the cron of WordPress. The module plans a job only when the same job has no
schedule yet [VERIFY: src/Packetery/Module/CronService.php#register].

| Job | Hook | Schedule | Anchor |
|---|---|---|---|
| Log deletion | `packetery_cron_log_auto_deletion_hook` | Every day at 02:00 | [VERIFY: src/Packetery/Module/CronService.php#CRON_LOG_AUTO_DELETION_HOOK] |
| Transient purge | `packetery_cron_purge_transients` | Every day at 02:10 | [VERIFY: src/Packetery/Module/CronService.php#register] |
| Carrier feed | `packetery_cron_carriers_hook` | Every day at 09:10 | [VERIFY: src/Packetery/Module/CronService.php#CRON_CARRIERS_HOOK] |
| Packet status, week | `packetery_cron_packet_status_sync_hook` | Six times a day, from Monday to Friday | [VERIFY: src/Packetery/Module/CronService.php:23] |
| Packet status, weekend | `packetery_cron_packet_status_sync_hook_weekend` | Once a day on Saturday and Sunday | [VERIFY: src/Packetery/Module/CronService.php#register] |

The deactivation of the plugin removes all five schedules
[VERIFY: src/Packetery/Module/CronService.php#deactivate]. The upgrade owns the `plugin-schema` of the five tables. It compares the stored version
with the version constant of the plugin. A difference creates the tables and then runs the migration
blocks of the versions between [VERIFY: src/Packetery/Module/Upgrade.php#check]. The oldest block
moves the Packeta data of an order from the WooCommerce metadata into the order table
[VERIFY: src/Packetery/Module/Upgrade.php#migrateWpOrderMetadata]. The uninstall runs only when the
shop defines the constant that permits it, and it then drops five tables and deletes the options,
the transients and the product metadata of the plugin
[VERIFY: src/Packetery/Module/Uninstaller.php#cleanUp].

## packetery-module-root: dependencies

packetery-module-root depends on the modules whose services it starts and on the core entities.
Structured lines:

references → packetery-core
references → packetery-module-hooks
references → packetery-module-order
references → packetery-module-carrier
references → packetery-module-options
references → packetery-module-log
references → packetery-module-checkout

The plugin class asks `packetery-module-hooks` to register every hook
[VERIFY: src/Packetery/Module/Plugin.php#run]. The scheduled jobs call the log purger, the transient
purger, the carrier downloader and the packet synchroniser of the other modules
[VERIFY: src/Packetery/Module/CronService.php#register]. The upgrade creates the tables through the
repositories that own them, so each module keeps its own schema
[VERIFY: src/Packetery/Module/Upgrade.php#runCreateTables]. The web request client implements the
interface of `packetery-core`, and the core uses it for every REST call
[VERIFY: src/Packetery/Module/WebRequestClient.php#get]. The weight calculation reads the packaging
weight from `packetery-module-options`
[VERIFY: src/Packetery/Module/WeightCalculator.php#calculateOrderWeight].

## packetery-module-root: known limitations

packetery-module-root has these limitations evidenced in the code. The database wrapper calls the
schema function of WordPress two times and compares the two answers, because that function reports
success even when it changes nothing [VERIFY: src/Packetery/Module/WpdbAdapter.php#dbDelta]. A
failed query goes to the log only when the query names a table of the plugin
[VERIFY: src/Packetery/Module/WpdbAdapter.php#isPacketeryTableQueried].

The uninstall deletes nothing unless the shop defines the constant that permits it, and a debug
constant stops it as well [VERIFY: src/Packetery/Module/Uninstaller.php#uninstall]. The context
detection reads the global variables of WordPress instead of an injected service
[VERIFY: src/Packetery/Module/ContextResolver.php#isOrderDetailPage]. The migration of the order
metadata ignores the result of the save, and a comment says so
[VERIFY: src/Packetery/Module/Upgrade.php#migrateWpOrderMetadata]. The shared shipping method sets
its enabled state as a fixed value, and a comment says that the state can become a setting
[VERIFY: src/Packetery/Module/ShippingMethod.php#__construct]. The module contains no TODO comment
and no FIXME comment.
