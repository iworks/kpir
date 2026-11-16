# Project mechanics — KPiR

> Loading flow, post types, meta/custom fields, JPK templates, reports,
> KSeF import, options, conventions. Read only when the task involves
> these areas.

## Overview

The "KPiR" plugin (slug `kpir`, textdomain `kpir`) implements
bookkeeping for **small companies in Poland** using the **KPiR —
Księga Przychodu i Rozchodów** (Revenue and Expense Ledger) accounting
form — the simplified ledger for Polish sole traders (JDG) and small
partnerships, instead of full accounting books. The plugin implements
this ledger in WordPress: invoices and contractors as post types,
monthly/annual reports, JPK V7M and VAT(3) XML exports, and KSeF
e-invoice import. Sources contain literal placeholders (`PLUGIN_VERSION`,
`PLUGIN_TILL_YEAR`, `BUILDTIME`, …) replaced by the bun bundler at build
time (see `build.md`). The version lives only in `package.json`.

## Directory structure

```
/
├── kpir.php                        # Plugin bootstrap (header, constants, options loader)
├── etc/
│   └── options.php                 # Options + admin pages config (iworks_options format)
├── includes/
│   └── iworks/
│       ├── iworks.php              # Base class (updater boilerplate)
│       ├── class-kpir.php          # Main class iworks_kpir (controller)
│       ├── options/                # Git SUBMODULE — iworks options framework
│       └── kpir/
│           ├── class-iworks-kpir-db.php      # DB table install/drop, NIP normalization
│           ├── class-iworks-kpir-github.php  # GitHub self-updater (excluded from wporg ZIP)
│           ├── class-iworks-kpir-ksef.php    # KSeF XML import → invoice posts
│           ├── jpk.php                       # Abstract JPK base (templates, money, contractors)
│           ├── jpk/v7m.php                   # JPK V7M export (schema chosen by date)
│           ├── jpk/vat_3.php                 # JPK VAT(3) export
│           ├── posttypes.php                 # Abstract post-type base (fields, meta save)
│           ├── posttypes/invoice.php         # Invoice post type (largest class)
│           ├── posttypes/contractor.php      # Contractor post type
│           └── reports/{monthly,annually}.php
├── template-parts/jpk/             # Overridable templates: {v7m,v7m-2,v7m-3}/{xml/*.php,show.php}
├── languages/                      # kpir.pot + pl_PL .po/.mo
├── assets/                         # admin JS src + SCSS src, externals (select2, datepicker)
├── package.json                    # Version + release metadata (single source of truth)
├── bundler/                        # Bun build system — see build.md
└── readme.txt                      # WordPress.org readme
```

## Loading flow

1. WordPress loads `kpir.php`:
   - defines `IWORKS_KPIR_VERSION` (`'PLUGIN_VERSION'`) and
     `IWORKS_KPIR_PREFIX` (`'iworks_kpir_'`);
   - requires `includes/iworks/class-kpir.php`, `etc/options.php` and the
     vendored `iworks_options` (class_exists guarded);
   - `iworks_kpir_get_options()` returns a global `iworks_options`
     instance (option function `iworks_kpir_options`, prefix
     `iworks_kpir_`);
   - instantiates `iworks_kpir`, registers activation/deactivation hooks.
2. `iworks_kpir::__construct()`:
   - capability: `apply_filters( 'iworks_kpir_capability', 'manage_options' )`;
   - instantiates the `invoice` and `contractor` post-type classes
     (from `includes/iworks/kpir/posttypes/`), stored as
     `$this->post_type_{name}` and in `$this->objects`;
   - hooks `admin_init`, `wp_loaded` → `plugins_loaded` (priority 1);
   - conditionally loads (file_exists guarded): `class-iworks-kpir-db.php`
     (instantiated immediately), `class-iworks-kpir-github.php`
     (`$this->objects['github']` — file is **excluded from the wporg
     ZIP**, so it never loads on wp.org installs),
     `class-iworks-kpir-ksef.php` (`$this->objects['ksef']`, receives the
     objects container).
