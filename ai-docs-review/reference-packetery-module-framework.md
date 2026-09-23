## ai-docs/reference/packetery-module-framework.md @ 43b25221 — 2026-09-22

| line | claim | class | evidence |
|---|---|---|---|
| 17-19 | The module wraps the WordPress and WooCommerce functions in two adapters, so other modules take a service instead of a global function. | CONFIRMED | `src/Packetery/Module/Framework/WpAdapter.php:24` `class WpAdapter` and `WcAdapter.php:31` `class WcAdapter`; every method delegates to a global function or a static class. |
| 19-20 | A test can replace the platform with a double. | CONFIRMED | `tests/Module/MockFactory.php:13-14` `createWpAdapter()` builds `getMockBuilder( WpAdapter::class )->getMock()`. |
| 20-21 | The module holds 16 files. | CONFIRMED | `ls src/Packetery/Module/Framework/*.php \| wc -l` = 16. |
| 21 | 537 lines of logic. | CONFIRMED | Non-blank, non-comment lines of `src/Packetery/Module/Framework/*.php` = 537; `ai-docs/manifest.yaml:185` `lines: 537`. |
| 21 | 115 public methods. | CONFIRMED | `grep -rh "public function" src/Packetery/Module/Framework/ \| wc -l` = 115. |
| 23 | The module carries no Packeta rule. | CONFIRMED | No `Packetery\` reference in the module apart from its own `namespace` and the `@package` docblocks. |
| 23-25 | It depends on no other module of the repository and is the lowest layer. | CONFIRMED | Same grep; the only imports are `WC_*`, `WP_*`, `Automattic\WooCommerce\Utilities\*`, `stdClass`, `DateTimeZone`. Anchor `WcAdapter.php#WcAdapter` does not itself carry the claim. |
| 25-26 | A business rule lives in the module that calls the adapter, not here. | CONFIRMED | Follows from the previous row; no conditional Packeta logic in the module. |
| 28 | `> ⚠ add business context (elicitation)` | PENDING | Standard placeholder. |
| 32-33 | Two adapters, each with its own methods and a set of traits. | CONFIRMED | `WpAdapter.php:24-32` uses 8 traits, `WcAdapter.php:31-36` uses 6 traits. |
| 37 | `WpAdapter` covers query arguments, terms, screens, nonces, multisite, admin addresses, mail and shortcodes. | CONFIRMED | `WpAdapter.php` has `addQueryArg`, `getTerm`, `getCurrentScree`, `createNonce`, `isMultisite`, `getAdminUrl`, `wpMail`, `addShortcode`. |
| 38 | `WcAdapter` covers products, shipping zones and packages, currency, orders and the logger. | CONFIRMED | `WcAdapter.php`: `productFactoryGetProduct`, `shippingZonesGetZoneMatchingPackage`, `shippingGetPackages`, `getWoocommerceCurrency`, `getOrdersWithoutPagination`, `getLogger`. |
| 39 | `HookTrait` registers actions and filters and applies them. | CONFIRMED | `HookTrait.php`: `addAction`, `addFilter`, `applyFilters`, `didAction`, `registerActivationHook`, `registerDeactivationHook`. |
| 40 | `OptionTrait`, `TransientTrait` read and write options and transients. | CONFIRMED | `OptionTrait.php`: `getOption`, `updateOption`, `deleteOption`; `TransientTrait.php`: `getTransient`, `setTransient`, `deleteTransient`. |
| 41 | "Posts and terms — `PostTrait` — Read the posts of WordPress". | INCORRECT | `PostTrait.php` holds only `getTheId`, `getEditPostLink`, `resetPostdata`; `grep -ci term src/Packetery/Module/Framework/PostTrait.php` = 0. The term methods are `WpAdapter::getTerm` and `WpAdapter::getTerms`, not `PostTrait`. |
| 42 | `EscapingTrait`, `TranslationTrait` escape the output and translate the texts. | CONFIRMED | `EscapingTrait.php`: `escUrl`, `escHtml`, `escAttr`; `TranslationTrait.php`: `__`, `getLocale`, `loadPluginTextDomain`. |
| 43 | `AssetTrait`, `HttpTrait` register scripts and styles and send HTTP requests. | CONFIRMED | `AssetTrait.php`: `enqueueScript`, `enqueueStyle`, `localizeScript`; `HttpTrait.php`: `remoteGet`, `remoteRetrieveBody`, `safeRedirect`. |
| 44 | "Read the cart and read and write the session of WooCommerce". | INCORRECT | `WcCartTrait` also writes: `cartCalculateTotals`, `cartCalculateShipping` and `cartFeesApiAddFee` change the cart. The row calls the cart part read-only. |
| 45 | `WcCustomerTrait`, `WcTaxTrait` read the customer and compute the taxes of a shipping rate. | CONFIRMED | `WcCustomerTrait.php`: `customerGetShippingCountry`, `customerGetBillingCountry`; `WcTaxTrait.php`: `taxGetShippingTaxRates`, `calcTax`, `taxCalcInclusiveTax`. |
| 46 | `ActionSchedulerTrait` plans an action of the Action Scheduler. | CONFIRMED | `ActionSchedulerTrait.php:27` `asScheduleSingleAction()` calls `as_schedule_single_action()`. |
| 50-51 | Depends on WordPress and WooCommerce and on nothing else of this repository. | CONFIRMED | Same import grep as line 23-25. |
| 53 | `called from → woocommerce` | CONFIRMED | Valid structured line; `ai-docs/manifest.yaml:1` `service: woocommerce`, and the adapters are consumed inside the plugin. |
| 55-56 | The module holds no `use` statement of another Packeta namespace. | CONFIRMED | `grep -rn "Packetery\\\\" src/Packetery/Module/Framework/` returns only the `namespace` lines and `@package` docblocks. Anchor `WpAdapter.php#getTerm` is a single method and cannot carry a module-wide claim. |
| 56-57 | "so a change of a Packeta module **never** changes this one". | UNCERTAIN | The absolute holds for compile-time imports only; the adapters call WooCommerce state that other modules mutate (for example the session that `BlockHooks` writes). No code proves "never". |
| 57-58 | "**Every other module** of the plugin takes one or both adapters in its constructor." | INCORRECT | Five module directories contain no `WpAdapter`/`WcAdapter` reference at all: `src/Packetery/Module/CustomsDeclaration/`, `EntityFactory/`, `Exception/`, `Payment/`, `Upgrade/`. `EntityFactory/Address.php:27` calls the global `get_option( 'woocommerce_store_address', null )` instead. Anchor `WcAdapter.php#getLogger` is unrelated to the claim. |
| 58-59 | The adapters call the global functions and the static classes of the platform. | CONFIRMED | `WcAdapter.php:132` `WC_Shipping_Zones::get_zone_matching_package()`, `:106` `wc_get_logger()`. |
| 59-61 | The Action Scheduler wrapper is "the one exception" that reaches a plugin of the shop and not the platform. | UNCERTAIN | Action Scheduler ships inside WooCommerce, and `WcAdapter.php:12-13` also imports `Automattic\WooCommerce\Utilities\FeaturesUtil` and `LoggingUtil`, which are equally "a plugin of the shop". Nothing in the code marks one exception. |
| 62 | "the scheduled **jobs** of Packeta go through it". | UNCERTAIN | One call site only: `src/Packetery/Module/Order/PacketSynchronizer.php:132`. |
| 66-68 | The adapters do not cover the whole platform; several modules still call a global function directly. | CONFIRMED | Direct `get_option()` calls outside the module, e.g. `src/Packetery/Module/Options/Exporter.php:139`, `src/Packetery/Module/EntityFactory/Address.php:27`, `src/Packetery/Module/Log/Purger.php:44`. Anchor `HookTrait.php#HookTrait` does not carry this. |
| 68-69 | The wrapper is a convention, not a boundary the code enforces. | CONFIRMED | Follows from the previous row; no lint rule or gate in the repository forbids the global call. |
| 71 | "**Three** methods hold more than a pass through." | INCORRECT | At least five do, and one of the three named is a pure pass-through. `WcAdapter.php:105-107` `public function getLogger() { return wc_get_logger(); }`. Branching methods the sentence leaves out: `WcAdapter.php:57-63` `productFactoryGetProduct` (`instanceof WC_Product` else `null`), `WcSessionTrait.php` `sessionGetString` and `sessionGetArray` (docblock "Minimal logic to ensure strict typing"). |
| 71-73 | One method normalises the answer of the WooCommerce order query, which returns a list or an object. | CONFIRMED | `WcAdapter.php:155-167` `getOrdersWithoutPagination` handles `is_array( $results )`, `$results instanceof stdClass`, else `[]`. |
| 73-75 | One returns nothing when the WooCommerce logging class is missing. | CONFIRMED | `WcAdapter.php:173-176` `if ( class_exists( LoggingUtil::class ) === false ) { return null; }`. |
| 75-77 | One returns two possible types of the logger, and only the comment says so. | CONFIRMED | `WcAdapter.php:102-105` docblock `@return WC_Logger|WC_Logger_Interface`, the signature has no return type. |
| 77-78 | The module contains no TODO comment and no FIXME comment. | CONFIRMED | `grep -rn "TODO\|FIXME" src/Packetery/Module/Framework/` returns nothing (rc=1). |

