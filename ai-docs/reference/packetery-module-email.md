---
title: "woocommerce — packetery-module-email module"
repo: woocommerce
module: packetery-module-email
generated-by: skill:generate-docs@0.3.5
source-commit: c5bc5fe5
last-generated: 2026-09-22
covers: [src/Packetery/Module/Email]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-email, type-reference]
---

Repo: woocommerce · Module: packetery-module-email · Type: reference · Status: current

## packetery-module-email: purpose

packetery-module-email puts the Packeta data of an order into the emails of the shop, and it sends
the bug report of the administrator to the Packeta support. The shop owner writes a shortcode into
an email template, and the module replaces it with the value of that order
[VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#register]. The module holds 3 files, 347
lines of logic and 21 public methods.

packetery-module-email collects the diagnostic files of the installation for the bug report. These
are the settings export, the system status of WooCommerce and the recent log files, all in one
archive [VERIFY: src/Packetery/Module/Email/BugReportAttachment.php#createAttachments].

> ⚠ add business context (elicitation)

## packetery-module-email: public interface

packetery-module-email exposes fourteen shortcodes and the bug report.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Shortcode registration | `register` | Registers every Packeta shortcode of the email templates | [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#register] |
| Tracking | `packeta_tracking_number`, `packeta_tracking_url` | Give the packet barcode and the tracking address | [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#trackingNumber] |
| Pickup point | `packeta_pickup_point_id`, `packeta_pickup_point_name`, `packeta_pickup_point_address` | Give the identifier, the name and the full address of the pickup point | [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#pickupPointAddress] |
| Pickup point parts | `packeta_pickup_place`, `packeta_pickup_point_street`, `packeta_pickup_point_city`, `packeta_pickup_point_zip`, `packeta_pickup_point_country` | Give one part of the pickup point address | [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#pickupPointStreet] |
| Carrier | `packeta_carrier_name` | Gives the name of the carrier of the order | [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#carrierName] |
| Conditions | `packeta_if_packet_submitted`, `packeta_if_pickup_point`, `packeta_if_carrier` | Show their content only when the order matches the condition | [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#ifPacketSubmitted] |
| Bug report | `sendBugReport` | Sends the message of the administrator with the diagnostic archive | [VERIFY: src/Packetery/Module/Email/BugReportEmail.php#sendBugReport] |
| Diagnostic archive | `createAttachments` | Builds the archive of the settings export, the system status and the logs | [VERIFY: src/Packetery/Module/Email/BugReportAttachment.php#createAttachments] |
| Fresh logs | `addFreshLogsToZipByPrefix` | Adds the recent log files of one name prefix to the archive | [VERIFY: src/Packetery/Module/Email/BugReportAttachment.php#addFreshLogsToZipByPrefix] |

Every shortcode takes the order number in one attribute, and it gives an empty text when the order
does not exist [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#findOrder]. The archive stays
empty when the installation has no archive extension of PHP
[VERIFY: src/Packetery/Module/Email/BugReportAttachment.php#createAttachments].

## packetery-module-email: dependencies

packetery-module-email depends on the order data and on the diagnostic sources. Structured lines:

references → packetery-core
references → packetery-module-root
references → packetery-module-order
references → packetery-module-options
references → packetery-module-log

The shortcodes read the order entity and its pickup point of `packetery-core` through the repository
of `packetery-module-order` [VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#pickupPointName].
The archive takes the settings export of `packetery-module-options` and the size limit of the
diagnostic file of `packetery-module-log`
[VERIFY: src/Packetery/Module/Email/BugReportAttachment.php#addTracyLogsToZip]. The address of the
support comes to the email class as a constructor argument, so its value lives outside this module
[VERIFY: src/Packetery/Module/Email/BugReportEmail.php#createEmailBody].

## packetery-module-email: known limitations

packetery-module-email has these limitations evidenced in the code. The shortcode of the pickup
point country gives the shipping country of the order and not the country of the pickup point, so
its name and its value do not agree
[VERIFY: src/Packetery/Module/Email/EmailShortcodes.php#pickupPointCountry]. A template that uses it
for a pickup point order shows the country of the customer.

The names of the log files and the path of the debug file of WordPress are fixed strings of the
archive class [VERIFY: src/Packetery/Module/Email/BugReportAttachment.php#addWpDebugLogToZip]. The
bug report carries the settings of the shop, so it inherits the masking of the settings export and
nothing more. The module contains no TODO comment and no FIXME comment.
