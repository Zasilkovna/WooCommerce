## ai-docs/reference/packetery-module-carrier.md @ 82e3a6c9 — 2026-09-22

| line | claim | class | evidence |
|---|---|---|---|
| 6 | `source-commit: 82e3a6c9` | CONFIRMED | `git cat-file -t 82e3a6c9` = commit, on branch `docs/teams` |
| 8 | `covers: [src/Packetery/Module/Carrier]` | CONFIRMED | Directory exists, holds 16 `.php` files |
| 13 | Repo, module, type and status header | CONFIRMED | Matches front matter and `ai-docs/reference/` layout |
| 17-19 | Downloads the carrier feed and writes the carriers to its own table | CONFIRMED | `Updater::save()` calls `Repository::insert()` / `update()` on `wpdbAdapter->packeteryCarrier` |
| 19-21 | Builds the carrier entity that checkout and order use | CONFIRMED | `EntityRepository::getAnyById()` returns `Entity\Carrier` via `carrierEntityFactory` |
| 21-22 | The module also holds the carriers that no feed contains: the Packeta pickup point carriers | CONFIRMED | `PacketaPickupPointsConfig::getNonFeedCarriersByCountry()` filters `getCompoundAndVendorCarriers()` |
| 22-23 | ...and the car delivery carriers | INCORRECT | `Core/Entity/Carrier.php:23` — `public const CAR_DELIVERY_CARRIERS = [ '25061' ]`, a numeric feed id. `getNonFeedCarriersByCountry()` returns compound and vendor pickup point providers only, and `isInternalPickupPointCarrier()` is `return ! is_numeric( $carrierId );`, so a car delivery carrier is a feed carrier |
| 25-26 | The administrator sets limits, size restrictions and payment rules on the carrier settings page | CONFIRMED | `OptionsPage::register()` registers the page; `Options::getFreeShippingLimit()`, `getSizeRestrictions()`, `getDefaultCODSurcharge()` |
| 27-28 | One WordPress option per carrier, the name starts with a fixed prefix | CONFIRMED | `OptionPrefixer::CARRIER_OPTION_PREFIX`, `OptionPrefixer::getOptionId()` |
| 29 | 16 files, 1721 lines of logic, 92 public methods | CONFIRMED | `find … -name '*.php' \| wc -l` = 16; non-blank non-comment lines = 1721; `grep -c '^\s*public \(static \)\?function '` = 92 |
| 31 | `> ⚠ add business context (elicitation)` | PENDING | Standard placeholder |
| 35 | Exposes one admin page and a set of services | CONFIRMED | `OptionsPage::register()` is the only `add_submenu_page()` call in the module |
| 39 | Carrier settings page, `packeta-country` | CONFIRMED | `OptionsPage::SLUG` at `OptionsPage.php:42`; slug is a page id, not a secret |
| 40 | `getAnyById` returns the carrier of a feed id or an internal id | CONFIRMED | `EntityRepository::getAnyById()` scans non-feed carriers first, then `getById( (int) $carrierId )` |
| 41 | `getByCountryIncludingNonFeed` returns feed and internal carriers of one country | CONFIRMED | `EntityRepository::getByCountryIncludingNonFeed()` merges non-feed and feed carriers |
| 42 | `isValidForCountry` answers whether a carrier is active and delivers to the country | CONFIRMED | `EntityRepository::isValidForCountry()` checks deleted, available, country, then `carrierActivityBridge->isActive()` |
| 43 | `createByCarrierId` builds the settings object from the WordPress option | CONFIRMED | `CarrierOptionsFactory::createByCarrierId()` → `createByOptionId()` → `wpAdapter->getOption()` |
| 44 | `Options` gives typed access to limits, surcharges and restrictions | CONFIRMED | `Options::getFreeShippingLimit()`, `getDefaultCODSurcharge()`, `getSizeRestrictions()` |
| 45 | `getOptionId` builds the option name of a carrier | CONFIRMED | `OptionPrefixer::getOptionId()` returns `self::CARRIER_OPTION_PREFIX . $carrierId` |
| 45 | `getOptionId` also recognises the option name | INCORRECT | `getOptionId()` only concatenates. Recognition is a different method: `public static function isOptionId( string $optionId ): bool { return ( strpos( $optionId, self::CARRIER_OPTION_PREFIX ) === 0 ); }` (`OptionPrefixer.php:215`) |
| 46 | `run` downloads the feed, validates it and saves the carriers | CONFIRMED | `Downloader::run()` calls `fetch_as_array()`, `carrierUpdater->validate_carrier_data()`, `carrierUpdater->save()` |
| 47 | `startUpdate` starts the feed download from the admin page | CONFIRMED | `CarrierUpdater::startUpdate()` sets `Transients::RUN_UPDATE_CARRIERS` and redirects |
| 47 | `startUpdate` also shows the last update time | INCORRECT | `startUpdate()` has no such code; the time comes from a different method: `public function getLastUpdate(): ?string { $lastCarrierUpdate = $this->wpAdapter->getOption( OptionNames::LAST_CARRIER_UPDATE ); … }` |
| 48 | `getFixedCarrierId` maps the legacy pickup point id to the compound carrier of a country | CONFIRMED | `PacketaPickupPointsConfig::getFixedCarrierId()` compares with `Entity\Carrier::INTERNAL_PICKUP_POINTS_ID` and returns `$compoundCarriers[ $country ]->getId()` |
| 50-51 | The page needs the `manage_options` capability | CONFIRMED | `OptionsPage.php:105`, fourth argument of `add_submenu_page()` |
| 51-53 | The country list renders a filter form for carrier name, country and active state | CONFIRMED | `CountryListingFormFactory::create()` adds `PARAM_CARRIER_FILTER`, `PARAM_COUNTRY_FILTER`, `PARAM_ACTIVE_ONLY` |
| 53-55 | The shipping class tabs repeat the weight limits and the value limits per class | CONFIRMED | `ShippingClassPage::getTemplateParams()` builds `weightSectionId` / `valueSectionId` from `OptionsPage::FORM_FIELD_WEIGHT_LIMITS` and `FORM_FIELD_PRODUCT_VALUE_LIMITS` per shipping class |
| 59-61 | The endpoint template is a constant of the downloader; the request adds the API key and the site language | CONFIRMED | `Downloader::API_URL` (`Downloader.php:24`, a `private const` — the document names the symbol and does not quote the value); `download_json()` does `sprintf( self::API_URL, $this->optionsProvider->get_api_key(), $language )`, language from `substr( $this->wpAdapter->getLocale(), 0, 2 )` |
| 61-63 | The downloader decodes the answer and stops when it is empty or invalid | CONFIRMED | `get_from_json()` uses `json_decode()`; `run()` returns the error result when `$carriers === null \|\| count( $carriers ) === 0` |
| 63-65 | A carrier without one of the required keys stops the whole update | CONFIRMED | `Updater::validate_carrier_data()` returns `false` on the first incomplete carrier; `Downloader::run()` then returns before `save()` |
| 67-68 | The update maps the feed fields to the table columns and writes the differences | CONFIRMED | `Updater::carriers_mapper()` maps `pickupPoints` → `is_pickup_points` etc.; `save()` writes and logs `getArrayDifferences()` |
| 68-70 | A carrier the feed no longer contains gets the `deleted` flag | CONFIRMED | `Repository::set_as_deleted()` runs `UPDATE … SET \`deleted\` = 1 WHERE \`id\` IN (…)` |
| 70-71 | A carrier that becomes unavailable loses the active state in its settings | CONFIRMED | `Updater::save()`: `if ( isset( $differences['available'] ) )` → `updateOption( …, [ 'active' => false ] )`. Note: the code reacts to any change of `available`, not only to becoming unavailable |
| 71-72 | A new carrier of the feed gets a generated shipping method class | CONFIRMED | `Updater::save()`: `if ( ShippingMethodGenerator::classExists( … ) === false ) { … generateClass( … ) }` (the block also runs for an existing carrier whose class is missing) |
| 72-74 | Every change goes to the plugin log, and a transient signals the change | CONFIRMED | `Updater::addLogEntry()` adds a `Record` with `ACTION_CARRIER_LIST_UPDATE`; `save()` ends with `set_transient( Transients::CARRIER_CHANGES, true )` |
| 76-78 | The manual update stores a flag in a transient, redirects and downloads on the next request | CONFIRMED | `CarrierUpdater::startUpdate()` + `runUpdate()` reading `Transients::RUN_UPDATE_CARRIERS` |
| 78-79 | The time of the last update is one WordPress option | CONFIRMED | `OptionNames::LAST_CARRIER_UPDATE` in `Downloader::run()` and `CarrierUpdater::getLastUpdate()` |
| 83-84 | Depends on the core entities, the plugin options and the WooCommerce shipping zones | CONFIRMED | `use Packetery\Core\Entity`, `use Packetery\Module\Options\OptionsProvider`, `WC_Shipping_Zones` in `CarrierActivityBridge` |
| 86 | references → packetery-core | CONFIRMED | `use Packetery\Core\Entity\Carrier`, `Packetery\Core\Log\ILogger`, `Packetery\Core\PickupPointProvider\*` |
| 87 | references → packetery-module-options | CONFIRMED | `use Packetery\Module\Options\OptionsProvider` (`Downloader.php:15`), `Packetery\Module\Options\OptionNames` |
| 88 | references → packetery-module-shipping | CONFIRMED | `use Packetery\Module\Shipping\ShippingMethodGenerator` (`Updater.php:16`), `Packetery\Module\Shipping\BaseShippingMethod` (`CarrierActivityBridge.php`) |
| 89 | references → packetery-module-log | CONFIRMED | `use Packetery\Module\Log;` (`CountryListingPage.php:18`), constructor parameter `Log\Page $logPage` |
| 90 | references → packetery-module-framework | CONFIRMED | `use Packetery\Module\Framework\WpAdapter` / `WcAdapter` in 7 files of the module |
| 91 | calls → packeta-widget-api (sync, REST) | INCORRECT | The feed endpoint `Downloader::API_URL` (`Downloader.php:24`) uses the `pickup-point.api.packeta.com` host, while `packeta-widget-api` denotes the `widget.packeta.com` host in `ai-docs/reference/packetery-core.md:58` and `Core/Api/Rest/PickupPointValidate.php:13`. The structured line names a service the module does not call |
| 93-94 | Builds the `Packetery\Core\Entity\Carrier` object and the pickup point providers of packetery-core | CONFIRMED | `PacketaPickupPointsConfig::getVendorCarriers()` builds `VendorProvider` objects from `VendorCollectionFactory` |
| 95-96 | Reads the API key and the shop configuration from packetery-module-options, anchored at `CarrierOptionsFactory#createByOptionId` | INCORRECT | `CarrierOptionsFactory` has one dependency: `public function __construct( WpAdapter $wpAdapter )`, and `createByOptionId()` only does `$this->wpAdapter->getOption( $optionId )`. No `OptionsProvider`, no API key. The API key is read in `Downloader::download_json()`: `$this->optionsProvider->get_api_key()` |
| 96-99 | Generates and reads the shipping method classes, and decides the activity from the WooCommerce shipping zones in that mode | CONFIRMED | `Updater::save()` calls `shippingMethodGenerator->generateClass()`; `CarrierActivityBridge::isActive()` branches on `optionsProvider->isWcCarrierConfigEnabled()` and reads `WC_Shipping_Zones::get_zones()` |
| 99-101 | The carrier feed comes from the same Packeta REST service that validates a pickup point | INCORRECT | Two different hosts: `Downloader.php:24` (`API_URL`, `pickup-point.api.packeta.com`) against `Core/Api/Rest/PickupPointValidate.php:13` (`widget.packeta.com`). The fragment itself keeps this open as unknown 3 |
| 101-103 | The order module and the checkout module read the carriers only through this module | CONFIRMED | `grep -rn packeteryCarrier src/Packetery --include='*.php'` outside `Carrier/` matches only `Uninstaller.php:72`, `WpdbAdapterFactory.php:24`, `WpdbAdapter.php:27`; `Order/*` and `Checkout/*` use `Carrier\EntityRepository` |
| 107-108 | Owns one table of feed carriers, settings live in a WordPress option | CONFIRMED | `Repository::createOrAlterTable()`; `CarrierOptionsFactory::createByOptionId()` |
| 112 | `id` int(11), not null, primary key | CONFIRMED | `Repository::createOrAlterTable()`: `` `id` int(11) NOT NULL `` and `PRIMARY KEY  (\`id\`)` |
| 113 | `name`, `country`, `currency` varchar(255), not null | CONFIRMED | `createOrAlterTable()`; mapped in `Updater::carriers_mapper()` |
| 114 | `is_pickup_points`, `has_carrier_direct_label` tinyint(1), not null | CONFIRMED | `createOrAlterTable()`; mapper maps `pickupPoints` and `apiAllowed` |
| 115 | `requires_email`, `requires_phone`, `requires_size`, `separate_house_number` tinyint(1), not null | CONFIRMED | `Repository::COLUMN_NAMES` and `createOrAlterTable()` |
| 116 | `customs_declarations`, `disallows_cod` tinyint(1), not null | CONFIRMED | `Repository::COLUMN_NAMES` and `createOrAlterTable()` |
| 117 | `max_weight` float, not null | CONFIRMED | `` `max_weight` float NOT NULL `` |
| 118 | `available`, `deleted` tinyint(1), not null | CONFIRMED | `createOrAlterTable()` (`available` also has `DEFAULT 1`); `set_as_deleted()` |
| 119 | Option keys `free_shipping_limit`, `max_cart_value`, `age_verification_fee` | CONFIRMED | `Options::getFreeShippingLimit()`, `getMaxCartValue()`, `getAgeVerificationFee()` |
| 120 | Option keys `weight_limits`, `product_value_limits`, `pricing_type`, `per_class`, `class_calculation_type` | CONFIRMED | `OptionsPage::FORM_FIELD_WEIGHT_LIMITS`, `FORM_FIELD_PRODUCT_VALUE_LIMITS`, `FORM_FIELD_PRICING_TYPE`, `OPTIONS_SECTION_PER_CLASS`, `FORM_FIELD_CLASS_CALC_TYPE` (`OptionsPage.php:35-40`) |
| 121 | Option keys `dimensions_restrictions`, `vendor_groups`, `cod_rounding`, `surcharge_limits`, `address_validation` | CONFIRMED | `Options::getSizeRestrictions()`, `getVendorGroups()`, `getCodRoundingType()`, `hasAnyCodSurchargeSetting()`, `getAddressValidation()` |
| 123-124 | The option name is the prefix and the carrier id | CONFIRMED | `OptionPrefixer::getOptionId()` |
| 124-126 | An internal carrier has a non numeric id; the compound carriers use a fixed prefix | CONFIRMED | `isInternalPickupPointCarrier()` = `! is_numeric()`; `PacketaPickupPointsConfig::COMPOUND_CARRIER_PREFIX` (value not quoted by the document) |
| 127-129 | The price table keys are constants of the settings page, the other keys are string literals of the settings object | CONFIRMED | `OptionsPage.php:35-40` constants against `Options::getCodRoundingType()` = `$this->options['cod_rounding'] ?? Rounder::DONT_ROUND` |
| 133-135 | The repository holds a comment about repeated queries and a missing cache | CONFIRMED | `Repository.php:16` — `TODO: cache - some queries may run more times during request.` |
| 135-137 | The entity repository answers with a static per-request cache that holds the feed carriers alone | CONFIRMED | `EntityRepository.php:26` `private static $carrierDataCache = []`, used only by `getByIdCached( int $carrierId )` |
| 139-140 | Two admin classes call WordPress and WooCommerce functions directly although the adapters are in the constructor | CONFIRMED | `CountryListingPage` (`\WC()`, `get_option`, `get_transient`, `add_query_arg`, `wp_timezone`) and `OptionsPage` (`add_submenu_page`, `get_option`) are the only admin classes of the module that do so, both taking `WpAdapter` and `WcAdapter` |
| 141-142 | The settings page reads a carrier option with a direct call | CONFIRMED | `OptionsPage::getCarrierTemplateData()`: `$options = get_option( OptionPrefixer::getOptionId( $carrier->getId() ) );` |
| 142-144 | Most carrier option key names are string literals | CONFIRMED | `Options::getAddressValidation()` = `$this->options['address_validation'] ?? 'none'`, same pattern in 10 other getters |
| 144-146 | The internal carriers, their translations and the vendor groups are hardcoded lists | CONFIRMED | `PacketaPickupPointsConfig::getCompoundCarriers()` and `getVendorCarriers()` hold inline `$translatedNames` arrays; the groups come from `Core/PickupPointProvider/VendorCollectionFactory::create()`, also an inline list |
| 146-148 | The car delivery configuration takes two switches from the container, origin not shown | CONFIRMED | `CarDeliveryConfig::__construct( bool $sample, bool $enabled )` — two scalars, no source in the module |
| 150-151 | The feed download runs as a scheduled task registered outside this module | CONFIRMED | `Module/CronService.php:84` `add_action( self::CRON_CARRIERS_HOOK, [ $this->carrierDownloader, 'runAndRender' ] )` and `:106` `as_schedule_recurring_action( …, CRON_CARRIERS_HOOK )` |

