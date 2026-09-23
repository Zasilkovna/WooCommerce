## ai-docs/reference/packetery-module-productcategory.md @ b34fe03c — 2026-09-22

| line | claim | class | evidence |
|---|---|---|---|
| 17 | The module disallows carriers for a whole product category | CONFIRMED | `Entity.php:19` `META_DISALLOWED_SHIPPING_RATES`, `FormFields.php:106-115` builds one checkbox per active carrier |
| 17-19 | The shop owner opens a category and selects the carriers that must not deliver [anchor `FormFields.php#render`] | CONFIRMED | `FormFields.php:133-146` renders `template/product_category/form-fields.latte` with the form of `createForm()` (`:103-124`) |
| 19-21 | The checkout removes those carriers for a cart that holds such goods [anchor `Entity.php#getDisallowedShippingRateIds`] | CONFIRMED | `Checkout/CartService.php:203-214` reads the getter per cart-item category, `Checkout/ShippingRateFactory.php:223-225` drops the rate when `isShippingRateRestrictedByProductsCategory` is true |
| 23 | The module holds the same rule as the product module, one level higher | CONFIRMED | `Product/Entity.php:23,64-79` same getter pair on post meta |
| 24 | 4 files | CONFIRMED | `find src/Packetery/Module/ProductCategory -name '*.php' \| wc -l` = 4 (src only, no tests) |
| 24 | 213 lines of logic | CONFIRMED | non-empty non-comment lines of those 4 files = 213; matches `manifest.yaml:196` `lines: 213` |
| 24 | 14 public methods | CONFIRMED | `grep -cE '^\s*(final )?public( static)? function'` over the 4 files = 14 |
| 24-25 | It owns the `category-settings` in one metadata key of a category [anchor `Entity.php#META_DISALLOWED_SHIPPING_RATES`] | CONFIRMED | `Entity.php:19` is the only key of the module; `manifest.yaml:692-696` `owns_data` entity `category-settings` |
| 26-28 | A category rule covers every product of that category, so the rule is set once instead of on each product [anchor `ProductCategoryEntityFactory.php#fromTermId`] | INCORRECT | anchor does not evidence the claim: `ProductCategoryEntityFactory.php:17-25` only builds an `Entity` from a term id. The coverage lives outside the module, in `Checkout/CartService.php:203-212` (`$product->get_category_ids()` loop) |
| 30 | `> ⚠ add business context (elicitation)` | PENDING | standard placeholder |
| 34-35 | The module exposes the form fields of a category, the entity and the grid column | CONFIRMED | `FormFields.php`, `Entity.php`, `CategoryGridExtender.php`; the fourth class is the factory, named in the table below |
| 39 | `register` adds the Packeta fields to the category form and saves them | CONFIRMED | `FormFields.php:89-94` hooks `product_cat_edit_form_fields`, `product_cat_add_form_fields`, `edit_term`, `created_term` |
| 40 | `render` shows the carrier checkboxes on the edit form and on the add form | CONFIRMED | `FormFields.php:90-91` both hooks call `render`; `:114` adds the checkboxes |
| 41 | `saveData` stores the selected carriers, and only for the product category taxonomy | CONFIRMED | `FormFields.php:174-176` early return when `$taxonomy !== Entity::TAXONOMY_NAME`; `:191` `update_term_meta` |
| 42 | `getDisallowedShippingRateIds`, `getDisallowedShippingRateChoices` give the carriers the category disallows | CONFIRMED | `Entity.php:43-59` |
| 43 | `getId` gives the term identifier of the category | CONFIRMED | `Entity.php:66-68` returns `term_id` |
| 44 | `fromTermId` builds the entity, or gives none when the term is missing | CONFIRMED | `ProductCategoryEntityFactory.php:17-25` returns `null` when the term is not a `WP_Term` |
| 45 | `addCategoryListColumns`, `fillCategoryListColumn` add the restriction column and fill it | CONFIRMED | `CategoryGridExtender.php:32-42,49-69`; registered in `Hooks/HookRegistrar.php:427-428` |
| 46 | `hideCategoryListColumnByDefault` hides the column until the user shows it | CONFIRMED | `CategoryGridExtender.php:76-92` appends the column to `default_hidden_columns` on screen `edit-product_cat` |
| 48-49 | The metadata key of the category differs from the key of the product, so the two settings never mix [anchor `Entity.php#META_DISALLOWED_SHIPPING_RATES`] | CONFIRMED | `packetery_disallowed_shipping_rates_by_cat` (`Entity.php:19`, term meta) vs `packetery_disallowed_shipping_rates` (`Product/Entity.php:23`, post meta) |
| 50-51 | The save ignores the quick edit of the category list [anchor `Entity.php#TAXONOMY_NAME`] | INCORRECT | anchor does not evidence the claim: `Entity.php:20` is the constant `TAXONOMY_NAME = 'product_cat'` and says nothing about quick edit. The behaviour is `FormFields.php:178-180` `if ( $this->httpRequest->getPost( 'action' ) === 'inline-save-tax' ) { return; }` |
| 50-51 | The save accepts only the `product_cat` taxonomy | CONFIRMED | `FormFields.php:174-176` compared against `Entity.php:20` `TAXONOMY_NAME = 'product_cat'` |
| 55 | The module depends on the carrier list and on the platform helpers | CONFIRMED | `FormFields.php:14` `Carrier\EntityRepository`, `:13` `Carrier\CarDeliveryConfig`, `CategoryGridExtender.php:7` `Framework\WpAdapter` |
| 58 | references → packetery-module-root | CONFIRMED | `FormFields.php:16` `Packetery\Module\FormFactory` (path `src/Packetery/Module`, `manifest.yaml:198-200`) |
| 59 | references → packetery-module-carrier | CONFIRMED | `FormFields.php:13-15` `CarDeliveryConfig`, `EntityRepository`, `OptionPrefixer` |
| 60 | references → packetery-module-log | CONFIRMED | `FormFields.php:17` and `CategoryGridExtender.php:8` `Log\ArgumentTypeErrorLogger` |
| 58-60 | the three `references →` lines are the module's dependencies (completeness) | INCORRECT | `packetery-module-framework` is missing: `CategoryGridExtender.php:7,16,21` and `ProductCategoryEntityFactory.php:7,11,18` use `Packetery\Module\Framework\WpAdapter`, and `manifest.yaml:162-165` lists `packetery-module-framework` (`src/Packetery/Module/Framework`) as a module |
| 62-64 | The checkbox list holds the active carriers of `packetery-module-carrier` and leaves out the car delivery carriers the shop disabled [anchor `FormFields.php#saveData`] | CONFIRMED | `FormFields.php:107-115` `getAllActiveCarriersList()` and the `isCarDeliveryCarrierDisabled()` skip, in the private `createForm()` that `saveData:186` calls |
| 64-66 | Wrong hook arguments go to the logger of `packetery-module-log` | CONFIRMED | `CategoryGridExtender.php:33-37` `argumentTypeErrorLogger->log(...)`; same in `FormFields.php:157,163,169` |
| 67 | The checkout reads this module for the categories of every cart item | CONFIRMED | `Checkout/CartService.php:195-212` loops the cart products and their `get_category_ids()` |
| 67-68 | The product module holds the same rule for one product | CONFIRMED | `Product/Entity.php:23,64-79`; used in `Checkout/CartService.php:117-131` |
| 68-70 | The form comes from the form factory of `packetery-module-root` and the module adds only its own field [anchor `FormFields.php#render`] | CONFIRMED | `FormFields.php:104` `$this->formFactory->create()`, `:106` one container for the own meta key, no other field |
| 70-71 | No module writes this metadata except the category form | CONFIRMED | `grep -rn 'update_term_meta\|delete_term_meta' src` returns only `FormFields.php:191`; `grep -rn 'disallowed_shipping_rates_by_cat' src template` returns only `Entity.php:19` |
| 75-77 | The reader validates only that the stored value is an array, so another shape gives an empty list without an error | CONFIRMED | `Entity.php:44-49` `if ( ! is_array( $choices ) ) { return []; }`, no error path |
| 79-80 | The category list holds no filter for the restriction, although the product list has one | CONFIRMED | `Hooks/HookRegistrar.php:427-429` registers only column filters for the category grid, and `CategoryGridExtender.php` has no filter renderer; `Product/ProductGridExtender.php:24,128-130,148-160,178-182` has `FILTER_SHIPPING_RESTRICTIONS` with a template and a SQL clause |
| 81-82 | The column name of this module equals the column name of the product list, and only the screen keeps them apart | CONFIRMED | both are `packetery_shipping_restrictions` (`CategoryGridExtender.php:12`, `Product/ProductGridExtender.php:21`); the hooks and screens differ: `HookRegistrar.php:427` `manage_edit-product_cat_columns`, `CategoryGridExtender.php:85` screen `edit-product_cat` vs `ProductGridExtender.php:108` screen `edit-product` |
| 83-85 | The column shows only whether a restriction exists | CONFIRMED | `CategoryGridExtender.php:65-68` prints `Yes` or `—`, never the carrier names |
| 86 | The module contains no TODO comment and no FIXME comment | CONFIRMED | `grep -rcE 'TODO\|FIXME' src/Packetery/Module/ProductCategory/` = 0 in all 4 files |

