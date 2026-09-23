## ai-docs/reference/packetery-module-entityfactory.md @ 43b25221 — 2026-09-22

| line | claim | class | evidence |
|---|---|---|---|
| 17-18 | The module builds the entities of the domain module from WordPress, WooCommerce and plugin-database data. | CONFIRMED | `Address.php:27-29` reads WordPress options, `Carrier.php:37` reads a plugin table row, `CustomsDeclaration.php:27` reads a plugin table row. |
| 18-20 | A repository reads a row and hands it to a factory, which returns the entity. | CONFIRMED | `src/Packetery/Module/CustomsDeclaration/Repository.php:44` and `src/Packetery/Module/Carrier/EntityRepository.php:70` take the factory in the constructor. |
| 20-21 | The module holds 4 files. | CONFIRMED | `src/Packetery/Module/EntityFactory/` holds `Address.php`, `Carrier.php`, `CustomsDeclaration.php`, `SizeFactory.php`. |
| 21 | 142 lines of logic. | CONFIRMED | Non-blank non-comment lines = 142; `ai-docs/manifest.yaml:173` `lines: 142`. |
| 21 | 8 public methods. | CONFIRMED | 1 + 2 + 2 + 3 (`SizeFactory::__construct` included) = 8. |
| 23-25 | The mapping lives in one place, so a column or setting change touches one factory. | CONFIRMED | `CustomsDeclaration.php:27-44` holds the whole column mapping of the declaration. |
| 26 | The module writes nothing and reads no database of its own. | CONFIRMED | No `wpdb`, no `INSERT`/`UPDATE` and no `WpdbAdapter` in the four files; every method returns a `Packetery\Core\Entity\*`. |
| 28 | `> ⚠ add business context (elicitation)` | PENDING | Standard placeholder. |
| 32 | The module exposes four factories. | CONFIRMED | Four classes, one per file. |
| 36 | `fromWcStoreOptions` builds the address entity from the WooCommerce store settings. | CONFIRMED | `Address.php:25-33` reads `woocommerce_store_address`, `_city`, `_postcode`. |
| 37 | `fromDbResult` builds the carrier entity from a row of the carrier table and types its columns. | CONFIRMED | `Carrier.php:37-58` casts to `(bool)` and `(float)`. |
| 38 | `fromNonFeedCarrierData` builds the carrier entity of a pickup point provider that no feed contains. | CONFIRMED | `Carrier.php:67` takes `BaseProvider $nonFeedCarrierProvider`. |
| 39 | `fromStandardizedStructure` builds the declaration entity "from a row and the **order number**". | INCORRECT | The code passes an order ID, not an order number: `CustomsDeclaration.php:27` `public function fromStandardizedStructure( array $data, string $orderId )`, and `src/Packetery/Core/Entity/CustomsDeclaration.php:125` `string $orderId`. |
| 40 | `createItemFromStandardizedStructure` builds one item of the declaration. | CONFIRMED | `CustomsDeclaration.php:52-68` returns `Entity\CustomsDeclarationItem`. |
| 41 | `createSizeInSetDimensionUnit` builds the size entity of an order in the unit the shop selected. | CONFIRMED | `SizeFactory.php:27-45` converts only when `getDimensionsUnit() === OptionsProvider::DIMENSIONS_UNIT_CM`. |
| 42 | `createDefaultSizeForNewOrder` builds the size entity from the default dimensions of the plugin. | CONFIRMED | `SizeFactory.php:47-53` reads `getDefaultLength/Width/Height()`. |
| 44-45 | The carrier factory decides age verification from a fixed list of identifiers. | CONFIRMED | `Carrier.php:25-28` `private const AGE_VERIFIED_CARRIERS`, used at `:38` `in_array( $dbResult['id'], self::AGE_VERIFIED_CARRIERS, true )`. Holds for the feed path only; `fromNonFeedCarrierData` asks the provider (`:84`). |
| 45-47 | The internal carrier factory sets a fixed weight limit and several fixed flags. | CONFIRMED | `Carrier.php:72-83` passes six literal `false`, a literal `10` and `true`/`false`. |
| 51 | The module depends on the entities of the domain module and on the settings. | CONFIRMED | `use Packetery\Core\Entity`, `use Packetery\Module\Options\OptionsProvider`. |
| 54 | `references → packetery-core` | CONFIRMED | `Carrier.php:12-13`, `CustomsDeclaration.php:12-13`, `SizeFactory.php:12-13`, `Address.php:12`. |
| 55 | `references → packetery-module-options` | CONFIRMED | `SizeFactory.php:15` `use Packetery\Module\Options\OptionsProvider`. |
| 56 | `references → packetery-module-framework` | INCORRECT | No file of the module names the framework. `grep -rn "Framework" src/Packetery/Module/EntityFactory/` returns nothing, and the module is one of the five with no `WpAdapter`/`WcAdapter` reference at all. |
| 54-56 | The three `references →` lines are the module's outgoing edges. | INCORRECT | `packetery-module-root` is missing: `SizeFactory.php:14` `use Packetery\Module\ModuleHelper;`, used at `:38` and `:48-50`. `ModuleHelper.php` sits in `src/Packetery/Module`, which `ai-docs/manifest.yaml:219-220` maps to `packetery-module-root`. |
| 58-59 | The factories return the address, carrier, customs declaration and size entity of `packetery-core`. | CONFIRMED | `Entity\Address`, `Entity\Carrier`, `Entity\CustomsDeclaration`, `Entity\Size`. Anchor `SizeFactory.php#createDefaultSizeForNewOrder` covers the size entity only. |
| 60-61 | The size factory reads the dimension unit and the default dimensions from `packetery-module-options`. | CONFIRMED | `SizeFactory.php:36` `getDimensionsUnit()`, `:48-50` `getDefaultLength/Width/Height()`. |
| 61-62 | The address factory reads the store settings "through the adapters of `packetery-module-framework`". | INCORRECT | It calls the WordPress global directly. `Address.php:26-30`: `new Entity\Address( get_option( 'woocommerce_store_address', null ), get_option( 'woocommerce_store_city', null ), get_option( 'woocommerce_store_postcode', null ) )`. The file imports only `Packetery\Core\Entity` (`Address.php:12`); there is no adapter and no constructor. |
| 63-64 | "The carrier repository, the customs declaration repository and the order module call these factories." | INCORRECT | The checkout module is missing: `src/Packetery/Module/Checkout/OrderUpdater.php:10,88` `use Packetery\Module\EntityFactory\SizeFactory;` and takes it in the constructor. The other three are right (`Carrier/EntityRepository.php:70`, `CustomsDeclaration/Repository.php:44`, `Order/GridExtender.php:52`, `Order/CollectionPrint.php:94`, `Order/CustomsDeclarationMetabox.php:117`). |
| 64-66 | The module returns an entity and never writes one, so the caller saves through its own repository. | CONFIRMED | No write path in the four files; `CustomsDeclaration/Repository.php` holds the save. |
| 70-72 | The age-verification list is a constant of the factory, so a new such carrier needs a code change. | CONFIRMED | `Carrier.php:25` `private const AGE_VERIFIED_CARRIERS`; nothing reads it from a setting. |
| 72-74 | The weight limit and several flags of an internal carrier are fixed values of the same file. | CONFIRMED | `Carrier.php:72-83`. |
| 74-75 | "so an internal carrier accepts the same packet **size** in every country". | INCORRECT | The fixed value is a weight limit, not a size. `Carrier.php:81` passes `10` into the `maxWeight` position that `fromDbResult` fills from `(float) $dbResult['max_weight']` (`Carrier.php:53`); the `requiresSize` flag is passed as `false` (`:77`) and no dimension is set. |
| 77-78 | The factories trust the shape of their input; a missing or renamed column gives no error of this module. | CONFIRMED | `CustomsDeclaration.php:53-61` indexes `$data['customs_code']`, `$data['value']`, `$data['weight']` with no `??` and no guard; only `$data['id']` uses `?? null` (`:62`). |
| 79 | The module contains no TODO comment and no FIXME comment. | CONFIRMED | `grep -rn "TODO\|FIXME" src/Packetery/Module/EntityFactory/` returns nothing (rc=1). |

