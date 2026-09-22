---
title: "woocommerce — packetery-module-forms module"
repo: woocommerce
module: packetery-module-forms
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [src/Packetery/Module/Forms]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-forms, type-reference]
---

Repo: woocommerce · Module: packetery-module-forms · Type: reference · Status: current

## packetery-module-forms: purpose

packetery-module-forms builds the admin forms of the Packeta plugin and validates what the
administrator writes into them. The largest form is the carrier settings form, which holds the price
tables, the limits and the payment rules of one carrier
[VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm]. The module also builds the
per class form of a carrier, the currency rates form, the log filter, the diagnostic logging switch,
the stored until form and the bug report form
[VERIFY: src/Packetery/Module/Forms/ShippingClassFormFactory.php#createFromClassAndCarrier].

packetery-module-forms holds 9 files, 1066 lines of logic and 37 public methods. The module writes
the carrier settings to the WordPress option of that carrier, and every other value belongs to the
module that asked for the form [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#updateOptions].

> ⚠ add business context (elicitation)

## packetery-module-forms: public interface

packetery-module-forms exposes one factory for each form, and a helper with the shared parts.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Carrier form | `createForm` | Builds the settings form of one carrier and sets its stored values | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm] |
| Carrier form template | `createFormTemplate` | Builds the empty containers that the browser copies for a new limit | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createFormTemplate] |
| Carrier form save | `updateOptions`, `validateOptions` | Check the limits and write the settings of the carrier | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#validateOptions] |
| Shipping class form | `createFromClassAndCarrier` | Builds the price form of one carrier and one shipping class | [VERIFY: src/Packetery/Module/Forms/ShippingClassFormFactory.php#createFromClassAndCarrier] |
| Currency rates form | `createForm` | Builds one rate field for each currency that the shop does not use | [VERIFY: src/Packetery/Module/Forms/CurrencyRatesFormFactory.php#createForm] |
| Log filter form | `create` | Builds the filter of the log page as a form of the query string | [VERIFY: src/Packetery/Module/Forms/LogFilterFormFactory.php#create] |
| Diagnostic logging form | `onFormSuccess` | Stores the switch of the diagnostic logging | [VERIFY: src/Packetery/Module/Forms/DiagnosticsLoggingFormFactory.php#onFormSuccess] |
| Stored until form | `createForm` | Builds the small form of the storage date of a packet | [VERIFY: src/Packetery/Module/Forms/StoredUntilFormFactory.php#createForm] |
| Bug report form | `onFormSuccess` | Sends the message of the administrator and can send a copy | [VERIFY: src/Packetery/Module/Forms/BugReportForm.php#onFormSuccess] |
| Limit helper | `addWeightLimit`, `checkOverlapping`, `sortLimits` | Build a repeatable limit, reject two limits that overlap and sort them | [VERIFY: src/Packetery/Module/Forms/ShippingFormHelper.php#checkOverlapping] |

## packetery-module-forms: carrier settings form

packetery-module-forms builds the carrier form from the abilities of the carrier, so two carriers
get two different forms [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm]. The
form always holds the active switch, the display name, the pricing type and one price table. The
pricing type decides whether the table holds weight limits or order value limits.

| Field group | Condition | Content | Anchor |
|---|---|---|---|
| `active`, `name` | Always | The switch and the name that the checkout shows | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm] |
| `weight_limits`, `product_value_limits` | The selected pricing type | Repeatable rows of a limit and a price | [VERIFY: src/Packetery/Module/Forms/ShippingFormHelper.php#addWeightLimit] |
| `free_shipping_limit`, `coupon_free_shipping` | Always | The order value that makes the delivery free, and the coupon rules | [VERIFY: src/Packetery/Module/Forms/ShippingFormHelper.php#addProductValueLimit] |
| `default_COD_surcharge`, `surcharge_limits`, `cod_rounding` | The carrier supports cash on delivery | The surcharge table and the rounding rule | [VERIFY: src/Packetery/Module/Forms/ShippingFormHelper.php#addSurchargeLimit] |
| `dimensions_restrictions` | Always | Either the three dimensions, or the longest side and the sum of the sides | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm] |
| `vendor_groups` | The carrier is a compound carrier | One checkbox for each vendor group of the country | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#getAvailableVendors] |
| `address_validation` | The carrier delivers to an address in a supported country | The address check is off, optional or required | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm] |
| `age_verification_fee` | The carrier supports age verification | The fee that the cart adds | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm] |
| `days_until_shipping`, `shipping_time_cut_off` | The carrier is a car delivery carrier | The shipping day and the time of the day | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm] |
| `max_cart_value`, `class_calculation_type` | The shop uses the carrier configuration of WooCommerce | The cart limit and the rule for several shipping classes | [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm] |

The validation rejects two limits of one table that overlap, and it rejects a compound carrier with
fewer vendor groups than the minimum
[VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#isAvailableVendorsCountLowerThanRequiredMinimum].
The save merges the new limits with the stored ones, sorts them and keeps the per class section of
the settings [VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#updateOptions].

## packetery-module-forms: dependencies

packetery-module-forms depends on the modules that own the values of each form. Structured lines:

references → packetery-core
references → packetery-module-root
references → packetery-module-carrier
references → packetery-module-options
references → packetery-module-order
references → packetery-module-email

The carrier form reads and writes the settings of `packetery-module-carrier`, and it uses the option
name convention of that module
[VERIFY: src/Packetery/Module/Forms/ShippingFormHelper.php#createUrl]. The currency rates form takes
the currency list from `packetery-module-order`, and it takes the stored rates from
`packetery-module-options` [VERIFY: src/Packetery/Module/Forms/CurrencyRatesFormFactory.php#createForm].
The bug report form hands the message to `packetery-module-email`
[VERIFY: src/Packetery/Module/Forms/BugReportForm.php#onFormValidate]. The forms themselves come from
the form factory of `packetery-module-root`, which holds the validation messages
[VERIFY: src/Packetery/Module/Forms/StoredUntilFormFactory.php#createForm].

## packetery-module-forms: known limitations

packetery-module-forms has these limitations evidenced in the code. The names of most carrier
settings are string literals of the factory, and only the fields of the price table have constants
[VERIFY: src/Packetery/Module/Forms/CarrierFormFactory.php#createForm]. A change of one key needs a
change in the factory and in the settings object of the carrier module.

The data object of the order form has no reader in this module, so its use lives elsewhere
[VERIFY: src/Packetery/Module/Forms/FormData/OrderFormData.php#OrderFormData]. The shipping classes
are read once into a static variable, so a class that appears during one request is not seen
[VERIFY: src/Packetery/Module/Forms/ShippingFormHelper.php#getShippingClasses]. The module contains
no TODO comment and no FIXME comment.