CONFIRMED 64 · INCORRECT 6 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### manifest.part.packetery-module-carrier.yaml

| entry | class | evidence |
|---|---|---|
| `service: woocommerce` | CONFIRMED | Matches `repo: woocommerce` in the front matter and the other fragments |
| `source_commit: 82e3a6c9` | CONFIRMED | Commit exists on branch `docs/teams` |
| `aliases: [packetery-module-carrier]` | CONFIRMED | Same id as `modules[0].name` and the document file name |
| assumption 1 — branch `docs/teams`, commit 82e3a6c9 | CONFIRMED | `git rev-parse --abbrev-ref HEAD` = `docs/teams`; `git cat-file -t 82e3a6c9` = commit |
| assumption 2 — PSR-4 boundary, `ast/map.json` without `--split-psr4` at 11523b71, tests excluded | CONFIRMED | `ai-docs/ast/map.json` header: `"source-commit": "11523b7131c7c289991c414d1419747fc9b4e819"`, no per-namespace split; no test directory under `src/Packetery/Module/Carrier` |
| assumption 3 — the carrier feed uses the same Packeta REST service as the pickup point validation, hence `packeta-widget-api` | INCORRECT | `Downloader.php:24` (`API_URL`) uses the `pickup-point.api.packeta.com` host; `Core/Api/Rest/PickupPointValidate.php:13` uses the `widget.packeta.com` host. The fragment's own unknown 3 keeps the same question open, so the assumption contradicts the fragment |
| assumption 4 — the admin page of this module checks `manage_options`; the other modules do not | INCORRECT | `src/Packetery/Module/Options/Page.php:214` and `:226` also pass `'manage_options'` to `add_submenu_page()` |
| assumption 5 — the STE skill was invoked for the prose | UNCERTAIN | No evidence in the repository; not checkable from the code |
| `modules[0]` — name, path `src/Packetery/Module/Carrier`, lines 1721, doc `reference/packetery-module-carrier.md` | CONFIRMED | Directory exists; non-blank non-comment lines = 1721; the document file exists |
| `exposes[0]` — html `packeta-country`, auth `wordpress-capability`, evidence `OptionsPage#register` | CONFIRMED | `OptionsPage::SLUG` (`OptionsPage.php:42`) and the `'manage_options'` argument at `:105` |
| `exposes[1]` — library `getAnyById`, auth none | CONFIRMED | `EntityRepository::getAnyById()` is public and has no capability check |
| `exposes[2]` — library `createByCarrierId`, auth none | CONFIRMED | `CarrierOptionsFactory::createByCarrierId()` |
| `calls[0]` — service `packeta-widget-api`, kind rest, evidence `Downloader#run` | INCORRECT | The called host in `Downloader::API_URL` differs from the host that `packeta-widget-api` denotes in `manifest.part.packetery-core.yaml:31` and `Core/Api/Rest/PickupPointValidate.php:13`; the two entries name one service for two hosts |
| `owns_data[0]` — entity carrier, store mysql, evidence `Repository#createOrAlterTable` | CONFIRMED | `Repository::createOrAlterTable()` creates `wpdbAdapter->packeteryCarrier`; the settings live in a WordPress option through `CarrierOptionsFactory` |
| `unknowns[0]` — where WordPress schedules the feed download | CONFIRMED | No scheduling code in the namespace; the answer is outside it, at `Module/CronService.php:84` and `:106` |
| `unknowns[1]` — where the two car delivery switches come from | CONFIRMED | `CarDeliveryConfig::__construct( bool $sample, bool $enabled )` takes plain scalars; no wiring inside the module |
| `unknowns[2]` — whether the feed service is the same deployment as the pickup point validation | CONFIRMED | Honest open question: the two constants name different hosts, and the code cannot show whether one deployment serves both |

CONFIRMED 13 · INCORRECT 3 · UNCERTAIN 1 · NOT FOUND 0 · PENDING 0 · SENSITIVE 0 · INSTRUCTION 0
