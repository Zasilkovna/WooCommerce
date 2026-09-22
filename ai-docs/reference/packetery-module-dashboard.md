---
title: "woocommerce — packetery-module-dashboard module"
repo: woocommerce
module: packetery-module-dashboard
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [src/Packetery/Module/Dashboard]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-dashboard, type-reference]
---

Repo: woocommerce · Module: packetery-module-dashboard · Type: reference · Status: current

## packetery-module-dashboard: purpose

packetery-module-dashboard gives the home page of the Packeta plugin, and every other Packeta page
is a child of it [VERIFY: src/Packetery/Module/Dashboard/DashboardPage.php#register]. The page shows
the setup steps of the plugin and marks each step that the shop already finished
[VERIFY: src/Packetery/Module/Dashboard/DashboardItemBuilder.php#buildItems]. The module holds 4
files, 291 lines of logic and 13 public methods.

packetery-module-dashboard answers one question for the shop owner: what is still missing before the
plugin works. It reads that state from the settings, from the carriers, from the products and from
the shipping zones of WooCommerce
[VERIFY: src/Packetery/Module/Dashboard/DashboardHelper.php#isPacketaShippingMethodActive].

> ⚠ add business context (elicitation)

## packetery-module-dashboard: public interface

packetery-module-dashboard exposes the page and the builder of its steps.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Dashboard page | `packeta-home` | Admin home page of the plugin and the parent of every Packeta page | [VERIFY: src/Packetery/Module/Dashboard/DashboardPage.php#SLUG] |
| Page registration | `register` | Adds the page and needs the `manage_woocommerce` capability | [VERIFY: src/Packetery/Module/Dashboard/DashboardPage.php#register] |
| Page render | `render` | Runs the carrier update and renders the home template | [VERIFY: src/Packetery/Module/Dashboard/DashboardPage.php#render] |
| Setup steps | `buildItems` | Builds the eight steps and decides which ones are finished | [VERIFY: src/Packetery/Module/Dashboard/DashboardItemBuilder.php#buildItems] |
| One step | `DashboardItem` | Caption, link, description, order and the finished state of one step | [VERIFY: src/Packetery/Module/Dashboard/DashboardItem.php#getSortOrder] |
| Shipping zone check | `isPacketaShippingMethodActive` | Answers whether a zone holds an active Packeta method | [VERIFY: src/Packetery/Module/Dashboard/DashboardHelper.php#isPacketaShippingMethodActive] |

The eight steps are the account settings, the carrier configuration mode, the product settings, the
carrier update, the carrier settings, the shipping zone, the packet status synchronisation and the
automatic submission [VERIFY: src/Packetery/Module/Dashboard/DashboardItemBuilder.php#buildItems].
The link of the carrier update stays empty while the account has no API password
[VERIFY: src/Packetery/Module/Dashboard/DashboardItemBuilder.php#getCarrierUpdateUrl].

## packetery-module-dashboard: dependencies

packetery-module-dashboard depends on every module whose state a step reports. Structured lines:

references → packetery-module-root
references → packetery-module-options
references → packetery-module-carrier
references → packetery-module-order
references → packetery-module-product
references → packetery-module-views

The steps read the settings of `packetery-module-options`, the carrier list and the last update of
`packetery-module-carrier`, and the status mapping of `packetery-module-order`
[VERIFY: src/Packetery/Module/Dashboard/DashboardItemBuilder.php#buildItems]. One step asks whether
any product holds a Packeta setting, and it reads the product metadata of
`packetery-module-product` [VERIFY: src/Packetery/Module/Dashboard/DashboardItemBuilder.php#hasProductsWithPacketaSettings].
The links of the steps come from the URL builder of `packetery-module-views`
[VERIFY: src/Packetery/Module/Dashboard/DashboardItem.php#getUrl]. The shipping zone step reads the
zones of WooCommerce through the adapters of `packetery-module-root`, and it looks for a method of
the Packeta shipping module in them
[VERIFY: src/Packetery/Module/Dashboard/DashboardHelper.php#isPacketaShippingMethodActive]. No other
module reads this one: the dashboard is the top of the page tree and nothing depends on its data.

## packetery-module-dashboard: known limitations

packetery-module-dashboard has these limitations evidenced in the code. The check of the product
settings asks the database for a product whose metadata is not an empty array, and it compares
against a serialised empty value written as a literal
[VERIFY: src/Packetery/Module/Dashboard/DashboardItemBuilder.php#hasProductsWithPacketaSettings].
A change of that serialisation breaks the check without an error.

The page runs the carrier update while it renders, so an open dashboard can start a download
[VERIFY: src/Packetery/Module/Dashboard/DashboardPage.php#render]. The capability of the page is a
literal of the registration [VERIFY: src/Packetery/Module/Dashboard/DashboardPage.php#register]. The
module contains no TODO comment and no FIXME comment.
