## ai-docs/reference/packetery-module-product.md @ b34fe03c — 2026-09-22

Verified against the working tree at `docs/teams` HEAD a6ef312a. Module numbers measured with
`find src/Packetery/Module/Product -name '*.php' | wc -l` (4), non-empty non-comment lines of those
files (353) and `grep -cE '^\s*(final )?public( static)? function'` over them (26). Tests excluded.

| line | claim | class | evidence |
|---|---|---|---|
| 8 | `covers: [src/Packetery/Module/Product]` | CONFIRMED | The directory holds exactly the 4 files DataTab.php, Entity.php, ProductEntityFactory.php, ProductGridExtender.php |
| 17 | packetery-module-product holds the Packeta settings of one product | CONFIRMED | Entity.php:22-23 declares the two product meta keys; DataTab.php:141-150 builds their form |
| 17-19 | The shop owner marks a product for age verification and disallows carriers [DataTab.php#registerTab] | CONFIRMED | The anchored method only adds the tab (DataTab.php:116-130); the two settings are in the private DataTab::createForm:141-150 that the tab renders |
| 19-21 | The checkout hides those carriers and adds the verification fee [Entity.php#isAgeVerificationRequired] | CONFIRMED | Checkout/ShippingRateFactory.php:211-221 drops a disallowed option; Checkout/Checkout.php:226-249 addAgeVerificationFee |
| 23-24 | The module gives the size of a product in centimetres for the carrier size check [Entity.php#getLengthInCm] | CONFIRMED | Entity.php:91-116; Checkout/CartService.php:234-256 reads the three getters for the size check |
| 25 | The module holds 4 files | CONFIRMED | `find src/Packetery/Module/Product -name '*.php'` piped to `wc -l` gives 4 |
| 25 | 353 lines of logic | CONFIRMED | non-empty non-comment lines of those 4 files = 353; manifest `lines: 353` agrees |
| 25 | 26 public methods | CONFIRMED | `grep -cE '^\s*(final )?public( static)? function'` = 3 + 8 + 6 + 9 = 26 |
| 25-27 | The settings belong to the product itself [DataTab.php#saveData] | CONFIRMED | saveData:186-204 loads the product by post id and writes its own meta through processFormData:212-224 |
| 29 | `> ⚠ add business context (elicitation)` | PENDING | standard placeholder |
| 33 | The module exposes the product tab, the entity of a product and the grid extension | CONFIRMED | DataTab, Entity (+ ProductEntityFactory), ProductGridExtender |
| 37 | Product tab `packetery-tab`, hidden for a virtual product [DataTab.php#registerTab] | CONFIRMED | DataTab.php:30 `const NAME = 'packetery-tab'`; :126 `'class' => [ 'hide_if_virtual', 'hide_if_downloadable' ]` |
| 38 | `register` registers the tab, its panel and its save [DataTab.php#register] | CONFIRMED | DataTab.php:104-106 add_filter woocommerce_product_data_tabs, add_action woocommerce_product_data_panels, add_action woocommerce_process_product_meta |
| 39 | `saveData`, `processFormData` write the tab values into the product metadata [DataTab.php#processFormData] | CONFIRMED | DataTab.php:222 `update_post_meta( $productId, $attr, $value )` |
| 40 | `isAgeVerificationRequired` answers whether the product needs age verification | CONFIRMED | Entity.php:55-57 |
| 41 | `getDisallowedShippingRateIds`, `getDisallowedShippingRateChoices` give the carriers that must not deliver the product | CONFIRMED | Entity.php:64-80 |
| 42 | `isPhysical` answers whether the product is neither virtual nor downloadable | CONFIRMED | Entity.php:46-48 |
| 43 | `getLengthInCm`, `getWidthInCm`, `getHeightInCm` give the size in centimetres | CONFIRMED | Entity.php:91-116, each rounds CoreHelper::convertToCentimeters |
| 44 | `fromPostId`, `fromGlobals` build the entity from an identifier or from the current post | CONFIRMED | ProductEntityFactory.php:46-64 |
| 45 | `addProductListColumns`, `fillCustomProductListColumns` add the two Packeta columns | CONFIRMED | ProductGridExtender.php:56-100; HookRegistrar.php:430-431 registers both |
| 46 | `addProductFilters`, `processProductFilterClauses` add the two filters and join the metadata | CONFIRMED | ProductGridExtender.php:121-133 and :166-186; HookRegistrar.php:535-536 |
| 48 | The two columns are hidden by default [ProductGridExtender.php#defaultHiddenColumns] | CONFIRMED | ProductGridExtender.php:107-114 adds both columns to `$hidden` on screen `edit-product`; HookRegistrar.php:432 hooks `default_hidden_columns` |
| 48-49 | …and a filter can switch them off [ProductGridExtender.php#defaultHiddenColumns] | INCORRECT | The anchored method has no filter hook. The only `applyFilters` of the class is `packetery_product_list_filters_enabled` in the private getEnabledFilters():205, and it gates addProductFilters:121-133 — it switches off the two grid **filters**, not the two columns |
| 49-51 | The checkbox list of the carriers comes from the active carriers of the carrier module [DataTab.php#render] | CONFIRMED | render:167-177 renders the form built by createForm:144 `$this->carrierRepository->getAllActiveCarriersList()` (Packetery\Module\Carrier\EntityRepository) |
| 55 | The module keeps `product-settings` in the metadata of a product | CONFIRMED | Entity.php:22-23; manifest owns_data entity product-settings, store mysql |
| 59 | `packetery_age_verification_18_plus` metadata text flag; the tab stores `1` or `0`, the reader compares against `1` | CONFIRMED | DataTab.php:215 `$value = $value ? '1' : '0'`; Entity.php:56 compares with `'1'` |
| 60 | `packetery_disallowed_shipping_rates` metadata array of carrier identifiers | CONFIRMED | Entity.php:23, :64-80; DataTab.php:143-150 container of carrier option ids |
| 62-63 | The save keeps only the selected carriers, so an empty selection stores an empty array [DataTab.php#processFormData] | CONFIRMED | DataTab.php:218-220 `$value = array_filter( $value )` |
| 63-65 | The reader checks only that the value is an array and gives an empty list otherwise [Entity.php#getDisallowedShippingRateChoices] | CONFIRMED | Entity.php:65-70 |
| 65-67 | The grid filter joins the two metadata keys to the product query with its own aliases [ProductGridExtender.php#processProductFilterClauses] | CONFIRMED | ProductGridExtender.php:174 alias `packetery_pm_age`, :181 alias `packetery_pm_restrictions` |
| 71 | The module depends on the carrier list and on the platform helpers | CONFIRMED | DataTab.php:14 Carrier\EntityRepository; Entity.php:13 Framework\WpAdapter; ProductEntityFactory.php:13 Framework\WcAdapter |
| 73 | references → packetery-core | CONFIRMED | Entity.php:12 `use Packetery\Core\CoreHelper` |
| 74 | references → packetery-module-root | CONFIRMED | DataTab.php:17 `Packetery\Module\FormFactory`; ProductGridExtender.php:10 `Packetery\Module\WpdbAdapter` |
| 75 | references → packetery-module-carrier | CONFIRMED | DataTab.php:13-15 CarDeliveryConfig, EntityRepository, OptionPrefixer |
| 76 | references → packetery-module-log | CONFIRMED | DataTab.php:18 and ProductGridExtender.php:9 `Packetery\Module\Log\ArgumentTypeErrorLogger` |
| 77 | references → packetery-module-exception | CONFIRMED | ProductEntityFactory.php:12 `Packetery\Module\Exception\ProductNotFoundException` |
| 73-77 | the five `references →` lines are the dependencies of the module | INCORRECT | The list leaves out `packetery-module-framework`, which the manifest lists as a module (manifest.yaml:162, path src/Packetery/Module/Framework). Three of the four files import it: Entity.php:13 `use Packetery\Module\Framework\WpAdapter`, ProductEntityFactory.php:13 `use Packetery\Module\Framework\WcAdapter`, ProductGridExtender.php:8 `use Packetery\Module\Framework\WpAdapter` |
| 79-80 | The tab reads the active carriers of `packetery-module-carrier` | CONFIRMED | DataTab.php:144 `getAllActiveCarriersList()` |
| 79-80 | …and the grid reads the active carriers of `packetery-module-carrier` | INCORRECT | ProductGridExtender has no carrier dependency at all. Its imports (ProductGridExtender.php:7-11) are Latte Engine, Framework\WpAdapter, Log\ArgumentTypeErrorLogger, WpdbAdapter, Nette Request; its constructor :35-42 takes the same six. The grid reads the two product meta keys only |
| 80-81 | …they leave out the car delivery carriers that the shop disabled [ProductGridExtender.php#addProductFilters] | INCORRECT | addProductFilters:121-133 only asks getEnabledFilters() and registers two render callbacks; it contains no carrier code. The car delivery skip is in DataTab.php:146 `if ( $this->carDeliveryConfig->isCarDeliveryCarrierDisabled( … ) ) { continue; }`, and it applies to the tab only |
| 81-83 | The size conversion uses the helper of `packetery-core` [Entity.php#getHeightInCm] | CONFIRMED | Entity.php:111 `CoreHelper::convertToCentimeters( … )` |
| 83-85 | A missing product raises the exception of `packetery-module-exception` [ProductEntityFactory.php#fromGlobals] | CONFIRMED | fromGlobals:60-64 delegates to fromPostId, which throws at :49 `throw new ProductNotFoundException( "Product $postId not found." )` |
| 85-87 | Wrong hook arguments go to the logger of `packetery-module-log` [ProductGridExtender.php#fillCustomProductListColumns] | INCORRECT | fillCustomProductListColumns:69-100 never touches the logger; it has no type guard. The logger call of this class is in addProductListColumns:57-61 `$this->argumentTypeErrorLogger->log( __METHOD__, 'columns', 'array', $columns )`, and the second one is DataTab.php:118 |
| 87-88 | The checkout reads this module for every cart item | CONFIRMED | Checkout/CartService.php:70-72, :121-128 and :232-237 build a Product\Entity per cart product |
| 87-88 | …and the order builder reads this module for every cart item | INCORRECT | Order/Builder.php:243 iterates order items, not cart items: `foreach ( $wcOrder->get_items() as $item )`, then :246 `new Product\Entity( $product )` |
| 92-94 | The grid filter compares the metadata against a serialised empty array written as a literal [ProductGridExtender.php#processProductFilterClauses] | CONFIRMED | ProductGridExtender.php:182 the where clause appends a comparison of `meta_value` against the literal serialised empty array, for the alias `packetery_pm_restrictions` |
| 95 | The filter builds its own join instead of a metadata query | CONFIRMED | ProductGridExtender.php:174 and :181 append a LEFT JOIN on postmeta to `$clauses['join']` |
| 97-98 | The age verification flag is stored as a text value and compared against a text value | CONFIRMED | DataTab.php:215 stores `'1'`/`'0'`; Entity.php:56 compares with `'1'` |
| 98-99 | …so a numeric value of the same meaning does not match [Entity.php#isAgeVerificationRequired] | INCORRECT | The reader casts before the comparison: Entity.php:56 `return (string) $this->product->get_meta( self::META_AGE_VERIFICATION_18_PLUS ) === '1';`. An int `1` becomes `'1'` and matches |
| 99-101 | The reader of the disallowed carriers validates only the type of the value, not its content [Entity.php#getDisallowedShippingRateChoices] | CONFIRMED | Entity.php:66-68 `if ( ! is_array( $choices ) ) { return []; }` and nothing else |
| 101-102 | The module contains no TODO comment and no FIXME comment | CONFIRMED | a recursive grep for TODO and for FIXME over `src/Packetery/Module/Product/` returns 0 lines |

CONFIRMED 43 · INCORRECT 7 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### manifest.part.packetery-module-product.yaml

The separate fragment no longer exists; the entries are verified in `ai-docs/manifest.yaml`.
Rows cover the `modules:` entry (manifest.yaml:190-193) and every `exposes:` / `owns_data:` entry
whose `module:` is `packetery-module-product`. No entry carries
`resolved_by: packetery-module-product`, and the module has no `calls:` and no event entry.
Entries of `packetery-module-productcategory` (manifest.yaml:194-197, exposes:419-425) are a
different module and are not verified here.

| entry | class | evidence |
|---|---|---|
| `modules[].name: packetery-module-product`, `path: src/Packetery/Module/Product` | CONFIRMED | manifest.yaml:190-191; the directory exists and holds the 4 files of the module |
| `modules[].lines: 353` | CONFIRMED | manifest.yaml:192; non-empty non-comment lines of the 4 `src/` files = 353 |
| `modules[].doc: reference/packetery-module-product.md` | CONFIRMED | manifest.yaml:193; the file exists and its front matter has `module: packetery-module-product` |
| `exposes[] kind: library, name: getDisallowedShippingRateIds, module: packetery-module-product, evidence: src/Packetery/Module/Product/Entity.php#getDisallowedShippingRateIds` | CONFIRMED | manifest.yaml:412-417; Entity.php:78-80 |
| `exposes[] getDisallowedShippingRateIds · consumers: [woocommerce], auth: none` | CONFIRMED | manifest.yaml:414-416; a plain PHP method with no access check |
| `exposes[] getDisallowedShippingRateIds · notes: the checkout reads it for every cart item` | CONFIRMED | Checkout/CartService.php:121-131 collects the ids per cart product; Checkout/ShippingRateFactory.php:211-221 uses the result |
| `exposes[] kind: html, name: packetery-tab, module: packetery-module-product, evidence: src/Packetery/Module/Product/DataTab.php#registerTab` | CONFIRMED | manifest.yaml:513-519; DataTab.php:30 and :116-130 |
| `exposes[] packetery-tab · auth: wordpress-capability` | UNCERTAIN | No capability check in the module. The tab is registered only on the admin side: Hooks/HookRegistrar.php:396 `if ( $this->wpAdapter->isAdmin() )` → registerBackEnd, :532 `$this->productTab->register()`. The access rule is WordPress and WooCommerce screen behaviour, not code of this module |
| `exposes[] packetery-tab · consumers: [woocommerce]` | CONFIRMED | manifest.yaml:516-517; the tab is a WooCommerce product editor tab |
| `exposes[] packetery-tab · notes: holds the age verification and the disallowed carriers` | CONFIRMED | DataTab.php:141-150 adds the age verification checkbox and the carrier container |
| `owns_data[] entity: product-settings, store: mysql, module: packetery-module-product, evidence: src/Packetery/Module/Product/Entity.php#META_DISALLOWED_SHIPPING_RATES` | CONFIRMED | manifest.yaml:724-728; Entity.php:23; the values live in the WordPress `postmeta` table, which the grid filter joins (ProductGridExtender.php:174, :181) |
| `owns_data[] product-settings · notes: two metadata keys of a WooCommerce product` | CONFIRMED | Entity.php:22-23 declares exactly two keys; Uninstaller.php:127-128 deletes the same two |

CONFIRMED 11 · INCORRECT 0 · UNCERTAIN 1 · NOT FOUND 0 · PENDING 0 · SENSITIVE 0 · INSTRUCTION 0

Notes that are not rows:

- The `modules:` block of `ai-docs/manifest.yaml` (lines 113-213) has no `evidence:` key for any
  module, so the `evidence:` of this module could not be checked. This is a schema-wide gap, not a
  defect of this module, so it is not counted in the table.
- `packetery_product_list_filters_enabled` (ProductGridExtender.php:205) is a WordPress filter the
  module offers to a shop, and it has no entry in `exposes:`. The manifest does model such hooks
  (for example `packetery_cron_carriers_hook`, `kind: other`, manifest.yaml:522-525).
- `packetery-module-framework` is in `modules:` (manifest.yaml:162) but not in the top-level
  `aliases:` list (manifest.yaml:4-22).
