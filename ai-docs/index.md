---
title: "woocommerce — documentation index"
repo: woocommerce
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [.]
confidence: draft
tags: [ai-generated, repo-woocommerce, type-index]
---

Repo: woocommerce · Module: — · Type: index · Status: current

## woocommerce: documentation map

woocommerce is the Packeta plugin for WooCommerce. It adds the Packeta carriers to the checkout and
sends the orders to Packeta as packets. The documentation of woocommerce is split into these pages:

| Page | Content | Open it when |
|---|---|---|
| [manifest.yaml](manifest.yaml) | Machine-readable inventory: the modules, what the plugin exposes, what it calls and what data it owns | You grep for a route, an entity or an interface name — start here |
| [overview.md](overview.md) | What the plugin does, the start of the plugin and the seven modules without a document | You need orientation |
| [architecture.md](architecture.md) | How the modules hold together and where an order flows | You trace a request or an order |
| [dependencies.md](dependencies.md) | The Packeta services, the platform requirements and the links between modules | You look for what the plugin needs from outside |
| [reference/packetery-core.md](reference/packetery-core.md) | The domain module: entities, the API clients and the validators | You work with the Packeta API contract |
| [reference/packetery-module-order.md](reference/packetery-module-order.md) | Packet lifecycle, admin screens and the order table | You work with packets or the order grid |
| [reference/packetery-module-checkout.md](reference/packetery-module-checkout.md) | Shipping rates, validation and the widget | You work with the cart or the checkout |
| [reference/packetery-module-carrier.md](reference/packetery-module-carrier.md) | The carrier feed, the carrier table and the carrier settings | You work with carriers or their prices |
| [reference/packetery-module-root.md](reference/packetery-module-root.md) | Start, scheduled jobs, upgrade and uninstall | You work with the schema or the background jobs |
| [reference/packetery-module-hooks.md](reference/packetery-module-hooks.md) | The registration map of every callback of the plugin | You look for the place where a callback becomes active |

## woocommerce: modules

woocommerce contains 25 modules. Eighteen of them have a document in `reference/`, and the other
seven are described in [overview.md](overview.md).

| Module | Path | Document |
|---|---|---|
| packetery-core | `src/Packetery/Core` | [reference/packetery-core.md](reference/packetery-core.md) |
| packetery-module-root | `src/Packetery/Module` | [reference/packetery-module-root.md](reference/packetery-module-root.md) |
| packetery-module-order | `src/Packetery/Module/Order` | [reference/packetery-module-order.md](reference/packetery-module-order.md) |
| packetery-module-checkout | `src/Packetery/Module/Checkout` | [reference/packetery-module-checkout.md](reference/packetery-module-checkout.md) |
| packetery-module-carrier | `src/Packetery/Module/Carrier` | [reference/packetery-module-carrier.md](reference/packetery-module-carrier.md) |
| packetery-module-options | `src/Packetery/Module/Options` | [reference/packetery-module-options.md](reference/packetery-module-options.md) |
| packetery-module-shipping, packetery-module-hooks, packetery-module-api | `src/Packetery/Module/{Shipping,Hooks,Api}` | [reference/packetery-module-shipping.md](reference/packetery-module-shipping.md), [reference/packetery-module-hooks.md](reference/packetery-module-hooks.md), [reference/packetery-module-api.md](reference/packetery-module-api.md) |
| packetery-module-views, packetery-module-forms, packetery-module-log | `src/Packetery/Module/{Views,Forms,Log}` | [reference/packetery-module-views.md](reference/packetery-module-views.md), [reference/packetery-module-forms.md](reference/packetery-module-forms.md), [reference/packetery-module-log.md](reference/packetery-module-log.md) |
| packetery-module-product, packetery-module-productcategory, packetery-module-customsdeclaration | `src/Packetery/Module/{Product,ProductCategory,CustomsDeclaration}` | [reference/packetery-module-product.md](reference/packetery-module-product.md), [reference/packetery-module-productcategory.md](reference/packetery-module-productcategory.md), [reference/packetery-module-customsdeclaration.md](reference/packetery-module-customsdeclaration.md) |
| packetery-module-email, packetery-module-dashboard, packetery-module-labels | `src/Packetery/Module/{Email,Dashboard,Labels}` | [reference/packetery-module-email.md](reference/packetery-module-email.md), [reference/packetery-module-dashboard.md](reference/packetery-module-dashboard.md), [reference/packetery-module-labels.md](reference/packetery-module-labels.md) |

