## ai-docs/reference/packetery-module-blocks.md @ 43b25221 — 2026-09-22

| line | claim | class | evidence |
|---|---|---|---|
| 17 | The module puts the Packeta widget into the block checkout of WooCommerce. | CONFIRMED | `BlockHooks.php:40-46` `registerCheckoutBlock()` registers `WidgetIntegration` in the `IntegrationRegistry`. |
| 17-18 | "The block checkout is a React application, so the plugin cannot render its fields with a template." | UNCERTAIN | The repository ships only the built bundle `public/block/index.js` and `public/block/index.asset.php`; no source in this repository shows the framework, and nothing states the "cannot render" consequence. |
| 18-20 | The module registers one integration of WooCommerce Blocks and gives it the script of the widget. | CONFIRMED | Registration: `BlockHooks.php:41-45`. Script: `WidgetIntegration.php:69-75` `wp_register_script( self::INTEGRATION_NAME, plugins_url( $scriptPath ), … )`. The anchor `WidgetIntegration.php#initialize` covers the script half only; the registration is in the other file. |
| 20-21 | The module holds 2 files. | CONFIRMED | `BlockHooks.php`, `WidgetIntegration.php`. |
| 21 | 124 lines of logic. | CONFIRMED | Non-blank non-comment lines = 124; `ai-docs/manifest.yaml:145` `lines: 124`. |
| 21 | 11 public methods. | CONFIRMED | `BlockHooks` 5 + `WidgetIntegration` 6, constructors included. |
| 23-25 | The module keeps the selection of the customer in the WooCommerce session, because the block checkout sends it through the Store API and not through a form. | CONFIRMED | `BlockHooks.php:77-87` writes both values with `sessionSet`; the method is wired as the Store API update callback at `:93-98`. |
| 27 | `> ⚠ add business context (elicitation)` | PENDING | Standard placeholder. |
| 31 | The module exposes the integration of the block checkout and its hooks. | CONFIRMED | Two classes: `WidgetIntegration` and `BlockHooks`. |
| 35 | `registerCheckoutBlock` registers the Packeta integration in the block registry. | CONFIRMED | `BlockHooks.php:40-46` `$integrationRegistry->register( new Blocks\WidgetIntegration( … ) )`. |
| 36 | `register` adds the Packeta block to the blocks that may carry data attributes. | CONFIRMED | `BlockHooks.php:48-66`. The row leaves out the third thing the method does: `:68-74` also adds the `woocommerce_blocks_checkout_block_registration` action that calls `registerCheckoutBlock`. |
| 37 | `saveShippingAndPaymentMethodsToSession` stores the shipping and the payment method and recomputes the cart. | CONFIRMED | `BlockHooks.php:78-86`, ending in `$this->wcAdapter->cartCalculateTotals()`. |
| 38 | `orderUpdateCallback` registers the update callback of the Store API when WooCommerce offers one. | CONFIRMED | `BlockHooks.php:89-99`, guarded by `function_exists( 'woocommerce_store_api_register_update_callback' )`. |
| 39 | `get_name` gives the name of the integration to WooCommerce Blocks. | CONFIRMED | `WidgetIntegration.php:50-52` returns `self::INTEGRATION_NAME`. |
| 40 | `initialize` registers the block script and takes its version from the build artefact. | CONFIRMED | `WidgetIntegration.php:59-76`; the version comes from `index.asset.php` when it exists, otherwise from `get_file_version()`. |
| 41 | `get_script_handles`, `get_editor_script_handles` give the script handle to the front end and to the editor. | CONFIRMED | `WidgetIntegration.php:83-94`, both return `[ self::INTEGRATION_NAME ]`. |
| 42 | `get_script_data` gives the widget settings of the checkout module to the browser. | CONFIRMED | `WidgetIntegration.php:101-103` returns `$this->settings`, set from `CheckoutSettings::createSettings()` (`BlockHooks.php:43`). |
| 43 | `get_file_version` is listed as a member of the public interface. | INCORRECT | It is not public: `WidgetIntegration.php:112` `protected function get_file_version( string $filePath )`. The behaviour text is also short of a condition — `:113` is `if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $filePath ) )`, so the file must exist as well, and the argument it gets is the URL path `'/packeta/public/block/index.js'` (`:60`). |
| 45-47 | The module registers two filters for the data attributes: the current one and the name expected later. | CONFIRMED | `BlockHooks.php:49-66`, with the comments `// This hook is tested.` and `// This hook is expected replacement in the future.`. |
| 51-52 | The module depends on the checkout module for its data and on WooCommerce Blocks for its interface. | CONFIRMED | `BlockHooks.php:7,9`; `WidgetIntegration.php:10,26`. |
| 54 | `references → packetery-module-root` | CONFIRMED | `WidgetIntegration.php:11` `use Packetery\Module\Plugin;`, used at `:117`; `Plugin.php` sits in `src/Packetery/Module`, which `ai-docs/manifest.yaml:219-220` maps to `packetery-module-root`. |
| 55 | `references → packetery-module-checkout` | CONFIRMED | `BlockHooks.php:9` `use Packetery\Module\Checkout\CheckoutSettings;`. |
| 56 | `references → packetery-module-framework` | CONFIRMED | `BlockHooks.php:10-11` imports `WcAdapter` and `WpAdapter`. The set is complete: these are the only `Packetery\` imports of the two files. |
| 58-59 | The settings the block script receives come from `packetery-module-checkout`; this module only hands them over. | CONFIRMED | `BlockHooks.php:43` `$this->checkoutSettings->createSettings()` → `WidgetIntegration.php:42` `$this->settings = $settings;` → `:102` `return $this->settings;`. |
| 60-61 | The session write and the cart recomputation go through the adapters of `packetery-module-framework`. | CONFIRMED | `BlockHooks.php:79,83` `$this->wcAdapter->sessionSet(…)`, `:86` `$this->wcAdapter->cartCalculateTotals()`. |
| 62-63 | The script version falls back to the version constant of `packetery-module-root`. | CONFIRMED | `WidgetIntegration.php:117` `return Plugin::VERSION;`; `src/Packetery/Module/Plugin.php:22` `public const VERSION`. |
| 63-66 | The hook module calls the registration on the front end and the Store API callback on its own hook, because that call cannot move into this module. | CONFIRMED | `src/Packetery/Module/Hooks/HookRegistrar.php:595` `private function registerFrontEnd()`, `:618` `addAction( 'init', [ $this->blockHooks, 'register' ] )`, `:619` `// Cannot be moved to BlockHooks register.`, `:620` `addAction( 'woocommerce_blocks_loaded', [ $this->blockHooks, 'orderUpdateCallback' ] )`. |
| 66-67 | The script of the block is a build artefact and no PHP of this module renders it. | CONFIRMED | `public/block/index.js` plus `index.asset.php`; neither file of the module emits markup. |
| 71-74 | The integration interface is loaded with a manual include from two possible paths. | CONFIRMED | `WidgetIntegration.php:13-19`, two `file_exists` / `require_once` pairs. The anchor names `#IntegrationInterface`, which the file imports (`:10`) but does not define; the includes are at file level. |
| 73 | "because the autoloader of the shop does not always offer it". | UNCERTAIN | No comment or test in the repository states the reason. |
| 74-75 | "A third path of a future WooCommerce release would need another change." | UNCERTAIN | A reasonable consequence of the hard-coded paths, but it describes a future release, not this code. |
| 77-79 | The Store API callback is registered only when the WooCommerce function exists, and an older shop gets no callback and no message. | CONFIRMED | `BlockHooks.php:90-92` returns early with no notice and no log. |
| 79-81 | The block name and the two session keys are fixed strings of the module. | CONFIRMED | `WidgetIntegration.php:27` `const INTEGRATION_NAME`; the block name literal is at `BlockHooks.php:53` and `:62`, the two session keys at `:79` and `:83`. The anchor `WidgetIntegration.php#INTEGRATION_NAME` covers the integration name only; the block name and both session keys are in the other file. |
| 81-82 | The module contains no TODO comment and no FIXME comment. | CONFIRMED | `grep -rn "TODO\|FIXME" src/Packetery/Module/Blocks/` returns nothing (rc=1). |

