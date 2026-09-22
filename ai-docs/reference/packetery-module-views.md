---
title: "woocommerce — packetery-module-views module"
repo: woocommerce
module: packetery-module-views
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [src/Packetery/Module/Views]
confidence: reviewed
tags: [ai-generated, repo-woocommerce, module-packetery-module-views, type-reference]
---

Repo: woocommerce · Module: packetery-module-views · Type: reference · Status: current

## packetery-module-views: purpose

packetery-module-views renders five templates of the Packeta plugin, and it loads the scripts and
the styles of every Packeta screen. The module renders the delivery detail of the admin order, the
order detail of the customer and the footer of the order email
[VERIFY: src/Packetery/Module/Views/ViewAdmin.php#renderDeliveryDetail]. The templates themselves
live in the `template` directory of the plugin, and the other modules render their own templates. It decides which asset
belongs to which screen [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets].

packetery-module-views also runs the onboarding tours of the plugin, which show the administrator
one setting after another [VERIFY: src/Packetery/Module/Views/WizardAssetManager.php#enqueueWizardAssets].
The module holds 6 files, 1090 lines of logic and 18 public methods. Nearly every value comes from
another module, and the module adds only presentation rules such as the first date that the date
picker of the order grid offers
[VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets].

> ⚠ add business context (elicitation)

## packetery-module-views: public interface

packetery-module-views exposes the render methods and the asset methods that the hook module
registers, and two builders that the other modules call directly.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Front assets | `enqueueFrontAssets` | Loads the checkout style and either the widget library or the checkout script | [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueFrontAssets] |
| Admin assets | `enqueueAdminAssets` | Loads the styles and the scripts of the admin screen that the request opens | [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets] |
| Asset URL | `buildAssetUrl` | Builds the URL of a plugin asset and adds a cache key from the file time | [VERIFY: src/Packetery/Module/Views/UrlBuilder.php#buildAssetUrl] |
| Carrier link | `getCarrierConfigLink` | Builds the admin link to the settings of one carrier | [VERIFY: src/Packetery/Module/Views/UrlBuilder.php#getCarrierConfigLink] |
| Order detail, admin | `renderDeliveryDetail` | Shows the pickup point and the validated address on the order detail | [VERIFY: src/Packetery/Module/Views/ViewAdmin.php#renderDeliveryDetail] |
| Confirm modal | `renderConfirmModalTemplate` | Renders the confirmation window of the packet actions | [VERIFY: src/Packetery/Module/Views/ViewAdmin.php#renderConfirmModalTemplate] |
| Missing WooCommerce | `echoInactiveWooCommerceNotice` | Shows the notice that WooCommerce is not active | [VERIFY: src/Packetery/Module/Views/ViewAdmin.php#echoInactiveWooCommerceNotice] |
| Order detail, customer | `renderOrderDetail` | Shows the delivery information to the customer | [VERIFY: src/Packetery/Module/Views/ViewFrontend.php#renderOrderDetail] |
| Email footer | `renderEmailFooter` | Adds the delivery information to the order email through one filter | [VERIFY: src/Packetery/Module/Views/ViewMail.php#renderEmailFooter] |
| Onboarding tours | `enqueueWizardAssets` | Starts the tour that the query parameters and the stored flags select | [VERIFY: src/Packetery/Module/Views/WizardAssetManager.php#enqueueWizardAssets] |

## packetery-module-views: assets and templates

packetery-module-views loads a different set of assets for each screen. The checkout gets the front
style, and it gets the widget library when the shop uses the block checkout, or the checkout script
when it uses the classic one [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueFrontAssets].
The module passes the widget settings to the browser under one name, and the values come from the
checkout module.

| Screen | What the module loads | Data for the browser | Anchor |
|---|---|---|---|
| Checkout | `packetery-front-styles`, `packetery-checkout` or `packetery-widget-library` | `packeteryCheckoutSettings` | [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueFrontAssets] |
| Order grid | `packetery-admin-styles`, `packetery-admin-grid-order-edit-js`, `packetery-admin-stored-until-modal-js` | `datePickerSettings` | [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets] |
| Order detail | `packetery-multiplier`, `admin-order-detail`, the pickup point picker and the address picker | `packeteryPickupPointPickerSettings`, `packeteryAddressPickerSettings` | [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets] |
| Carrier settings | `packetery-select2`, `packetery-multiplier`, `packetery-admin-country-carrier` | `packeteryCountryCarrier` | [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets] |
| Plugin settings | `packetery-admin-options`, the WordPress editor and the bug report editor | `translationsAdminOptions` | [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets] |
| Plugin list | `packetery-admin-confirm-deactivation` | `translationsDeactivation` | [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets] |
| Onboarding tour | The tour library and one script for each tour | `wizardTourConfig` | [VERIFY: src/Packetery/Module/Views/WizardAssetManager.php#enqueueWizardAssets] |

The module renders five templates: the delivery detail of the admin order, the confirmation modal,
the admin notice, the order detail of the customer and the email footer
[VERIFY: src/Packetery/Module/Views/ViewFrontend.php#renderOrderDetail]. A shop can add its own
checkout style, because the module loads a file of a fixed name from the content directory when that
file exists [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueFrontAssets].

## packetery-module-views: dependencies

packetery-module-views depends on the modules that own the data it shows. Structured lines:

references → packetery-core
references → packetery-module-root
references → packetery-module-order
references → packetery-module-checkout
references → packetery-module-carrier
references → packetery-module-options
references → packetery-module-log
references → packetery-module-framework
references → packetery-module-dashboard
references → packetery-module-shipping

The admin order detail reads the order through the repository of `packetery-module-order`
[VERIFY: src/Packetery/Module/Views/ViewAdmin.php#renderDeliveryDetail], and the widget settings of
the pickup point picker come from the metabox of the same module
[VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets]. The checkout settings come
from `packetery-module-checkout` [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueFrontAssets].
The module asks `packetery-module-root` which admin screen the request opens
[VERIFY: src/Packetery/Module/Views/ViewAdmin.php#renderConfirmModalTemplate]. The URL of the widget
library comes from the resolver of `packetery-module-root`
[VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueFrontAssets].

## packetery-module-views: known limitations

packetery-module-views has these limitations evidenced in the code. Three render methods end without
a message when the input is not a WooCommerce order, or when the order has no valid carrier. Two of
them write the type error to the log [VERIFY: src/Packetery/Module/Views/ViewFrontend.php#renderOrderDetail],
and the render of the email footer returns without a record
[VERIFY: src/Packetery/Module/Views/ViewMail.php#renderEmailFooter]. The asset URL is `null` when the
file is missing, and the caller gets no error
[VERIFY: src/Packetery/Module/Views/UrlBuilder.php#buildAssetUrl].

The onboarding tour reads a page number from the request without a nonce check, and a comment says
that the read is safe [VERIFY: src/Packetery/Module/Views/WizardAssetManager.php#enqueueWizardAssets].
The decision which asset belongs to which screen is one long branch, so a new admin screen changes
that method [VERIFY: src/Packetery/Module/Views/AssetManager.php#enqueueAdminAssets]. The module
contains no TODO comment and no FIXME comment.
