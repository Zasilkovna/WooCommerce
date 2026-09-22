---
title: "woocommerce — packetery-module-options module"
repo: woocommerce
module: packetery-module-options
generated-by: skill:generate-docs@0.3.5
source-commit: 33f911c4
last-generated: 2026-09-22
covers: [src/Packetery/Module/Options]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-module-options, type-reference]
---

Repo: woocommerce · Module: packetery-module-options · Type: reference · Status: current

## packetery-module-options: purpose

packetery-module-options keeps the settings of the Packeta plugin and gives them to the modules that
need them. The module renders the settings page with its tabs, validates the values and writes them to
five WordPress options [VERIFY: src/Packetery/Module/Options/Page.php#create_form]. The module then
reads those options once and answers each question with a typed method
[VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getAllOptions].

packetery-module-options also builds the settings export for the Packeta support
[VERIFY: src/Packetery/Module/Options/Exporter.php#getExportContent], and it removes the expired
checkout transients of the plugin
[VERIFY: src/Packetery/Module/Options/TransientPurger.php#purge]. The module holds 6 files, 1593
lines of logic and 90 public methods. The settings page is the largest file, and the settings
provider holds the most public methods, because each setting has its own method.

> ⚠ add business context (elicitation)

## packetery-module-options: public interface

packetery-module-options exposes one admin page, one settings provider and one export action.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Settings page | `packeta-options` | Admin submenu page with the tabs of the plugin settings | [VERIFY: src/Packetery/Module/Options/Page.php#registerSubmenuPage] |
| Settings registration | `admin_init` | Registers the option group and the validation of the main settings | [VERIFY: src/Packetery/Module/Options/Page.php#admin_init] |
| Sender check | `processActions` | Validates the sender name against the Packeta API and saves the answer | [VERIFY: src/Packetery/Module/Options/Page.php#processActions] |
| API credentials | `get_api_key`, `get_api_password`, `get_sender` | Give the account settings to the modules that call Packeta | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#get_api_key] |
| Checkout settings | `getCheckoutDetection`, `getCheckoutWidgetButtonLocation`, `isPickupPointValidationEnabled` | Answer how the checkout renders and validates | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getCheckoutDetection] |
| Label settings | `getLabelFormats`, `get_packeta_label_format`, `getLabelMaxOffset` | Give the label catalogue and the selected format | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getLabelFormats] |
| Weight and size settings | `getDimensionsUnit`, `getSanitizedDimensionValueInMm`, `getDefaultWeight` | Give the default packet size and weight | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getDimensionsUnit] |
| Submission settings | `isPacketAutoSubmissionEnabled`, `getPacketAutoSubmissionEventForPaymentGateway` | Answer when the plugin submits a packet by itself | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getPacketAutoSubmissionEventForPaymentGateway] |
| Status sync settings | `getStatusSyncingPacketStatuses`, `getValidAutoOrderStatusFromMapping` | Give the packet statuses to synchronise and the order status of each one | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getStatusSyncingPacketStatuses] |
| Settings export | `export-settings` | Sends the settings and the environment of the shop as a text file | [VERIFY: src/Packetery/Module/Options/Exporter.php#ACTION_EXPORT_SETTINGS] |

The page and its parent menu need the `manage_options` capability
[VERIFY: src/Packetery/Module/Options/Page.php#registerMenuPage]. The tabs hold the general
settings, the advanced settings, the packet status synchronisation, the automatic submission, the
currency rates and the support tools
[VERIFY: src/Packetery/Module/Options/Page.php#createAutoSubmissionForm]. The carrier settings live
in `packetery-module-carrier` and not on this page.

## packetery-module-options: data model

packetery-module-options owns no database table. The module keeps the settings in five WordPress
options, and the names of every option are constants
[VERIFY: src/Packetery/Module/Options/OptionNames.php#PACKETERY_SYNC].

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| settings | `packetery` | option, array | Main settings: account, checkout, labels, weight and size | [VERIFY: src/Packetery/Module/Options/Page.php#create_form] |
| settings | `packetery_sync` | option, array | Packet status synchronisation and the order status mapping | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getMaxStatusSyncingPackets] |
| settings | `packetery_auto_submission` | option, array | Automatic packet submission for each payment gateway | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#isPacketAutoSubmissionEnabled] |
| settings | `packetery_advanced` | option, array | Carrier configuration mode of the shop | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#isWcCarrierConfigEnabled] |
| settings | `packetery_currency_rates` | option, array | Custom currency rates of the shop | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getCustomCurrencyRates] |
| settings | `api_password`, `api_key`, `sender` | keys of `packetery` | Credentials of the Packeta account. The module derives the key from the password | [VERIFY: src/Packetery/Module/Options/Page.php#sanitizePacketeryOptions] |
| settings | `packeta_label_format`, `carrier_label_format`, `label_note` | keys of `packetery` | Label format and the note that the label carries | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getLabelNoteTemplate] |
| settings | `default_weight`, `packaging_weight`, `dimensions_unit`, `default_length`, `default_width`, `default_height` | keys of `packetery` | Default packet size and weight of a new order | [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getPackagingWeight] |
| state | `packetery_version`, `packetery_activated`, `packetery_last_carrier_update`, `packetery_last_settings_export` | options | State of the plugin between requests | [VERIFY: src/Packetery/Module/Options/OptionNames.php#LAST_CARRIER_UPDATE] |
| state | `packeta_feature_flags`, `packeta_feature_flags_error_counter`, `packeta_feature_flags_disabled_due_errors` | options | Feature flags of the plugin. These names use a different prefix | [VERIFY: src/Packetery/Module/Options/OptionNames.php#FEATURE_FLAGS] |

The module reads the option table directly for two tasks. It finds the expired transients of one
prefix [VERIFY: src/Packetery/Module/Options/Repository.php#getExpiredTransientsByPrefix], and it
lists every option name of a set of prefixes
[VERIFY: src/Packetery/Module/Options/Repository.php#getAllOptionNamesByPrefixes]. The purge then
deletes the expired checkout transients of every site of a multisite installation
[VERIFY: src/Packetery/Module/Options/TransientPurger.php#purgeForSite].

## packetery-module-options: dependencies

packetery-module-options depends on the Packeta API client and on the modules that own the values it
shows. Structured lines:

references → packetery-core
references → packetery-module-carrier
references → packetery-module-order
references → packetery-module-dashboard
references → packetery-module-log
calls → packeta-api (sync, SOAP)

The settings page validates the sender name with the SOAP client of `packetery-core`, and it writes
the answer to the plugin log [VERIFY: src/Packetery/Module/Options/Page.php#processActions]. The
page builds the choice list of the packet statuses from `packetery-module-order`
[VERIFY: src/Packetery/Module/Options/Page.php#getAllPacketStatusesChoiceData], and it takes the
order statuses from WooCommerce
[VERIFY: src/Packetery/Module/Options/Page.php#getOrderStatusesChoiceData].
The maximum cart value combines the plugin setting with the carrier setting of
`packetery-module-carrier` [VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getEffectiveMaxCartValueLimit].
The export reads the carrier list, the shipping zones and the last five days of the plugin log
[VERIFY: src/Packetery/Module/Options/Exporter.php#getExportContent]. The menu of the plugin belongs
to `packetery-module-dashboard`, and this page is one of its children
[VERIFY: src/Packetery/Module/Options/Page.php#registerSubmenuPage].

## packetery-module-options: known limitations

packetery-module-options has these limitations evidenced in the code. The export action runs from
two query parameters, and the export class contains no capability check and no nonce check
[VERIFY: src/Packetery/Module/Options/Exporter.php#outputExportTxt]. The export masks the API
password and removes the API key, but every other setting goes into the file as it is
[VERIFY: src/Packetery/Module/Options/Exporter.php#getExportContent]. An object of an unknown class
is printed as its type and its class name, without its content
[VERIFY: src/Packetery/Module/Options/Exporter.php:248].

The settings page needs the SOAP extension for the sender check. Without the extension the page
shows an error and the other settings still save
[VERIFY: src/Packetery/Module/Options/Page.php#render]. Two settings remain in the provider only for
the upgrade path, and their comments say so
[VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getAutoOrderStatus]. The label catalogue
is a hardcoded list of formats, so a new Packeta format needs a code change
[VERIFY: src/Packetery/Module/Options/OptionsProvider.php#getLabelFormats]. The logo of the plugin
menu is a hardcoded constant of the page class
[VERIFY: src/Packetery/Module/Options/Page.php#PACKETA_SVG_LOGO].

The purge of the expired transients has no schedule in this module. The registration of that task is
outside the namespace [VERIFY: src/Packetery/Module/Options/TransientPurger.php#purge].
