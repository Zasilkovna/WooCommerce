---
title: "woocommerce — packetery-module-order module"
repo: woocommerce
module: packetery-module-order
generated-by: skill:generate-docs@0.3.5
source-commit: 97720cfc
last-generated: 2026-09-22
covers: [src/Packetery/Module/Order]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-module-order, type-reference]
---

Repo: woocommerce · Module: packetery-module-order · Type: reference · Status: current

## packetery-module-order: purpose

packetery-module-order keeps the Packeta data of each WooCommerce order and connects that data to
the Packeta API. The module owns one database table, and it builds the `Packetery\Core\Entity\Order`
object from that table and from `WC_Order` [VERIFY: src/Packetery/Module/Order/Builder.php#build].
The module gives the administrator all screens that work with a packet. These screens are the order
metabox [VERIFY: src/Packetery/Module/Order/Metabox.php#add_meta_boxes] and the customs declaration
metabox [VERIFY: src/Packetery/Module/Order/CustomsDeclarationMetabox.php#addMetaBoxes], the columns
and the bulk actions of the order grid
[VERIFY: src/Packetery/Module/Order/GridExtender.php#addOrderListColumns], the label print pages
[VERIFY: src/Packetery/Module/Order/LabelPrint.php#outputLabelsPdf] with the handover protocol page
[VERIFY: src/Packetery/Module/Order/CollectionPrint.php#requestShipment], and the modal windows of
the order detail [VERIFY: src/Packetery/Module/Order/CarrierModal.php#canBeDisplayed].
The module sends the packet to Packeta and reads the packet status back
[VERIFY: src/Packetery/Module/Order/PacketSubmitter.php#submitPacket].

packetery-module-order is the largest namespace in the repository. It holds 36 files, 5101 lines of
logic and 171 public methods. All classes are in one directory, and the module has no deeper
namespace to split on.

> ⚠ add business context (elicitation)

## packetery-module-order: public interface

packetery-module-order exposes its members to WordPress and to WooCommerce as hook callbacks, admin
screens and packet actions. The module registers no REST route of its own.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Order metabox | `packetery_metabox` | Shows the Packeta form on the order detail and saves its fields | [VERIFY: src/Packetery/Module/Order/Metabox.php#add_meta_boxes] |
| Customs declaration metabox | `packetery_customs_declaration_metabox` | Shows the customs form when the carrier needs a declaration | [VERIFY: src/Packetery/Module/Order/CustomsDeclarationMetabox.php#addMetaBoxes] |
| Grid columns | `packetery_weight`, `packetery`, `packetery_packet_id`, `packetery_packet_status`, `packetery_packet_stored_until`, `packetery_destination` | Add Packeta data to the order list | [VERIFY: src/Packetery/Module/Order/GridExtender.php#addOrderListColumns] |
| Grid filter links | `packetery_to_submit`, `packetery_to_print` | Filter the order list to orders that wait for submission or for a label | [VERIFY: src/Packetery/Module/Order/GridExtender.php#addFilterLinks] |
| Bulk actions | `submit_to_api`, `ACTION_PACKETA_LABELS`, `ACTION_CARRIER_LABELS`, `ACTION_PRINT_ORDER_COLLECTION` | Submit packets, print labels and print the handover protocol for selected orders | [VERIFY: src/Packetery/Module/Order/BulkActions.php#addActions] |
| Packet actions | `ACTION_SUBMIT_PACKET`, `ACTION_CANCEL_PACKET`, `ACTION_SUBMIT_PACKET_CLAIM` | Run one packet operation for one order, after a nonce check | [VERIFY: src/Packetery/Module/Order/PacketActionsCommonLogic.php#createNonceAction] |
| Status sync hook | `packetery_sync_order_status` | Reads the packet status of one order from Packeta | [VERIFY: src/Packetery/Module/Order/PacketSynchronizer.php#register] |
| Auto submission hooks | `packetery_auto_submission_handle_event`, `woocommerce_order_status_completed`, `woocommerce_order_status_processing` | Submit the packet when the order reaches a configured state | [VERIFY: src/Packetery/Module/Order/PacketAutoSubmitter.php#register] |
| REST response filter | `woocommerce_rest_prepare_shop_order_object` | Adds carrier, pickup point and packet id to the WooCommerce REST order | [VERIFY: src/Packetery/Module/Order/ApiExtender.php#extendResponse] |
| Order repository | `Repository` | Reads, saves and deletes the Packeta row of an order, and extends the grid query | [VERIFY: src/Packetery/Module/Order/Repository.php#processClauses] |

The module gives four extension filters to other code: `packeta_order_grid_links_settings`
[VERIFY: src/Packetery/Module/Order/GridExtender.php#addFilterLinks], `packeta_create_packet`
[VERIFY: src/Packetery/Module/Order/PacketSubmitter.php#submitPacket],
`packetery_exclude_orders_with_status`
[VERIFY: src/Packetery/Module/Order/Repository.php#applyCustomFilters] and
`packeta_order_detail_show_run_wizard_button`
[VERIFY: src/Packetery/Module/Order/Metabox.php#prepareMetaboxParts]. The label print page and the
handover protocol page are WordPress submenu pages under the Packeta dashboard
[VERIFY: src/Packetery/Module/Order/CollectionPrint.php#register]. Two modal windows save their data
through the internal REST routes of `packetery-module-api`
[VERIFY: src/Packetery/Module/Order/StoredUntilModal.php#renderModal].

## packetery-module-order: dependencies

packetery-module-order depends on the core entities, on other Packeta modules and on the WordPress
runtime. Structured lines:

references → packetery-core
references → packetery-module-carrier
references → packetery-module-options
references → packetery-module-customsdeclaration
references → packetery-module-labels
references → packetery-module-framework
references → packetery-module-api
references → packetery-module-shipping
references → packetery-module-log
references → packetery-module-exception
references → packetery-module-dashboard
references → packetery-module-views
references → packetery-module-payment

The module reads and writes `Packetery\Core\Entity\Order` and uses the SOAP client of
`packetery-core` for every packet operation
[VERIFY: src/Packetery/Module/Order/PacketSubmitter.php#submitPacket]. It gets the carrier
definition and the fixed pickup point configuration from `packetery-module-carrier`
[VERIFY: src/Packetery/Module/Order/Builder.php#build]. It reads the plugin settings, the packet
status mapping and the custom currency rates from `packetery-module-options`
[VERIFY: src/Packetery/Module/Order/CurrencyConversion.php#getOrderCustomCurrencyRate].
WordPress and WooCommerce call the module through hooks, and the module calls them back through the
adapters of `packetery-module-framework` [VERIFY: src/Packetery/Module/Order/WcOrderActions.php#updateOrderStatus].
The module plans the long operations as Action Scheduler jobs
[VERIFY: src/Packetery/Module/Order/PacketSynchronizer.php#syncStatuses]. The module also uses the
internal REST router of `packetery-module-api`, the shipping method classes of
`packetery-module-shipping`, and the log, the exceptions, the dashboard pages, the views and the
payment helpers of the other Packeta modules
[VERIFY: src/Packetery/Module/Order/StoredUntilModal.php#renderModal].

## packetery-module-order: data model

packetery-module-order owns one table for the Packeta data of an order. The table name comes from
the `WpdbAdapter` property `packeteryOrder`, and the schema statement is in the module
[VERIFY: src/Packetery/Module/Order/Repository.php#createOrAlterTable].

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| order | `id` | bigint(20) unsigned, not null | Primary key, equal to the WooCommerce order id | [VERIFY: src/Packetery/Module/Order/Repository.php#createOrAlterTable] |
| order | `carrier_id` | varchar(15), not null | Carrier of the order | [VERIFY: src/Packetery/Module/Order/Builder.php#build] |
| order | `packet_id`, `packet_claim_id`, `packet_claim_password` | varchar, null | Identifiers that Packeta returns after submission | [VERIFY: src/Packetery/Module/Order/PacketClaimSubmitter.php#processAction] |
| order | `is_exported`, `is_label_printed`, `address_validated`, `adult_content` | tinyint(1) | Flags of the packet lifecycle | [VERIFY: src/Packetery/Module/Order/Repository.php#orderToDbArray] |
| order | `point_id`, `point_name`, `point_city`, `point_street`, `point_zip`, `point_url`, `point_place` | varchar, null | Pickup point that the customer selected | [VERIFY: src/Packetery/Module/Order/Repository.php#createOrAlterTable] |
| order | `delivery_address` | text, null | JSON with the keys `street`, `city`, `zip`, `houseNumber`, `longitude`, `latitude`, `county` | [VERIFY: src/Packetery/Module/Order/Builder.php#build] |
| order | `weight`, `length`, `width`, `height` | float, null | Size and weight that the administrator can change | [VERIFY: src/Packetery/Module/Order/Form.php#FIELD_WEIGHT] |
| order | `value`, `cod` | double, null | Order value and cash on delivery amount | [VERIFY: src/Packetery/Module/Order/CreatePacketMapper.php#fromOrderToArray] |
| order | `packet_status`, `stored_until`, `deliver_on`, `carrier_number`, `car_delivery_id` | varchar and date, null | The synchronisation writes `packet_status` and `stored_until`. The other three come from the metabox form, from the checkout and from a cancellation | [VERIFY: src/Packetery/Module/Order/PacketSynchronizer.php#getPacketStatuses] |
| order | `api_error_message`, `api_error_date` | text and datetime, null | Last error that the Packeta API returned | [VERIFY: src/Packetery/Module/Order/Repository.php#orderToDbArray] |

The mapping from the entity to the columns is one method
[VERIFY: src/Packetery/Module/Order/Repository.php#orderToDbArray]. The checkout and the metabox
address the same data through field keys such as `POINT_ID` and `CAR_DELIVERY_ID`
[VERIFY: src/Packetery/Module/Order/Attribute.php#POINT_ID]. The query of the order grid joins the
Packeta table to the WooCommerce order table, and the join respects the HPOS setting
[VERIFY: src/Packetery/Module/Order/Repository.php#getWcOrderJoinClause].

## packetery-module-order: external links

packetery-module-order communicates outside the repository with the Packeta API. Structured lines:

calls → packeta-soap-api (sync, SOAP)
calls → packeta-widget (sync, REST)

The module sends these SOAP operations through the client of `packetery-core`: `createPacket` and
`createStorageFile` for a new packet [VERIFY: src/Packetery/Module/Order/PacketSubmitter.php#submitPacket],
`cancelPacket` for a cancellation [VERIFY: src/Packetery/Module/Order/PacketCanceller.php#shouldRevertSubmission],
`packetStatus` for the status [VERIFY: src/Packetery/Module/Order/PacketSynchronizer.php#getPacketStatuses],
`createPacketClaimWithPassword` for a claim packet
[VERIFY: src/Packetery/Module/Order/PacketClaimSubmitter.php#processAction] and
`packetSetStoredUntil` for a longer storage time
[VERIFY: src/Packetery/Module/Order/PacketSetStoredUntil.php#setStoredUntil]. The print pages call
`packetsLabelsPdf` and `packetsCarrierLabelsPdf` for the label documents
[VERIFY: src/Packetery/Module/Order/LabelPrint.php#requestPacketaLabels], and `createShipment` with
`barcodePng` for the handover protocol
[VERIFY: src/Packetery/Module/Order/CollectionPrint.php#requestShipment].

The module validates a selected pickup point over the REST interface
[VERIFY: src/Packetery/Module/Order/PickupPointValidator.php#validate]. The API key comes from
`packetery-module-options`, and no address or credential is in this module.

## packetery-module-order: known limitations

packetery-module-order has these limitations evidenced in the code. The pickup point validation
fails open. When the API key is empty or the REST call throws, the module accepts the pickup point
as valid [VERIFY: src/Packetery/Module/Order/PickupPointValidator.php#validate]. The list of packet
statuses is hardcoded in the module, so a new Packeta status needs a code change
[VERIFY: src/Packetery/Module/Order/PacketSynchronizer.php#getPacketStatuses]. The mapping from the
delivery country to the currency is also hardcoded
[VERIFY: src/Packetery/Module/Order/CurrencyConversion.php#getCurrencyByCountry].

The handover protocol stores the selected order identifiers in a transient without an expiration
time, but the label print stores its own identifiers with a time to live
[VERIFY: src/Packetery/Module/Order/BulkActions.php#handleActions]. The upload limit of a customs
declaration file is a constant of the metabox class
[VERIFY: src/Packetery/Module/Order/CustomsDeclarationMetabox.php#MAX_UPLOAD_FILE_MEGABYTES]. The
condition that shows the carrier modal does not agree with the comment above it
[VERIFY: src/Packetery/Module/Order/CarrierModal.php#canBeDisplayed]. The module contains no TODO
comment and no FIXME comment.

The classes that extend the order grid and the bulk actions give only callbacks. The registration of
these callbacks is outside this module
[VERIFY: src/Packetery/Module/Order/GridExtender.php#addOrderListColumns].
