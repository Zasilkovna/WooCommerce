## ai-docs/reference/packetery-core.md @ 6791c048 — 2026-09-22

Second pass, after the fixes of the first one. Verified against the working tree of branch `docs/teams` at HEAD `4034cda7`. `6791c048` is an ancestor of that HEAD and `git log 6791c048..HEAD -- src/Packetery/Core` is empty, so the code is identical to the documented commit. All paths below are relative to the repository root; `Core/` stands for `src/Packetery/Core/`.

While this pass ran, the fragment `ai-docs/manifest.part.packetery-core.yaml` was assembled into `ai-docs/manifest.yaml` and removed. The second table therefore verifies the entries of `ai-docs/manifest.yaml` that carry `module: packetery-core`, plus the two assumptions that belong to this module. Their text is unchanged from the fragment.

| line | claim | class | evidence |
|---|---|---|---|
| 17 | packetery-core is the namespace `Packetery\Core` | CONFIRMED | every file under `Core/` declares that namespace; the document `covers: [src/Packetery/Core]` |
| 18-19 | The module keeps the entities of a shipment, the submission rules and the transport code | CONFIRMED | `Core/Entity/`, `Core/Validator/`, `Core/Api/` |
| 19-21 | packetery-core knows nothing about WordPress or WooCommerce; it declares the services it needs as interfaces | CONFIRMED | no `add_action`, `add_filter`, `wp_*`, `WC_*`, `get_option` or `$wpdb` symbol under `Core/`; `Core/Interfaces/IWebRequestClient.php` declares `post()` |
| 22 | The module carries no persistence layer, no hooks and no user interface | CONFIRMED | no `PDO`, `mysqli`, `.latte` or `echo` under `Core/` |
| 24-25 | A change of the API contract stays inside the module, a change of the WordPress integration stays outside | CONFIRMED | follows from the row above: the namespace holds the API classes and no integration symbol |
| 25-27 | The SOAP client holds the request class and the response class of every API operation, and the modules call those operations by name | CONFIRMED | `Core/Api/Soap/Client.php` has 12 API methods; `Core/Api/Soap/Request/` holds 12 classes and `Core/Api/Soap/Response/` holds the same 12 plus `BaseResponse` |
| 29 | `> ⚠ add business context (elicitation)` | PENDING | standard placeholder |
| 33 | The module registers no route and no command | CONFIRMED | no `register_rest_route` and no `WP_CLI` symbol under `Core/` |
| 38 | `createPacket( array $requestData )` | CONFIRMED | `Core/Api/Soap/Client.php:92` `public function createPacket( array $requestData ): Response\CreatePacket` |
| 39 | `packetStatus( Request\PacketStatus $request )` | CONFIRMED | `Core/Api/Soap/Client.php:157` `public function packetStatus( Request\PacketStatus $request ): Response\PacketStatus` |
| 40 | `packetsLabelsPdf( $request )` returns label documents as PDF data | CONFIRMED | `Core/Api/Soap/Client.php:239` `packetsLabelsPdf( Request\PacketsLabelsPdf $request ): Response\PacketsLabelsPdf` |
| 41 | `PickupPointValidate::validate( $request )` | CONFIRMED | `Core/Api/Rest/PickupPointValidate.php:37` `validate( PickupPointValidateRequest $request ): PickupPointValidateResponse` |
| 42 | `Validator\Order::isValid( $order )` decides whether an order has the data a submission needs | CONFIRMED | `Core/Validator/Order.php:86` `isValid( Entity\Order $order ): bool` returns `count( $this->validate( $order ) ) === 0` |
| 43 | `Rounder::round( float $amount, int $roundingType, int $precision )` rounds up, down or not at all | CONFIRMED | `Core/Rounder.php:38` the signature, `:23-26` `ROUND_UP`, `ROUND_DOWN`, `DONT_ROUND`, `ROUNDING_TYPES` |
| 44 | `CoreHelper::getTrackingUrl( $packetId )` | CONFIRMED | `Core/CoreHelper.php:77` `getTrackingUrl( ?string $packetId ): ?string` |
| 46-48 | A fault of the API becomes a fault identifier, a rejected request carries the validation errors | CONFIRMED | `Core/Api/Soap/Client.php:77-78` `setFault( $this->getFaultIdentifier( $exception ) )` and `setFaultString(...)`; `getValidationErrors()` collects `PacketAttributesFault` entries |
| 48-50 | The validator does not throw; it collects translation keys | CONFIRMED | `Core/Validator/Order.php` has no `throw`; `validate()` builds a map of `ERROR_TRANSLATION_KEY_*` constants, `:27` `ERROR_TRANSLATION_KEY_WEIGHT = 'validation_error_weight'` |
| 57 | calls → packeta-api (sync, SOAP/XML) | CONFIRMED | `Core/Api/Soap/Client.php` builds a `SoapClient` per operation |
| 58 | calls → packeta-widget-api (sync, REST/JSON) | CONFIRMED | `Core/Api/Rest/PickupPointValidate.php` posts JSON to the widget endpoint |
| 59 | called from → woocommerce | CONFIRMED | 65 files of `src/Packetery/Module` import `Packetery\Core` |
| 61-62 | The module sends shipment data, label requests and status queries to the SOAP API | CONFIRMED | `Core/Api/Soap/Client.php` `createShipment()`, `packetsLabelsPdf()`, `packetStatus()` |
| 62-64 | The WSDL address and the API password come from the constructor, so the values live outside the module | CONFIRMED | `Core/Api/Soap/Client.php` `__construct( ?string $apiPassword, string $wsdlUrl )`, plus `setApiPassword()` |
| 66 | packetery-core declares four interfaces | CONFIRMED | `Core/Api/Soap/ILabelResponse.php`, `Core/Log/ILogger.php`, `Core/Interfaces/ValidatorTranslationsInterface.php`, `Core/Interfaces/IWebRequestClient.php` |
| 66-69 | `IWebRequestClient` performs the REST call and is implemented outside | CONFIRMED | interface at `Core/Interfaces/IWebRequestClient.php`, implementation at `src/Packetery/Module/WebRequestClient.php` |
| 68-69 | `ILogger` records what happened and is implemented outside | CONFIRMED | implementation at `src/Packetery/Module/Log/DbLogger.php` |
| 69-71 | `ValidatorTranslationsInterface` gives the texts and is implemented outside | CONFIRMED | implementation at `src/Packetery/Module/Order/ValidatorTranslations.php` |
| 71-73 | `ILabelResponse` has its implementation inside the module | CONFIRMED | `Core/Api/Soap/Response/PacketsLabelsPdf.php` and `Core/Api/Soap/Response/PacketsCourierLabelsPdf.php` |
| 83 | `Order.finalWeight` float, nullable | CONFIRMED | `Core/Entity/Order.php:928` `getFinalWeight(): ?float` |
| 84 | `Order.pickupPoint` nullable | CONFIRMED | `Core/Entity/Order.php:751` `getPickupPoint(): ?PickupPoint` |
| 85 | `Order.customsDeclaration` nullable | CONFIRMED | `Core/Entity/Order.php:321` `getCustomsDeclaration(): ?CustomsDeclaration` |
| 86 | `PacketStatus::DELIVERED` string constant | CONFIRMED | `Core/Entity/PacketStatus.php:19` `public const DELIVERED = 'delivered'` |
| 87 | `Carrier.maxWeight` float | CONFIRMED | `Core/Entity/Carrier.php:295` `getMaxWeight(): float` |
| 88 | `Size` holds length, width and height | CONFIRMED | `Core/Entity/Size.php:46` `__construct( ?float $length = null, ?float $width = null, ?float $height = null )` |
| 90-92 | One compound provider holds several vendor codes of one country | CONFIRMED | `Core/PickupPointProvider/CompoundProvider.php`: one `string $country` argument and `array $vendorCodes`, returned by `getVendorCodes()` |
| 96 | The module communicates with two services of Packeta | CONFIRMED | the SOAP client and the REST client are the only outbound classes under `Core/` |
| 98 | calls → packeta-api (sync, SOAP/XML) | CONFIRMED | same evidence as line 57 |
| 99 | calls → packeta-widget-api (sync, REST/JSON) | CONFIRMED | same evidence as line 58 |
| 101-102 | The SOAP client creates packets, cancels them, reads their status and downloads label and barcode documents | CONFIRMED | `Core/Api/Soap/Client.php`: `createPacket()`, `cancelPacket():137`, `packetStatus()`, `packetsLabelsPdf()`, `barcodePng():218` |
| 102-104 | The address comes from the constructor argument `wsdlUrl`, so the module holds no endpoint value | CONFIRMED | `Core/Api/Soap/Client.php` `__construct( ?string $apiPassword, string $wsdlUrl )`, every call uses `new SoapClient( $this->wsdlUrl )` |
| 106-108 | The REST endpoint is a constant of the class and the API key arrives through a factory method | CONFIRMED | `Core/Api/Rest/PickupPointValidate.php:13` `private const URL_VALIDATE_ENDPOINT`, `:26` `createWithValidApiKey( IWebRequestClient $webRequestClient, ?string $apiKey )` |
| 108-110 | The request and the response have their own classes | CONFIRMED | `Core/Api/Rest/PickupPointValidateRequest.php` and `PickupPointValidateResponse.php` |
| 116-117 | The compound pickup point provider ignores vendor identifiers, which the code records as an open point | CONFIRMED | `Core/PickupPointProvider/CompoundProvider.php:20` `TODO: consider vendorIds.` |
| 117-120 | The factory of a compound carrier collection does not take age verification into account | CONFIRMED | `Core/PickupPointProvider/CompoundCarrierCollectionFactory.php:20-21` `TODO: take into account that not all types of pickup points support age verification.` |
| 122-123 | The SOAP client has no retry and no timeout of its own | CONFIRMED | every call does `new SoapClient( $this->wsdlUrl )` with no options array |
| 123-125 | The order validator checks presence and shape, not the business rules of the carrier | CONFIRMED | `Core/Validator/Order.php` `validate()` maps `hasNumber()`, `hasName()`, `hasFinalValue()`, `hasPickupPointOrCarrierId()`, `hasEshop()`, the weight, the address and the size report |

