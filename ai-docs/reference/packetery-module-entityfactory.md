---
title: "woocommerce — packetery-module-entityfactory module"
repo: woocommerce
module: packetery-module-entityfactory
generated-by: skill:generate-docs@0.3.5
source-commit: 43b25221
last-generated: 2026-09-22
covers: [src/Packetery/Module/EntityFactory]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-entityfactory, type-reference]
---

Repo: woocommerce · Module: packetery-module-entityfactory · Type: reference · Status: current

## packetery-module-entityfactory: purpose

packetery-module-entityfactory builds the entities of the domain module from the data of WordPress,
of WooCommerce and of the plugin database. A repository reads a row and hands it to a factory, and
the factory returns the entity that the rest of the plugin works with
[VERIFY: src/Packetery/Module/EntityFactory/Carrier.php#fromDbResult]. The module holds 4 files, 142
lines of logic and 8 public methods.

packetery-module-entityfactory keeps the mapping in one place, so a change of a column or of a
setting changes one factory and not every caller
[VERIFY: src/Packetery/Module/EntityFactory/CustomsDeclaration.php#fromStandardizedStructure]. The
module writes nothing and reads no database of its own.

> ⚠ add business context (elicitation)

## packetery-module-entityfactory: public interface

packetery-module-entityfactory exposes four factories.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Shop address | `fromWcStoreOptions` | Builds the address entity from the store settings of WooCommerce | [VERIFY: src/Packetery/Module/EntityFactory/Address.php#fromWcStoreOptions] |
| Feed carrier | `fromDbResult` | Builds the carrier entity from a row of the carrier table and types its columns | [VERIFY: src/Packetery/Module/EntityFactory/Carrier.php#fromDbResult] |
| Internal carrier | `fromNonFeedCarrierData` | Builds the carrier entity of a pickup point provider that no feed contains | [VERIFY: src/Packetery/Module/EntityFactory/Carrier.php#fromNonFeedCarrierData] |
| Customs declaration | `fromStandardizedStructure` | Builds the declaration entity from a row and the order number | [VERIFY: src/Packetery/Module/EntityFactory/CustomsDeclaration.php#fromStandardizedStructure] |
| Declaration item | `createItemFromStandardizedStructure` | Builds one item of the declaration | [VERIFY: src/Packetery/Module/EntityFactory/CustomsDeclaration.php#createItemFromStandardizedStructure] |
| Packet size | `createSizeInSetDimensionUnit` | Builds the size entity of an order in the unit that the shop selected | [VERIFY: src/Packetery/Module/EntityFactory/SizeFactory.php#createSizeInSetDimensionUnit] |
| Default size | `createDefaultSizeForNewOrder` | Builds the size entity from the default dimensions of the plugin | [VERIFY: src/Packetery/Module/EntityFactory/SizeFactory.php#createDefaultSizeForNewOrder] |

The carrier factory decides the age verification of a carrier from a fixed list of identifiers
[VERIFY: src/Packetery/Module/EntityFactory/Carrier.php#AGE_VERIFIED_CARRIERS], and the factory of an
internal carrier sets a fixed weight limit and several fixed flags
[VERIFY: src/Packetery/Module/EntityFactory/Carrier.php#fromNonFeedCarrierData].

## packetery-module-entityfactory: dependencies

packetery-module-entityfactory depends on the entities of the domain module and on the settings.
Structured lines:

references → packetery-core
references → packetery-module-options
references → packetery-module-framework

The factories return the address, the carrier, the customs declaration and the size entity of
`packetery-core` [VERIFY: src/Packetery/Module/EntityFactory/SizeFactory.php#createDefaultSizeForNewOrder].
The size factory reads the dimension unit and the default dimensions from
`packetery-module-options`, and the address factory reads the store settings through the adapters of
`packetery-module-framework` [VERIFY: src/Packetery/Module/EntityFactory/Address.php#fromWcStoreOptions].
The carrier repository, the customs declaration repository and the order module call these
factories [VERIFY: src/Packetery/Module/EntityFactory/Carrier.php#fromNonFeedCarrierData]. The
module returns an entity and never writes one, so a caller that changes an entity saves it through
its own repository.

## packetery-module-entityfactory: known limitations

packetery-module-entityfactory has these limitations evidenced in the code. The list of the carriers
that support age verification is a constant of the factory, so a new such carrier needs a code change
[VERIFY: src/Packetery/Module/EntityFactory/Carrier.php#AGE_VERIFIED_CARRIERS]. The weight limit and
several flags of an internal carrier are fixed values of the same file
[VERIFY: src/Packetery/Module/EntityFactory/Carrier.php#fromNonFeedCarrierData], so an internal
carrier accepts the same packet size in every country.

The factories trust the shape of their input. A row with a missing column or a changed name gives no
error of this module [VERIFY: src/Packetery/Module/EntityFactory/CustomsDeclaration.php#createItemFromStandardizedStructure].
The module contains no TODO comment and no FIXME comment.
