# Build — Bun bundler

> Toolchain, modes, pipeline, placeholders, release ZIPs, translations,
> Plugin Check. Read only when working on the build.

## Toolchain

- Bun (>= 1.3) — package manager, script runner and the bundler runtime
  (`bundler/index.js` modes). `bun.lock` is committed; `package-lock.json`
  is gitignored. Node is not required.
- PHP ≥ 8.0; `wp-cli.phar` (downloaded by `postinstall`, gitignored) for
  translations and Plugin Check. Composer dev-dependencies provide PHPUnit
  (`vendor/bin/phpunit`).
- Tooling runs **in-process** (`bundler/tools.mjs`): prettier (JS),
  sass (SCSS), terser (JS minify), clean-css (CSS minify).

## Files (`bundler/`)

| File               | Role                                                                                     |
| ------------------ | ---------------------------------------------------------------------------------------- |
| `index.js`         | Modes: `dev` (default) / `js` / `css` / `build` / `build:wporg` / `build:github` / `make-pot` / `pcp` / `test` |
| `config.mjs`       | Paths, names (from `package.json`), placeholder patterns, release copy rules per flavor  |
| `pipelines.mjs`    | Asset pipelines: JS concat+min, SCSS compile+concat+min; prettier pass; clean            |
| `tools.mjs`        | In-process APIs (prettier, sass, terser, clean-css, concat, atomic writes, glob helpers) |
| `makeZip.mjs`      | Staging copy → placeholder replacement → ZIP (archiver, level 9)                          |
| `makePot.mjs`      | wp-cli i18n: `make-pot` → `update-po` (→ `make-mo` for github)                            |
| `pluginCheck.mjs`  | Plugin Check (PCP) on the wporg release; summary tables; `plugin-check.log`               |
| `spawnAsync.mjs`   | `child_process.spawn` wrapper with captured output                                        |
| `consoleHelpers.mjs`, `progressBar.mjs` | Logging, timing, build progress bar                                  |

All names derive from `package.json` (`name`, `title`, `version`,
`homepage`, `repository.website`, `requires`, `tested`, `tags`,
`author[0]`) at runtime.

## Asset pipelines (grunt parity)

- **JS**: `assets/scripts/admin/src/{datepicker,invoice,jpk,select2}.js`
  (order matters) → concat → `assets/scripts/admin/kpir.js` → terser →
  `kpir.min.js`. Version banner prepended; `jQuery` is mangle-reserved.
- **CSS**: `assets/styles/src/admin/{post-type-invoice,wordpress-admin-dashboard,reports}.scss`
  → sass (expanded, no source maps) → `assets/styles/admin/*.css`;
  then `assets/styles/admin/*.css` (non-min, sorted) → concat →
  `assets/styles/kpir-admin.css`; then every non-min CSS in
  `assets/styles/` (including hand-written `kpir-frontend.css`) →
  clean-css → `*.min.css`.
- `bun run dev` watches `assets/scripts/admin/src/*.js` (rebuilds JS) and
  `assets/styles/src/**/*.scss` (rebuilds CSS), 500 ms debounce.

## Modes (`bun run ...`)

| Command                | Description                                                                 |
| ---------------------- | --------------------------------------------------------------------------- |
| `bun run dev`          | Watcher: rebuild assets on JS/SCSS change; no ZIP, no i18n                   |
| `bun run js`           | JS pipeline only (prettier + concat + terser)                                |
| `bun run css`          | CSS pipeline only (sass + concat + clean-css)                                |
| `bun run build`        | Both flavors: **github first**, then wporg (with Plugin Check)               |
| `bun run build:wporg`  | Production → `release/wporg/{name}.zip` + Plugin Check                        |
| `bun run build:github` | Production + `.mo` → `release/github/{name}.zip`                              |
| `bun run make-pot`     | Translations only (`.pot` + `.po` update)                                    |
| `bun run pcp`          | Plugin Check only — reuses the existing wporg ZIP, builds it first if missing |
| `bun run test`         | PHPUnit (`tests/php/phpunit.xml`; `vendor/bin/phpunit` if present)            |

## Build pipeline (`bun run build:wporg`, 7 steps)

1. **clean** — remove `languages/*.mo`;
2. **prettier** — format sources in place (`assets/scripts/admin/src/**/*.js`,
   `bundler/**/*.{js,mjs}`, root `*.js`) — the build rewrites sources by design;
3. **assets** — JS + CSS pipelines (see above);
4. **i18n** — wp-cli `i18n make-pot` → `languages/{name}.pot`, then
   `i18n update-po` per existing `.po`; `make-mo` only in `build:github`;
   a timestamp-only `.pot` diff is detected and reverted;
5. **zip** — staging copy → placeholder replacement →
   `release/{flavor}/{version}/{name}/` → `release/{flavor}/{name}.zip`;
6. **plugin-check** (wporg only) — copies the staged release into the local
   WordPress plugins dir under the real slug and runs `wp plugin check
   --categories=plugin_repo --format=csv` (PCP) against it; prints detailed
   findings + summary tables (by file, by check code); writes the raw CSV to
   `plugin-check.log` (gitignored); the build **fails on any ERROR**. The
   vendored iWorks submodule (`includes/iworks/options`) is excluded — it
   lives in an upstream repo;