3. `plugins_loaded()` dispatches on `$_REQUEST['action']`:
   `iworks_kpir_jpk_vat_3` and `iworks_kpir_jpk_v7m` load the JPK module
   via `get_module_file()` and call `get_xml( $this )` (nonce-verified
   inside the JPK class).

## Admin pages (etc/options.php + iworks_options)

- Main settings page `index` — submenu under the **Invoices** list page
  (`edit.php?post_type=iworks_kpir_invoice`).
- Extra pages (all submenus of the same parent): `ksef_import` (KSeF
  Import — callback `iworks_kpir_ksef_import`, dispatches the
  `iworks/kpir/admin/page/ksef/import` action), `report_monthly`
  (Monthly Report), `report_annually` (Annual Report), plus commented-out
  `jpk_vat_3` / `jpk_v7m` pages.
- Global options (prefix `iworks_kpir_`): `nip`, `taxpayer_type`
  (person/company, since 1.2.0), person fields (`name`, `surname`,
  `last_name`, `date_of_birth`, `email`, `department_of_revenue`,
  `phone`), `cash_pit` (cash-method PIT), `check_vat_on_monothly`
  (**typo is the stored name — do not rename**).
- Standalone callbacks live at the bottom of `etc/options.php`
  (`iworks_kpir_report_monthly()` etc.) delegating to
  `iworks_kpir->show_page_reports()` / `show_page_jpk_*()`.

## Post types

- `iworks_kpir_invoice` (class `iworks_kpir_posttypes_invoice`) — incomes,
  expenses (costs), salaries, assets; meta boxes: basic, income, expense,
  salary, asset; admin columns sorted by date; collapsed meta boxes.
- `iworks_kpir_contractor` (class `iworks_kpir_posttypes_contractor`) —
  contractor directory (NIP, address data), linked from invoices via the
  `_iworks_kpir_basic_contractor` meta.
- Both extend the abstract `iworks_kpir_posttypes` (field definitions,
  meta save with `iworks_kpir_posttype_update_post_meta` action, cash-PIT
  awareness via the `cash_pit` option).

## Custom fields (invoice)

Named via public getters (`get_custom_field_*_name()`), values stored as
`iworks_kpir_*` post meta:

- `get_custom_field_basic_type_name()` — income/expense discriminator;
- `get_custom_field_basic_date_name()` — invoice issue date (timestamp);
- `get_custom_field_date_of_cash_name()` — cash-PIT payment date;
- `get_custom_field_year_month_name()` / `_cash_name()` — normalized
  `YYYY-MM` keys used for monthly queries (written by
  `save_year_month_to_extra_field()` on meta save);
- `get_custom_field_basic_contractor_name()` — contractor post ID.
- Option-style meta (`basic_description`, `basic_ksef_number`,
  `expense_car`, …) goes through `$this->options->get_option_name()`.
- VAT rates for car-related expenses: 20% / 75% / 100% (option-driven).

## Dashboard widgets

`add_dashboard_widgets()` registers two widgets (current + past month,
titles via `_x( 'Y F', … )`); each renders
`iworks_kpir_posttypes_invoice->month_table( 'YYYY-MM' )` — a summary
table querying by the year-month custom field.

## JPK exports (jpk.php + jpk/v7m.php + jpk/vat_3.php)

- Abstract `iworks_kpir_jpk` holds template rendering
  (`get_template()` → `get_template_part( 'kpir/{base}', {file}, $args )`
  — **theme-overridable** via a `kpir/` template directory), contractor
  caching, money formatting, and validation of `YYYY-MM` input.
- `iworks_kpir_jpk_v7m::get_xml()` requires `nonce`, `purpose` and `m`
  request params (nonce action = class `$name` = `kpir-jpk-v7m`), then
  picks the schema template by date:
  - `jpk/v7m/xml` — original V7M;
  - `jpk/v7m-2/xml` — V7M-2 from 2021;
  - `jpk/v7m-3/xml` — V7M-3 from 2026-02 (year > 2025 && month > 1).