## woocommerce: generation assumptions

woocommerce was documented under these assumptions (uncertainties the skill resolved on its own so
that it could finish non-interactively):

- The module boundary is the PSR-4 namespace. Each subdirectory of `src/Packetery/Module` is one
  module, and the classes that stand directly in that directory are the module
  `packetery-module-root`.
- The committed map in `ai-docs/ast` was produced at an older commit and without the namespace
  split, so the counts of each module come from a second run of the same tool. Test directories are
  excluded from every count.
- The module `packetery-module-order` holds more lines than the split rule permits. The team decided
  to document it as one module and to postpone the split.
- The names of the Packeta services follow the manifest of the PrestaShop module of Packeta, so a
  cross-repository query finds both plugins under one name.

## woocommerce: generation status

woocommerce — documentation generated from commit `b34fe03c`.

| Document | confidence | Verification |
|---|---|---|
| reference/packetery-core.md | reviewed | 53/53 CONFIRMED at the second verification |
| reference/packetery-module-order.md | reviewed | 82/94 CONFIRMED, 9 INCORRECT fixed |
| reference/packetery-module-checkout.md | reviewed | 81/89 CONFIRMED, 6 INCORRECT fixed |
| reference/packetery-module-carrier.md | reviewed | 77/88 CONFIRMED, 9 INCORRECT fixed |
| reference/packetery-module-options.md | reviewed | 71/77 CONFIRMED, 5 INCORRECT fixed |
| reference/packetery-module-shipping.md | reviewed | 58/62 CONFIRMED, 3 INCORRECT fixed |
| reference/packetery-module-api.md | reviewed | 65/68 CONFIRMED, 2 INCORRECT fixed |
| The nine remaining reference pages | draft | not yet verified |
| overview.md, architecture.md, dependencies.md, index.md | draft | not yet verified |

The lowest confidence is `draft`. A verification run of those documents, and a fix of every finding,
raises them to `reviewed`. A document reaches `verified` only when every checked row is confirmed at
the first verification.

## woocommerce: what the documentation does not know

woocommerce has these evidenced blind spots (carried machine-readably by `manifest.yaml`, section
`unknowns`). They are not tasks for the reader — they are findings to report when a query runs into
them.

| What is unknown | What was searched | What would resolve it |
|---|---|---|
| Whether the open permission callback of the four checkout REST routes is a decision or a defect | The permission callbacks of both controllers, which differ | A person owning the plugin security |
| Whether a capability check protects the settings export | The export class and the page that builds its link | The namespace that registers the hook |
| Which Packeta API version and WSDL the plugin runs against | The constructor arguments of the SOAP client | Packeta API documentation |
| Whether the carrier feed host and the pickup point validation host are one service | The endpoint constant and the REST client, which name two hosts | Packeta API documentation |
| Which consumers outside this repository use the domain module | This repository only | Other documented repositories |
| Which business rules decide the order status of each packet status, the disallowed gateways and the supported currency switchers | The settings that hold those values | A person owning the order process |
| Which data a shop must keep after it removes the plugin, and whether the job schedules fit a large shop | The uninstall constant and the five schedules | A person owning the plugin |
| How an installation with a read only plugin directory gets the class of a new carrier | The generator and the bulk generator | A person owning the plugin release |
| Whether a category restriction must win over a product restriction, and which products need age verification | The two metadata keys, which the checkout reads separately | A person owning the catalogue |
| Whether the shortcode of the pickup point country must give the country of the point or of the customer | The method behind that shortcode, which reads the shipping country | A person owning the email templates |

The manifest carries every question of every module. The table above groups the questions that share
one answer, and it keeps the question of every group.