CONFIRMED 29 · INCORRECT 1 · UNCERTAIN 3 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### ai-docs/manifest.yaml — packetery-module-blocks

| entry | class | evidence |
|---|---|---|
| `aliases:` `packetery-module-blocks` (manifest.yaml:7) | CONFIRMED | Matches the `module:` front matter and the `modules:` entry. |
| `modules:` `path: src/Packetery/Module/Blocks`, `lines: 124`, `doc: reference/packetery-module-blocks.md` (manifest.yaml:143-146) | CONFIRMED | Path exists; 124 = non-blank non-comment lines of the two files. |
| `exposes:` `get_script_data` — kind other, consumers woocommerce, auth none, evidence `WidgetIntegration.php#get_script_data`, notes "Gives the widget settings to the script of the block checkout." (manifest.yaml:509-515) | CONFIRMED | `WidgetIntegration.php:101` `public function get_script_data(): array`, part of `IntegrationInterface`, so WooCommerce Blocks calls it. |
| `exposes:` `registerCheckoutBlock` — kind other, consumers woocommerce, auth none, evidence `BlockHooks.php#registerCheckoutBlock`, notes "Registers the Packeta integration in the block checkout of WooCommerce." (manifest.yaml:647-653) | CONFIRMED | `BlockHooks.php:40` `public function registerCheckoutBlock( IntegrationRegistry $integrationRegistry ): void`, hooked on `woocommerce_blocks_checkout_block_registration` (`:68-74`). |

CONFIRMED 4 · INCORRECT 0 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 0 · SENSITIVE 0 · INSTRUCTION 0

Both tables: CONFIRMED 33 · INCORRECT 1 · UNCERTAIN 3 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0
