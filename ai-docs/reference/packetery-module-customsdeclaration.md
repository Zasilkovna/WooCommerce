---
title: "woocommerce — packetery-module-customsdeclaration module"
repo: woocommerce
module: packetery-module-customsdeclaration
generated-by: skill:generate-docs@0.3.5
source-commit: c5bc5fe5
last-generated: 2026-09-22
covers: [src/Packetery/Module/CustomsDeclaration]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-customsdeclaration, type-reference]
---

Repo: woocommerce · Module: packetery-module-customsdeclaration · Type: reference · Status: current

## packetery-module-customsdeclaration: purpose

packetery-module-customsdeclaration stores the customs declaration of an order and the items of that
declaration. A shipment that leaves the customs union needs the declaration, and the order module
sends it to Packeta with the packet
[VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#getByOrderNumber]. The module holds
1 file, 284 lines of logic and 12 public methods.

packetery-module-customsdeclaration owns the `customs-declaration` of an order in two tables, and it
keeps the invoice file and the export document inside them [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#createOrAlterTable].
The module holds no user interface: the metabox of the order module writes through it.

> ⚠ add business context (elicitation)

## packetery-module-customsdeclaration: public interface

packetery-module-customsdeclaration exposes one repository.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Read one | `getByOrderNumber` | Gives the declaration of an order with its items and with lazy files | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#getByOrderNumber] |
| Read items | `getItemsByCustomsDeclarationId`, `getCustomsDeclarationItemRows` | Give the items of one declaration as entities or as rows | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#getItemsByCustomsDeclarationId] |
| Write | `save` | Inserts or updates the declaration and writes the files separately | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#save] |
| Write an item | `saveItem` | Inserts or updates one item of the declaration | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#saveItem] |
| Delete | `delete`, `deleteItem` | Delete the declaration with its items, or one item | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#deleteItem] |
| Schema | `createOrAlterTable`, `createOrAlterItemTable` | Create or change the two tables of the module | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#createOrAlterItemTable] |
| Mapping | `declarationToDbArray`, `declarationItemToDbArray` | Map the entities to the columns of the tables | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#declarationItemToDbArray] |

## packetery-module-customsdeclaration: data model

packetery-module-customsdeclaration owns the declaration table and the item table.

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| customs declaration | `id`, `order_id` | int and bigint, not null | Primary key and the order of the declaration | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#createOrAlterTable] |
| customs declaration | `ead`, `mrn` | varchar | Export document number and the movement reference number | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#declarationToDbArray] |
| customs declaration | `invoice_number`, `invoice_issue_date`, `delivery_cost` | varchar, date and decimal | Invoice of the shipment and the delivery cost | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#declarationToDbArray] |
| customs declaration | `invoice_file`, `ead_file` | mediumblob, null | The two documents, stored in the row itself | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#save] |
| customs declaration | `invoice_file_id`, `ead_file_id` | varchar, null | Identifiers that Packeta gives to the stored files | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#declarationToDbArray] |
| declaration item | `id`, `customs_declaration_id` | int, not null | Primary key and the declaration of the item | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#createOrAlterItemTable] |
| declaration item | `customs_code`, `country_of_origin` | varchar(8) and char(2) | Customs code and the country of origin, stored in upper case | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#declarationItemToDbArray] |
| declaration item | `product_name`, `product_name_en` | varchar | Name of the goods in the local language and in English | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#declarationItemToDbArray] |
| declaration item | `value`, `units_count`, `weight` | decimal and int | Value, number of units and weight of the item | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#declarationItemToDbArray] |
| declaration item | `is_food_or_book`, `is_voc` | tinyint(1) | Flags that customs rules need | [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#declarationItemToDbArray] |

The read of a declaration asks only whether a file exists, and it loads the content of the file when
the caller wants it [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#getByOrderNumber].
The save leaves both files out of the row update and writes each one with its own statement
[VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#save].

## packetery-module-customsdeclaration: dependencies

packetery-module-customsdeclaration depends on the entities of the domain module and on the database
wrapper. Structured lines:

references → packetery-core
references → packetery-module-root
references → packetery-module-entityfactory
references → packetery-module-exception

The module maps the rows to the declaration entity and the item entity of `packetery-core`
[VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#getCustomsDeclarationItemRows]. It
builds those entities with the factory of `packetery-module-entityfactory`, it reaches the database
through the wrapper of `packetery-module-root`, and a failed delete raises the exception of
`packetery-module-exception` [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#delete].
The order module reads the declaration when it sends a packet, and its metabox writes it.

## packetery-module-customsdeclaration: known limitations

packetery-module-customsdeclaration has these limitations evidenced in the code. The two documents
live in the table as binary columns, so the size of the table grows with every declaration and a
backup carries the files [VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#createOrAlterTable].
The upload limit of a file belongs to the metabox of the order module and not to this module.

The names of the two file columns are a default argument of the save method, and the other column
names are written in the statements
[VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#save]. Neither table holds an index
other than the primary key, so a read by the order number scans the table
[VERIFY: src/Packetery/Module/CustomsDeclaration/Repository.php#createOrAlterItemTable]. The module
contains no TODO comment and no FIXME comment.
