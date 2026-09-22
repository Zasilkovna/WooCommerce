---
title: "woocommerce — packetery-module-carrier module"
repo: woocommerce
module: packetery-module-carrier
generated-by: skill:generate-docs@0.3.5
source-commit: 82e3a6c9
last-generated: 2026-09-22
covers: [src/Packetery/Module/Carrier]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-module-carrier, type-reference]
---

Repo: woocommerce · Module: packetery-module-carrier · Type: reference · Status: current

## packetery-module-carrier: purpose

packetery-module-carrier keeps the list of the Packeta carriers and the settings of each carrier.
The module downloads the carrier feed from Packeta, and it writes the carriers to its own database
table [VERIFY: src/Packetery/Module/Carrier/Updater.php#save]. The module builds the carrier entity
that the checkout and the order modules use
[VERIFY: src/Packetery/Module/Carrier/EntityRepository.php#getAnyById]. The module also holds the
carriers that no feed contains. These are the pickup point carriers of Packeta itself
[VERIFY: src/Packetery/Module/Carrier/PacketaPickupPointsConfig.php#getNonFeedCarriersByCountry].

The administrator sets the price limits, the size restrictions and the payment rules of each carrier
on the carrier settings page [VERIFY: src/Packetery/Module/Carrier/OptionsPage.php#register]. Each
carrier keeps its settings in one WordPress option, and the option name starts with a fixed prefix
[VERIFY: src/Packetery/Module/Carrier/OptionPrefixer.php#CARRIER_OPTION_PREFIX]. The module holds
16 files, 1721 lines of logic and 92 public methods.

> ⚠ add business context (elicitation)

## packetery-module-carrier: public interface

packetery-module-carrier exposes one admin page and a set of services that the other modules use.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Carrier settings page | `packeta-country` | Admin submenu page with the country list, the carrier detail and the shipping class tabs | [VERIFY: src/Packetery/Module/Carrier/OptionsPage.php#register] |
| Carrier entity | `getAnyById` | Returns the carrier of a feed identifier or of an internal identifier | [VERIFY: src/Packetery/Module/Carrier/EntityRepository.php#getAnyById] |
| Carriers of a country | `getByCountryIncludingNonFeed` | Returns the feed carriers and the internal carriers of one country | [VERIFY: src/Packetery/Module/Carrier/EntityRepository.php#getByCountryIncludingNonFeed] |
| Carrier validity | `isValidForCountry` | Answers whether a carrier is active and delivers to the country | [VERIFY: src/Packetery/Module/Carrier/EntityRepository.php#isValidForCountry] |
| Carrier options | `createByCarrierId` | Builds the settings object of one carrier from its WordPress option | [VERIFY: src/Packetery/Module/Carrier/CarrierOptionsFactory.php#createByCarrierId] |
| Settings object | `Options` | Gives typed access to the limits, the surcharges and the restrictions of a carrier | [VERIFY: src/Packetery/Module/Carrier/Options.php#getSizeRestrictions] |
| Option name | `getOptionId` | Builds the option name of a carrier | [VERIFY: src/Packetery/Module/Carrier/OptionPrefixer.php#getOptionId] |
| Feed download | `run` | Downloads the feed, validates it and saves the carriers | [VERIFY: src/Packetery/Module/Carrier/Downloader.php#run] |
| Manual update | `startUpdate` | Starts the feed download from the admin page | [VERIFY: src/Packetery/Module/Carrier/CarrierUpdater.php#startUpdate] |
| Internal carriers | `getFixedCarrierId` | Maps the legacy pickup point identifier to the compound carrier of a country | [VERIFY: src/Packetery/Module/Carrier/PacketaPickupPointsConfig.php#getFixedCarrierId] |

The page is available to a user with the `manage_options` capability
[VERIFY: src/Packetery/Module/Carrier/OptionsPage.php#register]. The country list renders a filter
form for the carrier name, the country and the active state
[VERIFY: src/Packetery/Module/Carrier/CountryListingFormFactory.php#create]. The shipping class tabs
of the carrier detail repeat the weight limits and the value limits for each shipping class
[VERIFY: src/Packetery/Module/Carrier/ShippingClassPage.php#getTemplateParams].

## packetery-module-carrier: carrier feed and update

packetery-module-carrier downloads the carrier feed as JSON. The endpoint template is a constant of
the downloader, and the request adds the API key of the shop and the language of the site
[VERIFY: src/Packetery/Module/Carrier/Downloader.php#API_URL]. The downloader decodes the answer and
stops when the answer is empty or invalid
[VERIFY: src/Packetery/Module/Carrier/Downloader.php#run]. The update then validates every carrier
of the feed. A carrier without one of the required keys stops the whole update
[VERIFY: src/Packetery/Module/Carrier/Updater.php#validate_carrier_data].

The update maps the feed fields to the table columns and writes the differences
[VERIFY: src/Packetery/Module/Carrier/Updater.php#carriers_mapper]. A carrier of the table that the
feed no longer contains gets the `deleted` flag
[VERIFY: src/Packetery/Module/Carrier/Repository.php#set_as_deleted]. A carrier that becomes
changes its available flag also gets that change in its own settings, and a carrier without a
generated shipping method class gets one [VERIFY: src/Packetery/Module/Carrier/Updater.php#save]. Every change
goes to the plugin log, and a transient tells the administrator that the carrier list changed
[VERIFY: src/Packetery/Module/Carrier/Updater.php#addLogEntry].

The administrator starts the same update from the carrier settings page. The module stores a flag in
a transient, redirects, and runs the download on the next request
[VERIFY: src/Packetery/Module/Carrier/CarrierUpdater.php#runUpdate]. The time of the last update is
one WordPress option [VERIFY: src/Packetery/Module/Carrier/CarrierUpdater.php#getLastUpdate].

## packetery-module-carrier: dependencies

packetery-module-carrier depends on the core entities, on the plugin options and on WooCommerce
shipping zones. Structured lines:

references → packetery-core
references → packetery-module-options
references → packetery-module-shipping
references → packetery-module-log
references → packetery-module-framework
calls → packeta-pickup-point-api (sync, REST)

The module builds the `Packetery\Core\Entity\Carrier` object and the pickup point providers of
`packetery-core` [VERIFY: src/Packetery/Module/Carrier/PacketaPickupPointsConfig.php#getVendorCarriers].
It reads the API key of the shop from `packetery-module-options` for the feed request
[VERIFY: src/Packetery/Module/Carrier/Downloader.php#download_json], and it reads the carrier
settings from the same module
[VERIFY: src/Packetery/Module/Carrier/CarrierOptionsFactory.php#createByOptionId]. It generates and
reads the shipping method classes of `packetery-module-shipping`, and it decides the activity of a
carrier from the WooCommerce shipping zones when the shop enables that mode
[VERIFY: src/Packetery/Module/Carrier/CarrierActivityBridge.php#isActive]. The carrier feed comes
from a Packeta REST service, and its host is not the host that validates a pickup point
[VERIFY: src/Packetery/Module/Carrier/Downloader.php#fetch_as_array]. The order module and the
checkout module read the carriers only through this module
[VERIFY: src/Packetery/Module/Carrier/EntityRepository.php#getActiveCarriers].

## packetery-module-carrier: data model

packetery-module-carrier owns one table of feed carriers, and it keeps the settings of each carrier
in a WordPress option [VERIFY: src/Packetery/Module/Carrier/Repository.php#createOrAlterTable].

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| carrier | `id` | int(11), not null | Primary key, the identifier of the feed | [VERIFY: src/Packetery/Module/Carrier/Repository.php#createOrAlterTable] |
| carrier | `name`, `country`, `currency` | varchar(255), not null | Name and market of the carrier | [VERIFY: src/Packetery/Module/Carrier/Updater.php#carriers_mapper] |
| carrier | `is_pickup_points`, `has_carrier_direct_label` | tinyint(1), not null | Delivery type and label type of the carrier | [VERIFY: src/Packetery/Module/Carrier/Updater.php#carriers_mapper] |
| carrier | `requires_email`, `requires_phone`, `requires_size`, `separate_house_number` | tinyint(1), not null | Fields that the packet must contain | [VERIFY: src/Packetery/Module/Carrier/Repository.php#COLUMN_NAMES] |
| carrier | `customs_declarations`, `disallows_cod` | tinyint(1), not null | Customs and cash on delivery rules | [VERIFY: src/Packetery/Module/Carrier/Repository.php#COLUMN_NAMES] |
| carrier | `max_weight` | float, not null | Weight limit of the carrier | [VERIFY: src/Packetery/Module/Carrier/Updater.php#carriers_mapper] |
| carrier | `available`, `deleted` | tinyint(1), not null | State against the last feed | [VERIFY: src/Packetery/Module/Carrier/Repository.php#set_as_deleted] |
| carrier settings | `free_shipping_limit`, `max_cart_value`, `age_verification_fee` | option keys | Price rules of one carrier | [VERIFY: src/Packetery/Module/Carrier/Options.php#getFreeShippingLimit] |
| carrier settings | `weight_limits`, `product_value_limits`, `pricing_type`, `per_class`, `class_calculation_type` | option keys | Price table of the carrier, also for each shipping class | [VERIFY: src/Packetery/Module/Carrier/OptionsPage.php#FORM_FIELD_WEIGHT_LIMITS] |
| carrier settings | `dimensions_restrictions`, `vendor_groups`, `cod_rounding`, `surcharge_limits`, `address_validation` | option keys | Size, vendor and payment rules of the carrier | [VERIFY: src/Packetery/Module/Carrier/Options.php#getSizeRestrictions] |

The option name of a carrier is the prefix and the carrier identifier
[VERIFY: src/Packetery/Module/Carrier/OptionPrefixer.php#getOptionId]. An internal carrier has a
non numeric identifier, and the compound carriers use a fixed prefix
[VERIFY: src/Packetery/Module/Carrier/PacketaPickupPointsConfig.php#isInternalPickupPointCarrier].
The keys of the price table live as constants of the settings page, but the other option keys are
string literals of the settings object
[VERIFY: src/Packetery/Module/Carrier/Options.php#getCodRoundingType].

## packetery-module-carrier: known limitations

packetery-module-carrier has these limitations evidenced in the code. The repository holds a comment
that its queries run more than one time in a request, and that a cache is missing
[VERIFY: src/Packetery/Module/Carrier/Repository.php#COLUMN_NAMES]. The entity repository answers
this with a static cache that lives for one request only, and that cache holds the feed carriers
alone [VERIFY: src/Packetery/Module/Carrier/EntityRepository.php#getAnyById].

Two admin classes call WordPress and WooCommerce functions directly, although the module gets the
adapters in the constructor [VERIFY: src/Packetery/Module/Carrier/CountryListingPage.php#getActiveCountries].
The settings page reads a carrier option with a direct call in the same way
[VERIFY: src/Packetery/Module/Carrier/OptionsPage.php#getCarrierTemplateData]. The names of most
carrier option keys are string literals, so a change of a key needs a change in more than one place
[VERIFY: src/Packetery/Module/Carrier/Options.php#getAddressValidation]. The internal carriers, their
translations and the vendor groups are hardcoded lists
[VERIFY: src/Packetery/Module/Carrier/PacketaPickupPointsConfig.php#getCompoundCarriers]. The car
delivery configuration takes its two switches from the container, and the module does not show where
they come from [VERIFY: src/Packetery/Module/Carrier/CarDeliveryConfig.php#isDisabled].

The feed download runs as a scheduled task, but the registration of that task is outside this module
[VERIFY: src/Packetery/Module/Carrier/Downloader.php#runAndRender].
