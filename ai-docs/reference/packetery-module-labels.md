---
title: "woocommerce — packetery-module-labels module"
repo: woocommerce
module: packetery-module-labels
generated-by: skill:generate-docs@0.3.5
source-commit: c5bc5fe5
last-generated: 2026-09-22
covers: [src/Packetery/Module/Labels]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-labels, type-reference]
---

Repo: woocommerce · Module: packetery-module-labels · Type: reference · Status: current

## packetery-module-labels: purpose

packetery-module-labels prepares what a label print needs. It reads the courier number of a packet
from Packeta and keeps it on the order
[VERIFY: src/Packetery/Module/Labels/CarrierLabelService.php#getPacketaPacketIdsWithCourierNumbers],
and it builds the print parameters: the label format and the number of label fields to skip
[VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#getOffset].

packetery-module-labels prints nothing by itself. The print pages of the order module ask this
module and then call Packeta
[VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#getLabelFormat]. The module
holds 4 files, 291 lines of logic and 19 public methods.

> ⚠ add business context (elicitation)

## packetery-module-labels: public interface

packetery-module-labels exposes one service for the courier numbers, one for the print parameters
and two small carriers of data.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Courier numbers | `getPacketaPacketIdsWithCourierNumbers` | Gives the packet identifiers with their courier numbers and reads the missing ones from Packeta | [VERIFY: src/Packetery/Module/Labels/CarrierLabelService.php#getPacketaPacketIdsWithCourierNumbers] |
| Offset form | `createForm` | Builds the selection of the first label field to use | [VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#createForm] |
| Offset value | `getOffset` | Takes the offset from the query, from the form, or gives none | [VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#getOffset] |
| Label format | `getLabelFormat`, `getLabelFormatByOrder` | Give the format of the Packeta label or of the carrier label | [VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#getLabelFormatByOrder] |
| External carriers | `removeExternalCarriers` | Removes the orders of external carriers when the print does not support them | [VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#removeExternalCarriers] |
| Print list | `addItem`, `getItems`, `getPacketIds`, `getOrderByOrderId` | Hold the orders and the packet identifiers of one print | [VERIFY: src/Packetery/Module/Labels/LabelPrintPacketData.php#getOrderByOrderId] |
| Print item | `getOrder`, `getPacketId` | One order and its packet in the print list | [VERIFY: src/Packetery/Module/Labels/LabelPrintPacketDataItem.php#getPacketId] |

The module asks Packeta for a courier number only when the order does not hold one
[VERIFY: src/Packetery/Module/Labels/CarrierLabelService.php#handleApiSuccess]. It then stores the
number on the order and adds a note to it. A wrong API password stops the whole run, and every other
fault is written to the log and the run continues with the next order
[VERIFY: src/Packetery/Module/Labels/CarrierLabelService.php#handleApiError].

## packetery-module-labels: dependencies

packetery-module-labels depends on the API client, on the order data and on the label settings.
Structured lines:

references → packetery-core
references → packetery-module-root
references → packetery-module-order
references → packetery-module-options

calls → packeta-soap-api (sync, SOAP)

The module reads the courier number over the SOAP client of `packetery-core`
[VERIFY: src/Packetery/Module/Labels/CarrierLabelService.php#handleApiSuccess]. It saves the number
through the order repository of `packetery-module-order`, and it takes the label type from the print
page of the same module [VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#getLabelFormat].
The label formats and the maximum offset of each format come from `packetery-module-options`
[VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#createForm].

## packetery-module-labels: known limitations

packetery-module-labels has these limitations evidenced in the code. The offset of the query string
is converted to a number and is not compared with the maximum offset of the format, so a larger
value passes this module [VERIFY: src/Packetery/Module/Labels/LabelPrintParametersService.php#getOffset].
Whether the print page limits it is outside this module.

One wrong API password ends the whole run and the module returns an empty list, so a print of many
orders gives no partial result [VERIFY: src/Packetery/Module/Labels/CarrierLabelService.php#handleApiError].
The module contains no TODO comment and no FIXME comment.
