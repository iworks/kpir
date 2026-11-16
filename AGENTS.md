# AGENTS.md — KPiR

> Working rules only. Technical knowledge lives in topic docs — read on demand, never preload.

## What the project is

The "KPiR" WordPress plugin — bookkeeping for **small companies in
Poland** whose accounting form is **KPiR — Księga Przychodu i Rozchodów**
(Revenue and Expense Ledger, the simplified bookkeeping Polish
sole-traders and small companies registered as civil partnerships use;
alternative to full accounting books). The plugin manages the ledger in
WordPress: invoice/contractor records as post types, monthly and annual
financial reports, JPK V7M / VAT(3) XML tax exports, and KSeF e-invoice
import. Structure: main file `kpir.php` + main class
(`includes/iworks/class-kpir.php`) + feature classes
(`includes/iworks/kpir/` — db, github updater, ksef, jpk, posttypes,
reports) + options config (`etc/options.php`, iworks_options format) +
JPK XML/HTML template parts (`template-parts/jpk/`) + vendored iWorks
options module (`includes/iworks/options` — git submodule). Built with a
bun bundler (`bundler/` → `release/` ZIPs, wporg and github flavors):
sources contain build-time placeholders (`PLUGIN_VERSION`,
`PLUGIN_TILL_YEAR`, …) replaced at build time; the version lives only in
`package.json`.

Git: the default branch is `master` (not `main`).

## Docs map — read only what the task needs

| Task | Read first |
| --- | --- |
| Bun bundler, build, ZIPs, placeholders, translations (.pot/.po/.mo), Plugin Check | `.opencode/docs/build.md` |
| Loading flow, post types, meta/custom fields, JPK templates, reports, KSeF, options, conventions | `.opencode/docs/project.md` |
| Feature class internals | the class file's header comment + method docblocks |

- When a change alters documented behavior, update the corresponding doc in
  the same task.

## Evergreen rules (every task)

- All documentation and README in English.
- `readme.txt` changelog is edited only when the user explicitly asks.
- Never commit, push, amend or create PRs without an explicit request.
- Naming: kebab-case files (`class-iworks-kpir-{name}.php`); PHP classes
  snake_case lowercase with `iworks_kpir_` prefix
  (`iworks_kpir_jpk_v7m`, `iworks_kpir_posttypes_invoice`).
- Version lives only in `package.json` — placeholders (`PLUGIN_VERSION`,
  `PLUGIN_TILL_YEAR`, `BUILDTIME`) are replaced by the bundler during
  build; never hardcode real values in sources.
- The `includes/iworks/options` submodule is a shared iWorks module —
  never edit it in this repo; upstream changes go to its own repository.
- Textdomain is `kpir`; option names get the `iworks_kpir_` prefix; custom
  post types are `iworks_kpir_invoice` and `iworks_kpir_contractor`.
- Every PHP file: `defined( 'ABSPATH' ) || exit;` (or `WPINC` guard in
  includes).
- Do not fix known typos/pitfalls without approval (see below).

## Known pitfalls (do not "fix" without approval)

- Source placeholders (`PLUGIN_VERSION`, `PLUGIN_TILL_YEAR`, …) are
  intentional — never replace them with real values in the repo.
- The build's prettier step rewrites JS sources in place (formatting-only
  diffs after a build are expected); the i18n step updates tracked
  `languages/*.po` files (timestamp diffs are normal).
- Option key `check_vat_on_monothly` (typo) — stored option name, renaming
  breaks existing installs.
- JPK schema template selection (`jpk/v7m{,-2,-3}`) is date-driven; adding
  a new schema version means a new template directory + a date rule in
  `includes/iworks/kpir/jpk/v7m.php`.
- `includes/iworks/kpir/class-iworks-kpir-db.php` contains a stray
  "chemistry table" comment — legacy copy-paste, harmless.
- Plugin Check (PCP) currently reports ~280 errors (mostly
  `EscapeOutput.OutputNotEscaped` and `date()` vs `gmdate()`) — the build
  fails on them; use `PLUGIN_CHECK_SKIP=1` for ZIPs without fixes, or fix
  findings explicitly.

## Commands

`bun run dev` (watcher: rebuilds assets on JS/SCSS change) ·
`bun run js` / `bun run css` (asset pipelines only) ·
`bun run build` (both flavors: wporg with Plugin Check first, then github) ·
`bun run build:wporg` (wp.org ZIP + Plugin Check) ·
`bun run build:github` (GitHub ZIP + .mo) ·
`bun run make-pot` (translations) ·
`bun run pcp` (Plugin Check only; builds the wporg ZIP first if missing) ·
`bun run test` (PHPUnit via composer, `tests/php/phpunit.xml`; uses
`tests/php/wp-stubs.php` — no WordPress install needed).
Requires PHP ≥ 8.0, WordPress ≥ 6.0, Bun ≥ 1.3.
