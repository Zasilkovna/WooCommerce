---
title: "woocommerce — packetery-module-productcategory module"
repo: woocommerce
module: packetery-module-productcategory
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [src/Packetery/Module/ProductCategory]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-productcategory, type-reference]
---

Repo: woocommerce · Module: packetery-module-productcategory · Type: reference · Status: current

## packetery-module-productcategory: purpose

packetery-module-productcategory disallows carriers for a whole product category. The shop owner
opens a category and selects the carriers that must not deliver the goods of that category
[VERIFY: src/Packetery/Module/ProductCategory/FormFields.php#render]. The checkout then removes
those carriers for a cart that holds such goods
[VERIFY: src/Packetery/Module/ProductCategory/Entity.php#getDisallowedShippingRateIds].

packetery-module-productcategory holds the same rule as the product module, one level higher. The
module holds 4 files, 213 lines of logic and 14 public methods, and it owns the `category-settings`
in one metadata key of a category [VERIFY: src/Packetery/Module/ProductCategory/Entity.php#META_DISALLOWED_SHIPPING_RATES].
A category rule covers every product of that category, so a shop with many products sets the rule
once instead of on each product
[VERIFY: src/Packetery/Module/ProductCategory/ProductCategoryEntityFactory.php#fromTermId].

> ⚠ add business context (elicitation)

## packetery-module-productcategory: public interface

packetery-module-productcategory exposes the form fields of a category, the entity and the grid
column.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Form registration | `register` | Adds the Packeta fields to the category form and saves them | [VERIFY: src/Packetery/Module/ProductCategory/FormFields.php#register] |
| Form render | `render` | Shows the carrier checkboxes on the edit form and on the add form | [VERIFY: src/Packetery/Module/ProductCategory/FormFields.php#render] |
| Form save | `saveData` | Stores the selected carriers, and only for the product category taxonomy | [VERIFY: src/Packetery/Module/ProductCategory/FormFields.php#saveData] |
| Disallowed carriers | `getDisallowedShippingRateIds`, `getDisallowedShippingRateChoices` | Give the carriers that the category disallows | [VERIFY: src/Packetery/Module/ProductCategory/Entity.php#getDisallowedShippingRateIds] |
| Category identifier | `getId` | Gives the term identifier of the category | [VERIFY: src/Packetery/Module/ProductCategory/Entity.php#getId] |
| Entity factory | `fromTermId` | Builds the entity of a category, or gives none when the term is missing | [VERIFY: src/Packetery/Module/ProductCategory/ProductCategoryEntityFactory.php#fromTermId] |
| Grid column | `addCategoryListColumns`, `fillCategoryListColumn` | Add the restriction column to the category list and fill it | [VERIFY: src/Packetery/Module/ProductCategory/CategoryGridExtender.php#fillCategoryListColumn] |
| Hidden column | `hideCategoryListColumnByDefault` | Hides the column until the user shows it | [VERIFY: src/Packetery/Module/ProductCategory/CategoryGridExtender.php#hideCategoryListColumnByDefault] |

The metadata key of the category differs from the key of the product, so the two settings never mix
[VERIFY: src/Packetery/Module/ProductCategory/Entity.php#META_DISALLOWED_SHIPPING_RATES]. The save
ignores the quick edit of the category list and accepts only the `product_cat` taxonomy
[VERIFY: src/Packetery/Module/ProductCategory/Entity.php#TAXONOMY_NAME].

## packetery-module-productcategory: dependencies

packetery-module-productcategory depends on the carrier list and on the platform helpers.
Structured lines:

references → packetery-module-root
references → packetery-module-carrier
references → packetery-module-log

The checkbox list holds the active carriers of `packetery-module-carrier`, and it leaves out the car
delivery carriers that the shop disabled
[VERIFY: src/Packetery/Module/ProductCategory/FormFields.php#saveData]. Wrong hook arguments go to
the logger of `packetery-module-log`
[VERIFY: src/Packetery/Module/ProductCategory/CategoryGridExtender.php#addCategoryListColumns]. The
checkout reads this module for the categories of every cart item, and the product module holds the
same rule for one product. The form itself comes from the form factory of `packetery-module-root`,
and the module adds only its own field
[VERIFY: src/Packetery/Module/ProductCategory/FormFields.php#render]. No module writes this metadata
except the category form.

## packetery-module-productcategory: known limitations

packetery-module-productcategory has these limitations evidenced in the code. The reader validates
only that the stored value is an array, so a value of another shape gives an empty list without an
error [VERIFY: src/Packetery/Module/ProductCategory/Entity.php#getDisallowedShippingRateChoices].

The category list holds no filter for the restriction, although the product list has one
[VERIFY: src/Packetery/Module/ProductCategory/CategoryGridExtender.php#hideCategoryListColumnByDefault].
The column name of this module equals the column name of the product list, and only the screen keeps
them apart [VERIFY: src/Packetery/Module/ProductCategory/CategoryGridExtender.php#addCategoryListColumns].
The column shows only whether a restriction exists, and the administrator opens the category to see
which carriers it names
[VERIFY: src/Packetery/Module/ProductCategory/CategoryGridExtender.php#fillCategoryListColumn]. The
module contains no TODO comment and no FIXME comment.