7. **done** — progress bar + total time; any failure prints `BUILD FAILED`,
   exit 1.

`bun run build` runs **github first**, then wporg, so the final
PCP/symlink state reflects the wporg release.
Env vars: `PLUGIN_CHECK_SKIP=1` (skip), `PLUGIN_CHECK_PLUGINS_DIR`
(plugins dir, defaults to the repo's parent), `PLUGIN_SYMLINK_SKIP=1`
(no release symlink).

## Plugin Check (PCP) details

- Needs the Plugin Check plugin installed in the local WordPress install
  and `wp-cli.phar` — otherwise skipped with a warning.
- The CSV output uses `FILE: <path>` header lines followed by data rows;
  `pluginCheck.mjs` `parseCsv()` tracks the current file, handles quoted
  fields, and normalizes paths (strips repeated `kpir/` staging prefixes
  caused by the release symlink).
- Findings are de-duplicated (symlinked copies of the same file report the
  same finding multiple times) keyed on `line:column:code:message`.
- Known state (~280 errors): dominated by
  `WordPress.Security.EscapeOutput.OutputNotEscaped` (mostly
  `reports/monthly.php`, `reports/annually.php`,
  `posttypes/invoice.php`, JPK template parts),
  `WordPress.DateTime.RestrictedFunctions.date_date` (`date()` vs
  `gmdate()`), `WordPress.WP.I18n.MissingTranslatorsComment`.

## Placeholders (release copies only)

`AUTHOR_NAME`, `AUTHOR_URI`, `BUILDTIME`, `IWORKS_RATE_TEXTDOMAIN`,
`IWORKS_OPTIONS_TEXTDOMAIN`, `PLUGIN_DESCRIPTION`,
`PLUGIN_GITHUB_WEBSITE`, `PLUGIN_NAME`, `PLUGIN_REQUIRES_PHP`,
`PLUGIN_REQUIRES_WORDPRESS`, `PLUGIN_TESTED_WORDPRESS`, `PLUGIN_TAGLINE`,
`PLUGIN_TAGS`, `PLUGIN_TILL_YEAR`, `PLUGIN_TITLE`, `PLUGIN_URI`,
`PLUGIN_VERSION`, and the `^Version: .*$` header line — sourced from
`package.json` + build time.

The version lives **only** in `package.json`.

## Release ZIPs — both flavors must build

- **WordPress.org** (`bun run build:wporg`) → `release/wporg/{version}/{name}/`.
  Base excludes **plus**: asset sources (`assets/styles/src/**`,
  `assets/scripts/admin/src/**`, legacy `**/img/src`, `**/js/src`,
  `**/css/{less,sass,src}`), the GitHub self-updater class
  (`includes/iworks/kpir/class-iworks-kpir-github.php`),
  `languages/*.po|*.mo`. `readme.txt` **is** included (required by wp.org).
- **GitHub** (`bun run build:github`) → `release/github/{version}/{name}/`.
  Base excludes plus `readme.txt`; `.po` **are** included and `.mo` compiled
  by `make-mo`. Pairs with the GitHub self-updater class.
- Base excludes (both flavors): `.git*`, `.github/**`, `.editorconfig`,
  `.env*`, `.opencode/**`, `AGENTS.md`, `bundler/**`, `Gruntfile.js`,
  lock files, `wp-cli.phar`, `node_modules`, `package.json`,
  `release/`, `.sass-cache`, `vendor/**`, `composer.*`, `**/tests/**`,
  `LICENSE`, `**/*.map`, `README.md`, `contributing.md`,
  `**/bitbucket-pipelines.yml`.
- Copy rules are ordered and evaluated **first match wins** (grunt
  semantics) — the catch-all `**/*` include must stay last
  (`config.mjs` `copyRules()`).

## Tests

- `bun run test` → PHPUnit 12 (`vendor/bin/phpunit` when composer deps are
  installed, otherwise bare `phpunit`), config `tests/php/phpunit.xml`,
  bootstrap `tests/php/bootstrap.php` with `tests/php/wp-stubs.php` —
  **no WordPress install or database needed**; current unit tests cover
  JPK and KSeF classes.

## Runtime quirks / pitfalls (do not fix without approval)

- The build's prettier step **rewrites source files** — that is intended.
  A diff after `bun run build` containing only formatting in JS sources is
  expected; anything else is not.
- The i18n step **updates the tracked `.po` files in place** — a build diff
  touching `languages/*.po` (timestamps/headers) is normal.
- `bun run make-pot` needs network on the first run (`wp-cli.phar`
  download); `postinstall` retries it on every `bun install`. Bun blocks
  untrusted postinstall scripts — `bun pm trust --all` after install.
- Copy rules: negations must precede the catch-all `**/*` include —
  reordering breaks the ZIPs.
- The wporg build runs Plugin Check and **fails the build** on ERROR
  findings; the codebase currently has known errors (see above) — either
  fix them or use `PLUGIN_CHECK_SKIP=1`.
