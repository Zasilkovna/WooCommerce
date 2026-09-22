---
title: "woocommerce — packetery-module-product module"
repo: woocommerce
module: packetery-module-product
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [src/Packetery/Module/Product]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-product, type-reference]
---

Repo: woocommerce · Module: packetery-module-product · Type: reference · Status: current

## packetery-module-product: purpose

packetery-module-product holds the Packeta settings of one product. The shop owner marks a product
that needs age verification, and the shop owner disallows the carriers that must not deliver it
[VERIFY: src/Packetery/Module/Product/DataTab.php#registerTab]. The checkout then hides those
carriers and adds the verification fee
[VERIFY: src/Packetery/Module/Product/Entity.php#isAgeVerificationRequired].

packetery-module-product also gives the size of a product in centimetres, which the cart needs for
the size check of a carrier [VERIFY: src/Packetery/Module/Product/Entity.php#getLengthInCm]. The
module holds 4 files, 353 lines of logic and 26 public methods. The settings belong to the product
itself, so a shop sets them once and every order of that product follows them
[VERIFY: src/Packetery/Module/Product/DataTab.php#saveData].

> ⚠ add business context (elicitation)

## packetery-module-product: public interface

packetery-module-product exposes the product tab, the entity of a product and the grid extension.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Product tab | `packetery-tab` | Packeta tab of the product editor, hidden for a virtual product | [VERIFY: src/Packetery/Module/Product/DataTab.php#registerTab] |
| Tab registration | `register` | Registers the tab, its panel and its save | [VERIFY: src/Packetery/Module/Product/DataTab.php#register] |
| Save | `saveData`, `processFormData` | Write the values of the tab into the product metadata | [VERIFY: src/Packetery/Module/Product/DataTab.php#processFormData] |
| Age verification | `isAgeVerificationRequired` | Answers whether the product needs age verification | [VERIFY: src/Packetery/Module/Product/Entity.php#isAgeVerificationRequired] |
| Disallowed carriers | `getDisallowedShippingRateIds`, `getDisallowedShippingRateChoices` | Give the carriers that must not deliver the product | [VERIFY: src/Packetery/Module/Product/Entity.php#getDisallowedShippingRateIds] |
| Physical product | `isPhysical` | Answers whether the product is neither virtual nor downloadable | [VERIFY: src/Packetery/Module/Product/Entity.php#isPhysical] |
| Size | `getLengthInCm`, `getWidthInCm`, `getHeightInCm` | Give the size of the product in centimetres | [VERIFY: src/Packetery/Module/Product/Entity.php#getWidthInCm] |
| Entity factory | `fromPostId`, `fromGlobals` | Build the entity from an identifier or from the current post | [VERIFY: src/Packetery/Module/Product/ProductEntityFactory.php#fromPostId] |
| Grid columns | `addProductListColumns`, `fillCustomProductListColumns` | Add the two Packeta columns to the product list | [VERIFY: src/Packetery/Module/Product/ProductGridExtender.php#addProductListColumns] |
| Grid filters | `addProductFilters`, `processProductFilterClauses` | Add the two filters and join the metadata to the query | [VERIFY: src/Packetery/Module/Product/ProductGridExtender.php#processProductFilterClauses] |

The two columns are hidden by default, and a filter can switch them off
[VERIFY: src/Packetery/Module/Product/ProductGridExtender.php#defaultHiddenColumns]. The checkbox
list of the carriers comes from the active carriers of the carrier module
[VERIFY: src/Packetery/Module/Product/DataTab.php#render].

## packetery-module-product: data model

packetery-module-product keeps the `product-settings` of Packeta in the metadata of a product.

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| product | `packetery_age_verification_18_plus` | metadata, text flag | The tab stores `1` or `0`, and the reader compares against `1` | [VERIFY: src/Packetery/Module/Product/Entity.php#META_AGE_VERIFICATION_18_PLUS] |
| product | `packetery_disallowed_shipping_rates` | metadata, array | Carrier identifiers that must not deliver the product | [VERIFY: src/Packetery/Module/Product/Entity.php#META_DISALLOWED_SHIPPING_RATES] |

The save keeps only the selected carriers in the array, so an empty selection stores an empty array
[VERIFY: src/Packetery/Module/Product/DataTab.php#processFormData]. The reader checks only that the
value is an array, and it gives an empty list otherwise
[VERIFY: src/Packetery/Module/Product/Entity.php#getDisallowedShippingRateChoices]. The grid filter
joins the two metadata keys to the product query with its own aliases
[VERIFY: src/Packetery/Module/Product/ProductGridExtender.php#processProductFilterClauses].

## packetery-module-product: dependencies

packetery-module-product depends on the carrier list and on the platform helpers. Structured lines:

references → packetery-core
references → packetery-module-root
references → packetery-module-carrier
references → packetery-module-log
references → packetery-module-exception

The tab and the grid read the active carriers of `packetery-module-carrier`, and they leave out the
car delivery carriers that the shop disabled
[VERIFY: src/Packetery/Module/Product/ProductGridExtender.php#addProductFilters]. The size
conversion uses the helper of `packetery-core`
[VERIFY: src/Packetery/Module/Product/Entity.php#getHeightInCm]. A missing product raises the
exception of `packetery-module-exception`
[VERIFY: src/Packetery/Module/Product/ProductEntityFactory.php#fromGlobals]. Wrong hook arguments go
to the logger of `packetery-module-log`
[VERIFY: src/Packetery/Module/Product/ProductGridExtender.php#fillCustomProductListColumns]. The
checkout and the order builder read this module for every cart item.

## packetery-module-product: known limitations

packetery-module-product has these limitations evidenced in the code. The grid filter compares the
metadata against a serialised empty array written as a literal, so a change of that serialisation
breaks the filter [VERIFY: src/Packetery/Module/Product/ProductGridExtender.php#processProductFilterClauses].
The filter also builds its own join instead of a metadata query.

The age verification flag is stored as a text value and compared against a text value, so a numeric
value of the same meaning does not match
[VERIFY: src/Packetery/Module/Product/Entity.php#isAgeVerificationRequired]. The reader of the
disallowed carriers validates only the type of the value, not its content
[VERIFY: src/Packetery/Module/Product/Entity.php#getDisallowedShippingRateChoices]. The module
contains no TODO comment and no FIXME comment.
