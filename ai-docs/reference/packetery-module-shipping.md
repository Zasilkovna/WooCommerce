---
title: "woocommerce — packetery-module-shipping module"
repo: woocommerce
module: packetery-module-shipping
generated-by: skill:generate-docs@0.3.5
source-commit: ffca7e2e
last-generated: 2026-09-22
covers: [src/Packetery/Module/Shipping]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-module-shipping, type-reference]
---

Repo: woocommerce · Module: packetery-module-shipping · Type: reference · Status: current

## packetery-module-shipping: purpose

packetery-module-shipping gives each Packeta carrier its own WooCommerce shipping method. The shop
owner then adds the carrier to a shipping zone in the same way as any other method
[VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#__construct]. One base
class holds the behaviour of every carrier, and a generated class holds the identifier of one
carrier [VERIFY: src/Packetery/Module/Shipping/ShippingMethodGenerator.php#generateClass].

packetery-module-shipping computes no price by itself. The method asks the checkout module for the
rates of the carriers of the zone
[VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#calculate_shipping]. The module also
answers whether an order used a Packeta method
[VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#wcOrderHasOurMethod]. The namespace
holds 148 files, 1521 lines of logic and 18 public methods, and 144 of those files are generated
carrier classes that the repository keeps under version control.

> ⚠ add business context (elicitation)

## packetery-module-shipping: public interface

packetery-module-shipping exposes the shipping methods of the carriers and three services of the
plugin.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Shipping method | `packeta_method_` | Identifier prefix of every generated carrier method | [VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#PACKETA_METHOD_PREFIX] |
| Method identifier | `getShippingMethodId` | Builds the method identifier from the carrier identifier | [VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#getShippingMethodId] |
| Rate calculation | `calculate_shipping` | Asks the checkout module for the rates and adds them to the package | [VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#calculate_shipping] |
| Instance settings | `init` | Loads the title and the settings of one zone instance | [VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#init] |
| Method list | `getSortedCachedMethods` | Gives WooCommerce the methods of the carriers that the zone allows, sorted by title | [VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#getSortedCachedMethods] |
| Method test | `isPacketaMethod` | Answers whether a method identifier belongs to Packeta | [VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#isPacketaMethod] |
| Order test | `wcOrderHasOurMethod` | Answers whether an order used a Packeta method | [VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#wcOrderHasOurMethod] |
| Class loading | `loadClasses` | Includes the generated classes, and skips the car delivery carriers when the shop disables them | [VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#loadClasses] |
| Class generation | `generateClass` | Writes the class of one carrier | [VERIFY: src/Packetery/Module/Shipping/ShippingMethodGenerator.php#generateClass] |
| Bulk generation | `generateClasses` | Writes the class of every carrier that has none | [VERIFY: src/Packetery/Module/Shipping/ShippingMethodBulkGenerator.php#generateClasses] |

The settings of one zone instance link to the carrier settings page, and the instance shows the
carrier modal as a template fragment
[VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#get_instance_form_fields].

## packetery-module-shipping: class generation

packetery-module-shipping writes one PHP class for each carrier. The generator builds the file with
a code generator, not with a template. The class name is the carrier identifier with a fixed prefix,
the class extends the base method, and its only member is the carrier identifier
[VERIFY: src/Packetery/Module/Shipping/ShippingMethodGenerator.php#generateClass]. The target
directory is a subdirectory of the namespace
[VERIFY: src/Packetery/Module/Shipping/ShippingMethodGenerator.php#getTargetDirectory]. The
generator tests that the directory is writable, and it returns `false` when the directory is not
writable [VERIFY: src/Packetery/Module/Shipping/ShippingMethodGenerator.php#generateClass].

The bulk generator collects the carriers from two sources. It takes the internal pickup point
carriers from the carrier configuration, and it takes the feed carriers from a fresh download
[VERIFY: src/Packetery/Module/Shipping/ShippingMethodBulkGenerator.php#generateClasses]. A carrier
that already has a class is skipped. When the download gives no answer, the run ends and writes
nothing. The carrier update writes the class of one new carrier in the same way.

The plugin then includes every generated file at runtime
[VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#loadClasses]. The method list of a zone
keeps only the carriers of the countries of that zone, and only the carriers that are active
[VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#getSortedCachedMethods].

## packetery-module-shipping: dependencies

packetery-module-shipping depends on the carrier module for the carrier data and on the checkout
module for the price. Structured lines:

references → packetery-core
references → packetery-module-carrier
references → packetery-module-checkout
references → packetery-module-root
references → packetery-module-framework

The method reads the carrier entity and the carrier options of `packetery-module-carrier`, and it
uses them for the title and for the active state
[VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#getSortedCachedMethods]. The method asks
`packetery-module-checkout` for the rates of the package, and it gives that call the carrier
identifiers and the titles of the Packeta methods of the zone, including its own
[VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#calculate_shipping]. The base method
takes its services from the service container of the plugin, because WooCommerce builds a shipping
method without a container [VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#__construct].
The module reads the countries of a zone through the repository of `packetery-module-root`
[VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#addMethods], and it reads the car
delivery identifiers of `packetery-core`
[VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#loadClasses].

## packetery-module-shipping: known limitations

packetery-module-shipping has these limitations evidenced in the code. The generated classes live in
the repository, so a new carrier of the feed changes the source tree of an installed plugin
[VERIFY: src/Packetery/Module/Shipping/ShippingMethodGenerator.php#getTargetDirectory]. An
installation with a read only plugin directory gets no class for a new carrier, and the generator
reports that state only as a return value and a message
[VERIFY: src/Packetery/Module/Shipping/ShippingMethodBulkGenerator.php#generateClasses].

The loading of the generated classes reads the directory without a test of the result
[VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#loadClasses]. The base method sets the
enabled state and the tax status as fixed values, and a comment says that the enabled state can
become a setting [VERIFY: src/Packetery/Module/Shipping/BaseShippingMethod.php#__construct]. The
sorted method list is cached for one request under a hash of the original list
[VERIFY: src/Packetery/Module/Shipping/ShippingProvider.php#getSortedCachedMethods]. The module
contains no TODO comment and no FIXME comment.
