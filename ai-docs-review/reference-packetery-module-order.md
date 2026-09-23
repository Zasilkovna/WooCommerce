## ai-docs/reference/packetery-module-order.md @ 97720cfc — 2026-09-22

Verified against the code in the working tree of branch `docs/teams` at HEAD `3d44ef98`. `git diff 97720cfc..3d44ef98 -- src/Packetery/Module/Order` is empty, so the code is identical to the documented commit. All paths below are relative to the repository root; `Order/` stands for `src/Packetery/Module/Order/`.

The document and the manifest fragment are verified as committed at `3d44ef98`. While this pass ran, the working tree received an uncommitted edit that changes the three counts of line 27-28 to 36 files, 5101 lines and 171 public methods, and `lines:` of the manifest to 5101. Those values match this repository. The four rows below that class those counts INCORRECT are resolved by that edit once it is committed; the summary counts are not restated for it.

| line | claim | class | evidence |
|---|---|---|---|
| 17 | The module keeps the Packeta data of each WooCommerce order and connects it to the Packeta API | CONFIRMED | `Order/Repository.php` stores the row, `Order/PacketSubmitter.php:311` calls the API |
| 18 | The module owns one database table | CONFIRMED | one `CREATE TABLE` in the namespace: `Order/Repository.php:216` |
| 18-19 | It builds `Packetery\Core\Entity\Order` from that table and from `WC_Order` | CONFIRMED | `Order/Builder.php`: `public function build( WC_Order $wcOrder, stdClass $result ): Entity\Order` |
| 20-23 | "The module gives the administrator all screens that work with a packet. These screens are the order metabox, the columns and the bulk actions of the order grid, and the label print pages." | INCORRECT | The set is incomplete against this page's own table and prose: the customs declaration metabox (line 41), the handover protocol page (lines 55-57) and two modal windows (lines 57-59) are packet screens of the same module |
| 21 | Order metabox anchor | CONFIRMED | `Order/Metabox.php:158` `add_meta_boxes()`, `Order/Metabox.php:168` `'packetery_metabox'` |
| 22 | Grid columns and bulk actions anchor | CONFIRMED | `Order/GridExtender.php:397-402` adds the columns |
| 23 | Label print pages anchor | CONFIRMED | `Order/LabelPrint.php` `outputLabelsPdf()` renders the label page |
| 24-25 | The module sends the packet to Packeta and reads the packet status back | CONFIRMED | send: `Order/PacketSubmitter.php:311` `createPacket`; status read: `Order/PacketSynchronizer.php` `getPacketStatuses()`/`syncStatus()`, not the anchored method |
| 27 | packetery-module-order is the largest namespace in the repository | CONFIRMED | `ai-docs/ast/map.json`, lines per namespace: `Packetery\Module\Order` 5155, next `Tests\Module\Checkout` 3199 |
| 27 | It holds 44 files | INCORRECT | `git ls-tree -r --name-only 97720cfc -- src/Packetery/Module/Order` returns 36 files; `ai-docs/ast/map.json` counts 37 files in the namespace |
| 27-28 | 5949 lines of logic | INCORRECT | `ai-docs/ast/map.json` sums 5155 lines for the namespace; the raw file total is 8057 lines, the non-blank non-comment total 5101. No measure gives 5949 |
| 28 | 198 public methods | INCORRECT | `grep -hE '^\s*public function' Order/*.php` counts 167 (171 with `final`/`static` forms); `ai-docs/ast/map.json` lists 175 methods of any visibility |
| 28-29 | All classes are in one directory and the module has no deeper namespace | CONFIRMED | `find src/Packetery/Module/Order -type d` returns the directory itself only |
| 31 | `> ⚠ add business context (elicitation)` | PENDING | standard placeholder |
| 35-36 | The module exposes members as hook callbacks, admin screens and packet actions | CONFIRMED | covered by the rows below |
| 36 | The module registers no REST route of its own | CONFIRMED | no `register_rest_route` in `Order/*.php` |
| 40 | `packetery_metabox` shows the Packeta form and saves its fields | CONFIRMED | `Order/Metabox.php:168` registers the box, `Order/Metabox.php:515` saves after `current_user_can( 'edit_post', $orderId )` |
| 41 | `packetery_customs_declaration_metabox` is shown when the carrier needs a declaration | CONFIRMED | `Order/CustomsDeclarationMetabox.php` `addMetaBoxes()` returns early when `$order->getCarrier()->requiresCustomsDeclarations() === false` |
| 42 | Six grid columns | CONFIRMED | `Order/GridExtender.php:397-402` adds `packetery_weight`, `packetery`, `packetery_packet_id`, `packetery_packet_status`, `packetery_packet_stored_until`, `packetery_destination` |
| 43 | Grid filter links `packetery_to_submit`, `packetery_to_print` | CONFIRMED | `Order/GridExtender.php:99-124` |
| 44 | Four bulk actions | CONFIRMED | `Order/BulkActions.php:72-75` adds `ACTION_SUBMIT_TO_API` (`submit_to_api`), `LabelPrint::ACTION_PACKETA_LABELS`, `LabelPrint::ACTION_CARRIER_LABELS`, `CollectionPrint::ACTION_PRINT_ORDER_COLLECTION` |
| 45 | Three packet actions, each after a nonce check | CONFIRMED | `Order/PacketActionsCommonLogic.php:31-33` constants, `:84` `wp_verify_nonce( ..., self::createNonceAction( ... ) )` |
| 46 | `packetery_sync_order_status` reads the packet status of one order | CONFIRMED | `Order/PacketSynchronizer.php:28` hook name, `:114` `add_action( ..., [ $this, 'syncStatusById' ] )` |
| 47 | Auto submission hooks | CONFIRMED | `Order/PacketAutoSubmitter.php:24` `packetery_auto_submission_handle_event`, `:80` `woocommerce_order_status_completed`, `:97` `woocommerce_order_status_processing` |
| 48 | `woocommerce_rest_prepare_shop_order_object` adds carrier, pickup point and packet id | CONFIRMED | `Order/ApiExtender.php:45` filter, `:90-101` `carrier_id`, `point_id`, `point_name`, `packet_id` |
| 49 | `Repository` reads, saves and deletes the row and extends the grid query | CONFIRMED | `Order/Repository.php:419` `save()`, `:703` `delete()`, `:157` `processClauses()` |
| 51-55 | "The module gives three extension filters to other code" | INCORRECT | a fourth filter is applied in the same namespace: `Order/Metabox.php:268` `applyFilters( 'packeta_order_detail_show_run_wizard_button', true )` |
| 51-52 | `packeta_order_grid_links_settings` | CONFIRMED | `Order/GridExtender.php:89` |
| 52-53 | `packeta_create_packet` | CONFIRMED | `Order/PacketSubmitter.php:392`, in `preparePacketData()` which `submitPacket()` calls at `:292` |
| 54-55 | `packetery_exclude_orders_with_status` | CONFIRMED | `Order/Repository.php:136` |
| 55-57 | Label print page and handover protocol page are submenu pages under the Packeta dashboard | CONFIRMED | `Order/CollectionPrint.php:209` and `Order/LabelPrint.php:274` call `add_submenu_page( DashboardPage::SLUG, ... )` |
| 57-59 | Two modal windows save their data through the internal REST routes of packetery-module-api | CONFIRMED | `OrderRouter` (`src/Packetery/Module/Api/Internal/OrderRouter.php`) is used only by `Order/StoredUntilModal.php` and `Order/Modal.php` |
| 66 | references → packetery-core | CONFIRMED | 34 `use Packetery\Core\...` lines in the namespace |
| 67 | references → packetery-module-carrier | CONFIRMED | `use Packetery\Module\Carrier\...` in 6 files |
| 68 | references → packetery-module-options | CONFIRMED | `use Packetery\Module\Options\...` in 13 files |
| 69 | references → packetery-module-customsdeclaration | CONFIRMED | `Order/Builder.php:22`, `Order/CustomsDeclarationMetabox.php:16`, `Order/PacketSubmitter.php:14`, `Order/Repository.php:20` |
| 70 | references → packetery-module-labels | CONFIRMED | `use Packetery\Module\Labels\...` in 4 files |
| 71 | references → packetery-module-framework | CONFIRMED | `use Packetery\Module\Framework\...` in 16 files |
| 63-71 | The reference set of the dependencies section | INCORRECT | the set omits packetery-module-api, which the same page names at lines 57-59: `Order/StoredUntilModal.php:52` and `Order/Modal.php` use `Packetery\Module\Api\Internal\OrderRouter` |
| 73-75 | The module uses the SOAP client of packetery-core for every packet operation | CONFIRMED | `$this->soapApiClient->...` in `PacketSubmitter`, `PacketCanceller`, `PacketSynchronizer`, `PacketClaimSubmitter`, `PacketSetStoredUntil`, `LabelPrint`, `CollectionPrint` |
| 75-77 | Carrier definition and fixed pickup point configuration come from packetery-module-carrier | CONFIRMED | `Order/Builder.php`: `$this->pickupPointsConfig->getFixedCarrierId(...)`, `$this->carrierRepository->getAnyById(...)` |
| 77-79 | Plugin settings, packet status mapping and custom currency rates come from packetery-module-options | CONFIRMED | `Order/CurrencyConversion.php` `isCustomCurrencyRatesEnabled()`, `Order/WcOrderActions.php` `getAutoOrderStatusFromMapping()` |
| 80-81 | The module calls WordPress and WooCommerce back through the adapters of packetery-module-framework | CONFIRMED | `Order/WcOrderActions.php:13,47` `WcAdapter`, used at `:108` |
| 82-83 | Long operations are planned as Action Scheduler jobs | CONFIRMED | `Order/PacketSynchronizer.php:132` `$this->wcAdapter->asScheduleSingleAction(...)`; also `Order/PacketAutoSubmitter.php:169` `as_schedule_single_action(...)` |
| 87-89 | The table name comes from the `WpdbAdapter` property `packeteryOrder`, the schema statement is in the module | CONFIRMED | `Order/Repository.php:216` `'CREATE TABLE ' . $this->wpdbAdapter->packeteryOrder` |
| 93 | `id` bigint(20) unsigned not null, primary key, equal to the WooCommerce order id | CONFIRMED | `Order/Repository.php:217`, `:246` `PRIMARY KEY  (id)`, `:386` `'id' => (int) $order->getNumber()` |
| 94 | `carrier_id` varchar(15) not null | CONFIRMED | `Order/Repository.php:218` |
| 95 | `packet_id`, `packet_claim_id`, `packet_claim_password` varchar null | CONFIRMED | `Order/Repository.php:220-222` |
| 96 | `is_exported`, `is_label_printed`, `address_validated`, `adult_content` tinyint(1) | CONFIRMED | `Order/Repository.php:219,223,231,237` |
| 97 | Seven pickup point columns, varchar null | CONFIRMED | `Order/Repository.php:224-230` |
| 98 | `delivery_address` text null, JSON with `street`, `city`, `zip`, `houseNumber`, `longitude`, `latitude`, `county` | CONFIRMED | `Order/Repository.php:232`; `Order/Builder.php:144-154` decodes exactly these keys |
| 99 | `weight`, `length`, `width`, `height` float null, changeable by the administrator | CONFIRMED | `Order/Repository.php:233-236`; `Order/Form.php:18-24` form fields |
| 100 | `value`, `cod` double null | CONFIRMED | `Order/Repository.php:238-239`; `Order/CreatePacketMapper.php:69,89` |
| 101 | `packet_status`, `stored_until`, `deliver_on`, `carrier_number`, `car_delivery_id` are "state that the synchronisation writes back" | INCORRECT | the synchronisation writes only two of them: `Order/PacketSynchronizer.php` `syncStatus()` calls `setStoredUntil()` and `setPacketStatus()` before `save()`. `deliver_on` comes from the metabox form (`Order/Metabox.php:576`), `car_delivery_id` from the checkout attribute (`Order/Builder.php:160`), `carrier_number` is cleared on cancellation (`Order/PacketCanceller.php:254`) |
| 102 | `api_error_message` text, `api_error_date` datetime, null | CONFIRMED | `Order/Repository.php:240-241` |
| 104-105 | The mapping from the entity to the columns is one method | CONFIRMED | `Order/Repository.php` `orderToDbArray()` builds the whole `$data` array |
| 105-107 | Field keys such as `POINT_ID` and `CAR_DELIVERY_ID` | CONFIRMED | `Order/Attribute.php:19,39` |
| 107-109 | The grid query joins the two tables and respects the HPOS setting | CONFIRMED | `Order/Repository.php` `getWcOrderJoinClause()` branches on `ModuleHelper::isHposEnabled()` |
| 115 | calls → packeta-api (sync, SOAP) | CONFIRMED | `Order/PacketSubmitter.php:311` and the other SOAP call sites |
| 116 | calls → packeta-widget-api (sync, REST) | CONFIRMED | `Order/PickupPointValidator.php:55` `PickupPointValidate::createWithValidApiKey(...)` |
| 118-119 | `createPacket` and `createStorageFile` for a new packet | CONFIRMED | `Order/PacketSubmitter.php:311` and `:224` inside `submitPacket()` |
| 120 | `cancelPacket` for a cancellation | CONFIRMED | `Order/PacketCanceller.php:197` `$this->soapApiClient->cancelPacket( $request )`; the anchored `shouldRevertSubmission()` at `:299` only evaluates the response |
| 121 | `packetStatus` for the status | CONFIRMED | `Order/PacketSynchronizer.php` `syncStatus()` |
| 122-123 | `createPacketClaimWithPassword` for a claim packet | CONFIRMED | `Order/PacketClaimSubmitter.php` |
| 124-125 | `packetSetStoredUntil` for a longer storage time | CONFIRMED | `Order/PacketSetStoredUntil.php` |
| 125-127 | `packetsLabelsPdf` and `packetsCarrierLabelsPdf` for the label documents | CONFIRMED | `Order/LabelPrint.php` |
| 127-129 | `createShipment` with `barcodePng` for the handover protocol | CONFIRMED | `Order/CollectionPrint.php` |
| 131-132 | The module validates a selected pickup point over the REST interface | CONFIRMED | `Order/PickupPointValidator.php` `validate()` |
| 132-133 | The API key comes from packetery-module-options, no address or credential is in this module | CONFIRMED | `Order/PickupPointValidator.php:53` and `Order/Metabox.php:607,627` read `$this->optionsProvider->get_api_key()`; no literal credential in the namespace |
| 137-139 | The pickup point validation fails open on an empty API key and on a REST error | CONFIRMED | `Order/PickupPointValidator.php`: both the `InvalidApiKeyException` and the `RestException` branch `return new PickupPointValidateResponse( true, [] )` |
| 139-141 | The list of packet statuses is hardcoded | CONFIRMED | `Order/PacketSynchronizer.php` `getPacketStatuses()` returns a literal array of `PacketStatus` objects |
| 141-143 | The country to currency mapping is hardcoded | CONFIRMED | `Order/CurrencyConversion.php` `getCurrencyByCountry()` returns a literal `$mapping` array |
| 145-147 | The handover protocol transient has no expiration, the label print transients have a time to live | CONFIRMED | `Order/BulkActions.php:109` `set_transient( CollectionPrint::getOrderIdsTransientName(), $postIds )` versus `:120-121` with `60 * 60` |
| 147-149 | The upload limit of a customs declaration file is a class constant | CONFIRMED | `Order/CustomsDeclarationMetabox.php:38` `private const MAX_UPLOAD_FILE_MEGABYTES = 16;` |
| 149-151 | The condition that shows the carrier modal does not agree with the comment above it | CONFIRMED | `Order/CarrierModal.php`: the docblock says "There must be two carriers at least and no packet id", the code returns false only when `$carrierOptions === []` and then checks the packet id |
| 151-152 | The module contains no TODO comment and no FIXME comment | CONFIRMED | `grep -rc 'TODO\|FIXME' Order/*.php` matches nothing |
| 154-156 | The grid and bulk action classes give only callbacks, the registration is outside this module | CONFIRMED | no `add_filter`/`add_action` in `Order/GridExtender.php` and `Order/BulkActions.php` |

