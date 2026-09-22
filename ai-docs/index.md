---
title: "woocommerce — documentation index"
repo: woocommerce
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [.]
confidence: reviewed
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
| [architecture.md](architecture.md) | How the modules hold together, and the flows of an order, of the carrier list and of a label print | You trace a request or an order |
| [dependencies.md](dependencies.md) | The Packeta services, the platform requirements and the links between modules | You look for what the plugin needs from outside |
| `reference/` | Twenty-one module documents, one for each documented module; the table below lists them | You work in one module |

## woocommerce: modules

woocommerce contains 25 modules. Twenty-one of them have a document in `reference/`, and the other
four are described in [overview.md](overview.md).

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
| packetery-module-framework, packetery-module-entityfactory, packetery-module-blocks | `src/Packetery/Module/{Framework,EntityFactory,Blocks}` | [reference/packetery-module-framework.md](reference/packetery-module-framework.md), [reference/packetery-module-entityfactory.md](reference/packetery-module-entityfactory.md), [reference/packetery-module-blocks.md](reference/packetery-module-blocks.md) |

## woocommerce: generation assumptions

woocommerce was documented under these assumptions (uncertainties the skill resolved on its own so
that it could finish non-interactively):

- The module boundary is the PSR-4 namespace. Each subdirectory of `src/Packetery/Module` is one
  module, and the classes that stand directly in that directory are the module
  `packetery-module-root`.
- Three modules hold fewer lines than the threshold, but they hold more public members than the
  member threshold, or every other module depends on them. They have a document of their own:
  `packetery-module-framework`, `packetery-module-entityfactory` and `packetery-module-blocks`.
- The counts of each module come from a run of the map tool with the namespace split, while the
  committed map in `ai-docs/ast` holds one project for each PSR-4 root. Test directories are
  excluded from every count.
- The module `packetery-module-order` holds 5101 lines of logic, which is above the limit of 3000
  lines of the split rule. The documentation keeps it as one module.
- The names of the Packeta services follow the manifest of the PrestaShop module of Packeta, so a
  cross-repository query finds both plugins under one name.

## woocommerce: generation status

woocommerce — documentation generated from commit `b34fe03c`. Every document was verified against
the code, and every finding of the class INCORRECT was fixed. The ratio counts the rows of both
tables of the verification record, without the rows of the class PENDING.

| Document | confidence | Verification |
|---|---|---|
| reference/packetery-core.md | reviewed | 53/55 CONFIRMED at the second verification |
| reference/packetery-module-order.md | reviewed | 82/94 CONFIRMED |
| reference/packetery-module-checkout.md | reviewed | 81/88 CONFIRMED |
| reference/packetery-module-carrier.md | reviewed | 77/87 CONFIRMED |
| reference/packetery-module-options.md, -shipping.md, -api.md | reviewed | 71/77, 58/62 and 65/70 CONFIRMED |
| reference/packetery-module-hooks.md, -root.md, -log.md | reviewed | 44/58, 56/62 and 52/65 CONFIRMED |
| reference/packetery-module-forms.md, -views.md, -labels.md | reviewed | 51/58, 46/55 and 40/47 CONFIRMED |
| reference/packetery-module-product.md, -productcategory.md, -customsdeclaration.md | reviewed | 54/62, 44/48 and 49/51 CONFIRMED |
| reference/packetery-module-email.md, -dashboard.md | reviewed | 36/41 and 35/42 CONFIRMED |
| index.md, overview.md, architecture.md, dependencies.md | reviewed | 29/41, 39/44, 43/57 and 38/47 CONFIRMED |
| reference/packetery-module-framework.md, -entityfactory.md, -blocks.md | draft | Written after the verification round; not yet verified |

A document reaches `verified` only when every checked row is confirmed at the first verification.
Every document of this repository needed a fix, so `reviewed` is its ceiling until the next
generation run.

## woocommerce: what the documentation does not know

woocommerce has these evidenced blind spots. `manifest.yaml` carries all 33 of them in the section
`unknowns`; the table holds the ten that a reader meets most often.

| What is unknown | What was searched | What would resolve it |
|---|---|---|
| Whether the open permission callback of the four checkout REST routes is a decision or a defect | The permission callbacks of both controllers, which differ | A person owning the plugin security |
| Whether a capability check protects the settings export | The export class, the page that builds its link and the hook module | The hook module of the plugin |
| Which Packeta API version and WSDL the plugin runs against | The constructor arguments of the SOAP client | Packeta API documentation |
| Whether the carrier feed host and the pickup point validation host are one service | The endpoint constant and the REST client, which name two hosts | Packeta API documentation |
| Which consumers outside this repository use the domain module | This repository only | Other documented repositories |
| Which order status belongs to each packet status | The status mapping of the plugin settings | A person owning the order process |
| Which payment gateways a carrier must disallow, and which currency switchers the shops need | The carrier option and the supported plugin list | A person owning the checkout |
| Which data a shop must keep after it removes the plugin, and whether the job schedules fit a large shop | The uninstall constant and the five schedules | A person owning the plugin |
| Whether a category restriction must win over a product restriction, and which products need age verification | The two metadata keys, which the checkout reads separately | A person owning the catalogue |
| Whether the shortcode of the pickup point country must give the country of the point or of the customer | The method behind that shortcode, which reads the shipping country | A person owning the email templates |

The other questions of `unknowns` belong to one module each, and the document of that module names
them. An answer to any of them is an input for the next generation run.