- Template parts per schema (`xml/head|company|person|income|expense|
  incomes-summary|expenses-summary|incomes-empty|summary|evidence-start|
  evidence-end|footer.php` + `show.php` HTML view) + an example XML
  (`v7m-3/jpk_v7m-3.example.xml`).
- VAT(3) follows the same pattern with its own template set.

## KSeF import (class-iworks-kpir-ksef.php)

- Admin page (action `iworks/kpir/admin/page/ksef/import` → `run()`)
  renders the upload form; `handle_import()` on `wp_loaded` processes
  uploaded XML files (multi-file aware), parses with SimpleXML and
  creates invoice posts; notices collected per file.
- The DB class normalizes NIP values on install (strips spaces, dashes,
  `PL` prefix) to match KSeF data.

## Reports

- `iworks_kpir_reports_monthly` / `_annually` — financial summaries
  (income, expenses, VAT, contractor data) queried from invoice meta;
  both support standard and cash-PIT (payment-date) accounting, driven by
  the `cash_pit` option.

## DB layer (class-iworks-kpir-db.php)

- Registers tables on `$wpdb` (currently none defined in
  `$table_names` — infrastructure for future use);
- `db_install()` on activation + `shutdown`, versioned via the
  `iworks_kpir_db_version` option (NIP normalization ran at v2);
- drops tables on `iworks/kpir/register_uninstall_hook`.

## GitHub updater (class-iworks-kpir-github.php)

- Hooks `pre_set_site_transient_update_plugins` / `upgrader_process_complete`;
  queries the GitHub releases API for `iworks/kpir`; pairs with the
  **github** release flavor (self-hosted ZIP); excluded from the wporg
  build.

## Constants & globals

- `IWORKS_KPIR_VERSION`, `IWORKS_KPIR_PREFIX`;
- global `$iworks_kpir` (main instance), `$iworks_kpir_options`;
- `iworks_kpir_get_options()` accessor.

## Translations (i18n)

- Textdomain `kpir`; `languages/kpir.pot` + `kpir-pl_PL.po/.mo`.
- `bun run make-pot` regenerates the `.pot` and updates `.po` files;
  `.mo` compiles only in `build:github` (wp.org serves its own).

## Tests

- PHPUnit 12, `tests/php/phpunit.xml`, bootstrap
  `tests/php/bootstrap.php` loading `tests/php/wp-stubs.php` (WordPress
  function stubs) — runs without WordPress or a database.
- Suites: `IworksJpkTest`, `IworksKpirKsefTest`.
- `bun run test` uses `vendor/bin/phpunit` (composer) when available.

## General conventions

- Formatting: tabs for PHP, LF, final newline (`.editorconfig`);
  WordPress-style docblocks with `@since` tags everywhere.
- Every PHP file: `defined( 'ABSPATH' ) || exit;` (or
  `if ( ! defined( 'WPINC' ) ) die;` in includes).
- File naming: kebab-case (`class-iworks-kpir-{name}.php`); classes
  snake_case lowercase (`iworks_kpir_jpk_v7m`).
- Hooks: `iworks/kpir/...` (namespaced) for new filters/actions;
  `iworks_kpir_*` for legacy/internal. Currently public filters:
  `iworks_kpir_capability`, `iworks/kpir/duplicate_invoice/skip_meta`,
  `iworks/kpir/admin/page/ksef/import` (action);
  `iworks/kpir/register_activation_hook` /
  `iworks/kpir/register_uninstall_hook` (actions fired by the main
  class's activation/deactivation hooks, consumed by the DB class).
- Options/meta names carry the `iworks_kpir_` prefix.
- Assets: SCSS sources in `assets/styles/src/admin/`, JS sources in
  `assets/scripts/admin/src/`; compiled outputs are gitignored and built
  by the bundler; externals (select2, jquery-ui datepicker) are vendored
  under `assets/externals/`.