CONFIRMED 27 · INCORRECT 4 · UNCERTAIN 3 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### ai-docs/manifest.yaml — packetery-module-framework

| entry | class | evidence |
|---|---|---|
| `aliases:` `packetery-module-framework` (manifest.yaml:15) | CONFIRMED | Matches the `module:` front matter of the document and the `modules:` entry. |
| `modules:` `name: packetery-module-framework`, `path: src/Packetery/Module/Framework`, `lines: 537`, `doc: reference/packetery-module-framework.md` (manifest.yaml:183-186) | CONFIRMED | Path exists; 537 = non-blank non-comment lines of `src/` without tests; the document is at that path. |
| `exposes:` `WcAdapter` — kind library, module packetery-module-framework, consumers woocommerce, auth none, evidence `WcAdapter.php#productFactoryGetProduct`, notes "Wrapper of the WooCommerce functions and static classes." (manifest.yaml:297-304) | CONFIRMED | `WcAdapter.php:57` `public function productFactoryGetProduct`; the class wraps `WC()`, `wc_*` and `WC_*` static classes. |
| `exposes:` `WpAdapter` — evidence `WpAdapter.php#getTerm`, notes "Wrapper of the WordPress functions. **Every module takes it instead of a global call.**" (manifest.yaml:305-312) | INCORRECT | The evidence anchor is right (`WpAdapter.php:getTerm` exists), the note is not. `src/Packetery/Module/EntityFactory/`, `CustomsDeclaration/`, `Exception/`, `Payment/` and `Upgrade/` reference neither adapter, and `EntityFactory/Address.php:27-29` calls the global `get_option()` three times. |

CONFIRMED 3 · INCORRECT 1 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 0 · SENSITIVE 0 · INSTRUCTION 0

Both tables: CONFIRMED 30 · INCORRECT 5 · UNCERTAIN 3 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0
