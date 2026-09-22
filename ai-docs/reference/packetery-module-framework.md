---
title: "woocommerce — packetery-module-framework module"
repo: woocommerce
module: packetery-module-framework
generated-by: skill:generate-docs@0.3.5
source-commit: 43b25221
last-generated: 2026-09-22
covers: [src/Packetery/Module/Framework]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-framework, type-reference]
---

Repo: woocommerce · Module: packetery-module-framework · Type: reference · Status: current

## packetery-module-framework: purpose

packetery-module-framework wraps the functions of WordPress and of WooCommerce in two adapters, so
the other modules take a service instead of a global function
[VERIFY: src/Packetery/Module/Framework/WpAdapter.php#WpAdapter]. A test can then replace the
platform with a double, which a global function does not permit. The module holds 16 files, 537
lines of logic and 115 public methods.

packetery-module-framework carries no Packeta rule. It depends on no other module of the
repository, and it is the lowest layer of the plugin
[VERIFY: src/Packetery/Module/Framework/WcAdapter.php#WcAdapter]. A reader who looks for a business
rule finds it in the module that calls the adapter, not here.

> ⚠ add business context (elicitation)

## packetery-module-framework: public interface

packetery-module-framework exposes two adapters. Each one holds its own methods and a set of traits
that group the platform by topic.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| WordPress adapter | `WpAdapter` | Query arguments, terms, screens, nonces, multisite, admin addresses, mail and shortcodes | [VERIFY: src/Packetery/Module/Framework/WpAdapter.php#getTerm] |
| WooCommerce adapter | `WcAdapter` | Products, shipping zones and packages, currency, orders and the logger | [VERIFY: src/Packetery/Module/Framework/WcAdapter.php#productFactoryGetProduct] |
| Hooks | `HookTrait` | Registers actions and filters and applies them | [VERIFY: src/Packetery/Module/Framework/HookTrait.php#HookTrait] |
| Options and transients | `OptionTrait`, `TransientTrait` | Read and write the options and the transients of WordPress | [VERIFY: src/Packetery/Module/Framework/OptionTrait.php#OptionTrait] |
| Posts and terms | `PostTrait` | Read the posts of WordPress | [VERIFY: src/Packetery/Module/Framework/PostTrait.php#PostTrait] |
| Escaping and translation | `EscapingTrait`, `TranslationTrait` | Escape the output and translate the texts of the plugin | [VERIFY: src/Packetery/Module/Framework/EscapingTrait.php#EscapingTrait] |
| Assets and HTTP | `AssetTrait`, `HttpTrait` | Register the scripts and the styles, and send the HTTP requests | [VERIFY: src/Packetery/Module/Framework/AssetTrait.php#AssetTrait] |
| Cart and session | `WcCartTrait`, `WcSessionTrait` | Read the cart and read and write the session of WooCommerce | [VERIFY: src/Packetery/Module/Framework/WcCartTrait.php#WcCartTrait] |
| Customer and taxes | `WcCustomerTrait`, `WcTaxTrait` | Read the customer and compute the taxes of a shipping rate | [VERIFY: src/Packetery/Module/Framework/WcTaxTrait.php#WcTaxTrait] |
| Scheduled actions | `ActionSchedulerTrait` | Plans an action of the Action Scheduler | [VERIFY: src/Packetery/Module/Framework/ActionSchedulerTrait.php#ActionSchedulerTrait] |

## packetery-module-framework: dependencies

packetery-module-framework depends on WordPress and on WooCommerce, and on nothing else of this
repository. Structured lines:

called from → woocommerce

The module holds no `use` statement of another Packeta namespace
[VERIFY: src/Packetery/Module/Framework/WpAdapter.php#getTerm], so a change of a Packeta module
never changes this one. Every other module of the plugin takes one or both adapters in its
constructor [VERIFY: src/Packetery/Module/Framework/WcAdapter.php#getLogger]. The adapters
themselves call the global functions and the static classes of the platform. The wrapper of the
Action Scheduler is the one exception that reaches a plugin of the shop and not the platform
[VERIFY: src/Packetery/Module/Framework/ActionSchedulerTrait.php#ActionSchedulerTrait], and the
scheduled jobs of Packeta go through it.

## packetery-module-framework: known limitations

packetery-module-framework has these limitations evidenced in the code. The adapters do not cover
the whole platform, and several modules still call a global function directly
[VERIFY: src/Packetery/Module/Framework/HookTrait.php#HookTrait]. The wrapper is a convention, not a
boundary that the code enforces.

Three methods hold more than a pass through. One normalises the answer of the order query of
WooCommerce, which returns either a list or an object
[VERIFY: src/Packetery/Module/Framework/WcAdapter.php#getOrdersWithoutPagination]. One returns
nothing when the logging class of WooCommerce is missing
[VERIFY: src/Packetery/Module/Framework/WcAdapter.php#loggingUtilGetLogDirectory]. One returns two
possible types of the logger, and only the comment says so
[VERIFY: src/Packetery/Module/Framework/WcAdapter.php#getLogger]. The module contains no TODO
comment and no FIXME comment.