CONFIRMED 44 · INCORRECT 0 · UNCERTAIN 0 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

### manifest.yaml, entries of packetery-core

| entry | class | evidence |
|---|---|---|
| `aliases` holds `packetery-core` | CONFIRMED | `ai-docs/manifest.yaml:5` |
| assumption "Generated at commit 6791c048, on the branch that is now named docs/teams." | CONFIRMED | `git merge-base --is-ancestor 6791c048 HEAD` passes on `docs/teams` |
| assumption "Module boundary taken from the PSR-4 namespace Packetery\Core; ... a --split-psr4 run of the same tool at the same commit." | UNCERTAIN | the committed `ai-docs/ast/map.json` has no PSR-4 projects, so the first half holds, but its `source-commit` is `11523b71...`, not `6791c048`, and the claimed second run is not in the repository. The other modules state that commit in their own assumption; this one does not |
| assumption "The STE plugin esterka@packeta-dev was not loaded in the generating session." | UNCERTAIN | no record of a plugin load in this repository |
| `modules:` entry packetery-core | CONFIRMED | `path` and `doc` resolve; `lines: 2473` matches the non-blank non-comment count of `src/Packetery/Core` (raw total 6605 lines in 59 files) |
| `exposes:` `Packetery\Core`, kind library, auth none | CONFIRMED | `Core/Api/Soap/Client.php` `createPacket()`; the namespace registers no route and no command, so `auth: none` holds |
| `calls:` packeta-api | CONFIRMED | `Core/Api/Soap/Client.php` `createPacket()` |
| `calls:` packeta-widget-api | CONFIRMED | `Core/Api/Rest/PickupPointValidate.php` `validate()` |
| unknown "Which Packeta API version and WSDL the plugin runs against." | CONFIRMED | the WSDL address is a constructor argument, no version constant under `Core/` |
| unknown "Whether the compound pickup point provider must respect vendor identifiers and age verification." | CONFIRMED | the two TODO comments named in the limitations rows |
| unknown "Which consumers outside this repository use Packetery\Core." | CONFIRMED | not answerable inside this repository |

CONFIRMED 9 · INCORRECT 0 · UNCERTAIN 2 · NOT FOUND 0 · PENDING 0 · SENSITIVE 0 · INSTRUCTION 0

Both tables: CONFIRMED 53 · INCORRECT 0 · UNCERTAIN 2 · NOT FOUND 0 · PENDING 1 · SENSITIVE 0 · INSTRUCTION 0

All seven INCORRECT rows of the first pass are gone. The document now stands at `reviewed`: the two UNCERTAIN rows of the manifest keep it below `verified`, and both are about a tool run and a plugin load that the repository does not record.
