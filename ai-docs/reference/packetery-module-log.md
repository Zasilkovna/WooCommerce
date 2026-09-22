---
title: "woocommerce — packetery-module-log module"
repo: woocommerce
module: packetery-module-log
generated-by: skill:generate-docs@0.3.5
source-commit: b34fe03c
last-generated: 2026-09-22
covers: [src/Packetery/Module/Log]
confidence: draft
tags: [ai-generated, repo-woocommerce, module-packetery-module-log, type-reference]
---

Repo: woocommerce · Module: packetery-module-log · Type: reference · Status: current

## packetery-module-log: purpose

packetery-module-log keeps the record of what the Packeta plugin did. The modules that get the
logger write one row for a call to Packeta, for a carrier update and for a failed table creation
[VERIFY: src/Packetery/Module/Log/DbLogger.php#add]. A module that does not get the logger writes
nothing here, and the type errors of the plugin go to the log of WooCommerce instead
[VERIFY: src/Packetery/Module/Log/ArgumentTypeErrorLogger.php#LEVEL_ERROR]. The administrator reads those rows on an admin
page with a filter [VERIFY: src/Packetery/Module/Log/Page.php#createLogListUrl].

packetery-module-log implements the logger interface of the domain module, so a module that writes
knows nothing about the storage [VERIFY: src/Packetery/Module/Log/DbLogger.php#DbLogger]. The module holds 8 files, 727 lines
of logic and 41 public methods. The settings export of the plugin reads the records of the last days
through the same service [VERIFY: src/Packetery/Module/Log/DbLogger.php#getForPeriodAsArray], and a
second class cuts the diagnostic file of the plugin to a size limit
[VERIFY: src/Packetery/Module/Log/LogSizeLimiter.php#getLimitedFilePartAsString].

> ⚠ add business context (elicitation)

## packetery-module-log: public interface

packetery-module-log exposes the logger, its repository, the admin page and three helpers.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| Write | `add` | Stores one record and sets its date when the caller gives none | [VERIFY: src/Packetery/Module/Log/DbLogger.php#add] |
| Read | `getRecords`, `countRecords` | Give the records of a filter and their number | [VERIFY: src/Packetery/Module/Log/DbLogger.php#getRecords] |
| Read a period | `getForPeriodAsArray` | Gives the records of a time range, oldest first, for the settings export | [VERIFY: src/Packetery/Module/Log/DbLogger.php#getForPeriodAsArray] |
| Delete | `deleteOld` | Deletes the records older than a date modifier | [VERIFY: src/Packetery/Module/Log/DbLogger.php#deleteOld] |
| Automatic deletion | `autoDeleteHook` | Deletes the old records with the window from the plugin option | [VERIFY: src/Packetery/Module/Log/Purger.php#autoDeleteHook] |
| Log page | `packeta-logs` | Admin page with the filter, the pagination and the manual deletion | [VERIFY: src/Packetery/Module/Log/Page.php#SLUG] |
| Page registration | `register` | Adds the page under the Packeta dashboard | [VERIFY: src/Packetery/Module/Log/Page.php#register] |
| Order link | `createLogListUrl`, `hasAnyRows` | Build the link to the log of one order and answer whether it holds a row | [VERIFY: src/Packetery/Module/Log/Page.php#hasAnyRows] |
| File limit | `getLimitedFilePartAsString`, `isFileFreshEnough` | Cut a text log file to a size limit and an age limit | [VERIFY: src/Packetery/Module/Log/LogSizeLimiter.php#getLimitedFilePartAsString] |
| Type errors | `log` | Writes a wrong argument type into the WooCommerce log | [VERIFY: src/Packetery/Module/Log/ArgumentTypeErrorLogger.php#LEVEL_ERROR] |

The log page needs the `manage_woocommerce` capability, and it shows 50 records on one page
[VERIFY: src/Packetery/Module/Log/Page.php#ITEMS_PER_PAGE]. The pagination comes from a small child
of the WordPress list table, which makes two protected methods public
[VERIFY: src/Packetery/Module/Log/ListTableFork.php#renderPagination].

## packetery-module-log: data model

packetery-module-log owns one table, and the name of that table comes from the database wrapper of
the plugin [VERIFY: src/Packetery/Module/Log/Repository.php#createOrAlterTable].

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| log | `id` | int(11), auto increment | Primary key | [VERIFY: src/Packetery/Module/Log/Repository.php#createOrAlterTable] |
| log | `order_id` | bigint(20) unsigned, null | Order that the record belongs to, when there is one | [VERIFY: src/Packetery/Module/Log/Repository.php#getWhereClause] |
| log | `title` | varchar(255) | Short text of the record | [VERIFY: src/Packetery/Module/Log/Repository.php#save] |
| log | `params` | text | Values of the record, stored as text | [VERIFY: src/Packetery/Module/Log/Repository.php#remapToRecord] |
| log | `status` | varchar(255) | `STATUS_SUCCESS` or `STATUS_ERROR` of the record | [VERIFY: src/Packetery/Core/Log/Record.php#STATUS_ERROR] |
| log | `action` | varchar(255) | Action of the record, for example the packet sending or the carrier list update | [VERIFY: src/Packetery/Module/Log/Page.php#getTranslatedActions] |
| log | `date` | datetime | Time of the record | [VERIFY: src/Packetery/Module/Log/DbLogger.php#add] |

The action values and the status values are constants of the domain module, and this module only
stores and translates them [VERIFY: src/Packetery/Module/Log/Repository.php#remapToRecord]. The
filter of the page builds the SQL conditions of the status, the date range and the text search
[VERIFY: src/Packetery/Module/Log/Repository.php#buildQueryConditions], and the order number and the
action come from a second method
[VERIFY: src/Packetery/Module/Log/Repository.php#getWhereClause].

## packetery-module-log: retention

packetery-module-log deletes its old records in two ways. A scheduled job deletes the records that
are older than the window of the plugin option, and the default window is in a constant of the
purger [VERIFY: src/Packetery/Module/Log/Purger.php#PURGER_MODIFIER_DEFAULT]. The option name is a
constant of the same class [VERIFY: src/Packetery/Module/Log/Purger.php#PURGER_OPTION_NAME].

The administrator deletes the old records from the page as well, and that action uses a fixed window
of seven days with a nonce check
[VERIFY: src/Packetery/Module/Log/Page.php#NONCE_DELETE_OLD]. The diagnostic file of the plugin has
its own limit: the module keeps the newest records that fit into a size limit and an age limit
[VERIFY: src/Packetery/Module/Log/LogSizeLimiter.php#getLimitedFilePartAsString], and it drops a
record that is older than the limit or larger than the limit by itself
[VERIFY: src/Packetery/Module/Log/LogSizeLimiter.php#flushCurrentRecord]. The state of that cut lives
in a small object [VERIFY: src/Packetery/Module/Log/LogSizeLimiterState.php#LogSizeLimiterState].

## packetery-module-log: dependencies

packetery-module-log depends on the domain module for the record shape and on the plugin for the
page. Structured lines:

references → packetery-core
references → packetery-module-root
references → packetery-module-forms
references → packetery-module-dashboard
references → packetery-module-views
references → packetery-module-framework

The module implements the logger interface of `packetery-core` and stores its record objects
[VERIFY: src/Packetery/Module/Log/DbLogger.php#add]. It reaches the database through the
wrapper of `packetery-module-root` [VERIFY: src/Packetery/Module/Log/Repository.php#save]. The filter
form comes from `packetery-module-forms`, and the page is a child of the dashboard page of
`packetery-module-dashboard` [VERIFY: src/Packetery/Module/Log/Page.php#register]. The page builds
its own links with the query helpers of `packetery-module-framework`
[VERIFY: src/Packetery/Module/Log/Page.php#createLogListUrl], and it uses the URL builder of
`packetery-module-views` for the images of the page
[VERIFY: src/Packetery/Module/Log/Page.php:174].

## packetery-module-log: known limitations

packetery-module-log has these limitations evidenced in the code. The manual deletion uses a fixed
window of seven days as a literal, while the scheduled deletion has a constant and an option
[VERIFY: src/Packetery/Module/Log/Page.php#deleteOldLogs]. The two windows can therefore differ
without a setting.

The schedule of the automatic deletion is not in this namespace. The module gives the callback, and
the plugin registers it [VERIFY: src/Packetery/Module/Log/Purger.php#autoDeleteHook]. The log table
has no index other than the primary key, so a filter over a large table reads the whole table
[VERIFY: src/Packetery/Module/Log/Repository.php#createOrAlterTable]. The page capability is a
literal of the registration. It equals the capability of the dashboard and of the print pages, and
it differs from the capability of the two settings pages
[VERIFY: src/Packetery/Module/Log/Page.php#register]. The module contains no TODO comment and no
FIXME comment.