CONFIRMED 26 · INCORRECT 6 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### ai-docs/manifest.yaml — packetery-module-entityfactory

| entry | class | evidence |
|---|---|---|
| `aliases:` `packetery-module-entityfactory` (manifest.yaml:13) | CONFIRMED | Matches the `module:` front matter and the `modules:` entry. |
| `modules:` `path: src/Packetery/Module/EntityFactory`, `lines: 142`, `doc: reference/packetery-module-entityfactory.md` (manifest.yaml:171-174) | CONFIRMED | Path exists; 142 = non-blank non-comment lines of the four files. |
| `exposes:` `createSizeInSetDimensionUnit` — kind library, consumers woocommerce, auth none, evidence `SizeFactory.php#createSizeInSetDimensionUnit`, notes "Builds the size entity of an order in the dimension unit of the shop." (manifest.yaml:416-422) | CONFIRMED | `SizeFactory.php:27` `public function createSizeInSetDimensionUnit( Entity\Order $order ): Entity\Size`. |
| `exposes:` `fromDbResult` — kind library, consumers woocommerce, auth none, evidence `Carrier.php#fromDbResult`, notes "Builds the carrier entity of the domain module from a row of the carrier table." (manifest.yaml:433-439) | CONFIRMED | `Carrier.php:37` `public function fromDbResult( array $dbResult ): Entity\Carrier`; the caller is `Carrier/EntityRepository.php`. |

CONFIRMED 4 · INCORRECT 0 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 0 · SENSITIVE 0 · INSTRUCTION 0

Both tables: CONFIRMED 30 · INCORRECT 6 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0