CONFIRMED 34 · INCORRECT 3 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### manifest.part.packetery-module-productcategory.yaml

The separate fragment no longer exists; the entries below are the records of this module in
`ai-docs/manifest.yaml`. `packetery-module-product` records were excluded.

| entry | class | evidence |
|---|---|---|
| `aliases: packetery-module-productcategory` (manifest.yaml:19) | CONFIRMED | the namespace `Packetery\Module\ProductCategory` exists, `src/Packetery/Module/ProductCategory` holds 4 PHP files |
| `modules[packetery-module-productcategory].path: src/Packetery/Module/ProductCategory` (manifest.yaml:195) | CONFIRMED | directory exists and matches the `covers:` of the document |
| `modules[…].lines: 213` (manifest.yaml:196) | CONFIRMED | non-empty non-comment lines of the 4 `src/` files = 213 |
| `modules[…].doc: reference/packetery-module-productcategory.md` (manifest.yaml:197) | CONFIRMED | the file exists and its front matter carries `module: packetery-module-productcategory` |
| `modules[…].evidence:` | NOT FOUND | no `modules:` entry in this manifest carries `evidence:` (`grep -c 'evidence:'` over lines 113-213 = 0), so the field is absent by schema, not only for this module |
| `exposes: kind: library, name: getDisallowedShippingRateIds, module: packetery-module-productcategory, evidence: Entity.php#getDisallowedShippingRateIds` (manifest.yaml:420-426) | CONFIRMED | `Entity.php:57-59`; notes "Gives the carriers that a product category disallows" match the body. The record of the same method name for `packetery-module-product` (manifest.yaml:414-419) is a different module and is correctly separated |
| `exposes: kind: html, name: product_cat, module: packetery-module-productcategory, auth: wordpress-capability, evidence: Entity.php#TAXONOMY_NAME` (manifest.yaml:565-571) | CONFIRMED | `Entity.php:20` `TAXONOMY_NAME = 'product_cat'`; the fields render on the WordPress term screens `product_cat_edit_form_fields` / `product_cat_add_form_fields` (`FormFields.php:90-91`), which WordPress gates by the term capability — the module itself holds no capability check |
| `owns_data: entity: category-settings, store: mysql, module: packetery-module-productcategory, evidence: Entity.php#META_DISALLOWED_SHIPPING_RATES` (manifest.yaml:692-696) | CONFIRMED | `Entity.php:19,44` `get_term_meta` and `FormFields.php:191` `update_term_meta` write one key of the WordPress term meta table |
| `calls:` — no entry for this module (manifest.yaml:640-683) | CONFIRMED | the module calls no external service; it uses only in-repo services (`FormFactory`, `Carrier\EntityRepository`, `WpAdapter`, Latte, Nette) |
| `unknowns:` — "Whether a category restriction must win over a product restriction", `resolved_by: a person owning the catalogue` (manifest.yaml:780-783) | CONFIRMED | the two keys are read separately, `Checkout/CartService.php:117-131` (product) and `:203-214` (category), and `ShippingRateFactory.php:221-225` combines them with OR, so no precedence rule exists in the code |
| `resolved_by: packetery-module-productcategory` — no entry | CONFIRMED | `grep -c 'resolved_by: packetery-module-productcategory' ai-docs/manifest.yaml` = 0 |

CONFIRMED 10 · INCORRECT 0 · UNCERTAIN 0 · NOT FOUND 1 · PENDING 0 · SENSITIVE 0 · INSTRUCTION 0