CONFIRMED 69 · INCORRECT 7 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### manifest.part.packetery-module-order.yaml

| entry | class | evidence |
|---|---|---|
| `service: woocommerce` | CONFIRMED | `ai-docs/ast/map.json` `repo: woocommerce`, matches the document front matter |
| `source_commit: 97720cfc` | CONFIRMED | the commit exists on `docs/teams` and matches the front matter of the document |
| `aliases: [packetery-module-order]` | CONFIRMED | same id as the document front matter and the `modules` entry |
| assumption "Generated on branch docs/teams at commit 97720cfc." | CONFIRMED | `git rev-parse --abbrev-ref HEAD` is `docs/teams`, the commit is an ancestor of HEAD |
| assumption about `--split-psr4` and the per-module counts | UNCERTAIN | the committed `ai-docs/ast/map.json` has no PSR-4 projects, so the "without --split-psr4" half holds, but its `source-commit` is `11523b71...`, not `97720cfc`, and the claimed second run is not in the repository |
| assumption "The module holds 5949 lines of logic, which is above the 3000 line limit" | INCORRECT | the line count is wrong: 5155 by `ai-docs/ast/map.json`, 8057 raw, 5101 non-blank non-comment. The module is above 3000 lines by every one of those measures |
| assumption "The STE skill ... was invoked for the prose" | UNCERTAIN | no record of a skill invocation in this repository |
| `modules:` entry packetery-module-order | INCORRECT | `path` and `doc` resolve, but `lines: 5949` matches no measure of `src/Packetery/Module/Order` (see the row above) |
| `exposes: packetery_metabox` | CONFIRMED | `Order/Metabox.php:168`; `auth: wordpress-capability` is shown by `current_user_can( 'edit_post', $orderId )` at `:515` |
| `exposes: packetery_customs_declaration_metabox` | UNCERTAIN | the box and its condition are confirmed (`Order/CustomsDeclarationMetabox.php` `addMetaBoxes()`), but `auth: wordpress-capability` has no evidence in the class: it contains no `current_user_can` call |
| `exposes: submit_to_api` | UNCERTAIN | the bulk action is confirmed (`Order/BulkActions.php:13,72`), but `auth: wordpress-capability` has no evidence: `Order/BulkActions.php` contains no capability check |
| `exposes: packetery_sync_order_status` | CONFIRMED | `Order/PacketSynchronizer.php:28,114`; it is an Action Scheduler hook, scheduled at `:132` through `asScheduleSingleAction` |
| `calls: packeta-api` | CONFIRMED | `Order/PacketSubmitter.php:311` and the other SOAP call sites |
| `calls: packeta-widget-api` | CONFIRMED | `Order/PickupPointValidator.php:55` |
| `owns_data: order` | CONFIRMED | `Order/Repository.php:216` `CREATE TABLE ... $this->wpdbAdapter->packeteryOrder` |
| unknown "Where WordPress registers the grid column and bulk action callbacks" | CONFIRMED | no `add_filter`/`add_action` in `Order/GridExtender.php` and `Order/BulkActions.php` |
| unknown "Which internal REST routes save the modal data and the stored until date" | CONFIRMED | `Order/StoredUntilModal.php` calls `$this->orderRouter->getSaveStoredUntilUrl()`; the router lives in `src/Packetery/Module/Api/Internal/OrderRouter.php`, outside this module |
| unknown "Which order status the shop wants for each packet status" | CONFIRMED | `Order/WcOrderActions.php` reads the mapping through `getAutoOrderStatusFromMapping()` of the options provider |

CONFIRMED 13 · INCORRECT 2 · UNCERTAIN 3 · NOT FOUND 0 · PENDING 0 · SENSITIVE 0 · INSTRUCTION 0

Both tables: CONFIRMED 82 · INCORRECT 9 · UNCERTAIN 3 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### Fixes applied 2026-09-22

All INCORRECT rows of this record are fixed in the document and in the manifest fragment:
- the three counts of lines 27-28 now read 36 files, 5101 lines and 171 public methods, and `lines:` of the manifest reads 5101.
- the purpose section names the customs declaration metabox, the handover protocol page and the modal windows among the screens.
- the extension filter sentence names `packeta_order_detail_show_run_wizard_button` as the fourth filter, anchored to `src/Packetery/Module/Order/Metabox.php#prepareMetaboxParts`.
- the dependencies section adds `packetery-module-api`, `packetery-module-shipping`, `packetery-module-log`, `packetery-module-exception`, `packetery-module-dashboard`, `packetery-module-views` and `packetery-module-payment`, and one sentence of the prose names them.
- the row of `packet_status` and the four other state columns says which two columns the synchronisation writes and where the other three come from.

By the promotion rule the ceiling for this document is now `reviewed`: `verified` needs another pass by someone who did not write or fix it.
