#!/usr/bin/env bun
/**
 * Bundler entry point — replaces the old Grunt pipeline.
 *
 * Modes:
 *   dev (default) — watcher, rebuilds assets on JS/SCSS change
 *   build         — both flavors: build:github + build:wporg (+ Plugin Check)
 *   build:wporg   — production build → release/wporg/{name}.zip
 *   build:github  — production build + .mo → release/github/{name}.zip
 *   make-pot      — translations only (.pot + .po update)
 *   test          — PHPUnit (tests/php/phpunit.xml)
 *   pcp           — Plugin Check on the wporg build (builds it first if missing)
 */

import { dirname, join, resolve } from "node:path";
import { existsSync, watch } from "node:fs";
import { executionTime, log } from "./consoleHelpers.mjs";
import { ProgressBar } from "./progressBar.mjs";
import { spawnAsync } from "./spawnAsync.mjs";
import { config } from "./config.mjs";
import { buildAssets, formatSources, cleanCompiled } from "./pipelines.mjs";
import { makeZip } from "./makeZip.mjs";
import { makePot } from "./makePot.mjs";
import { pluginCheck } from "./pluginCheck.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");

/**
 * Friendly error printer — one-liners, no stack traces.
 *
 * @param {*} error Thrown error.
 */
function printError(error) {
  const message = error?.friendly
    ? error.message
    : error?.stack || String(error);
  log(message, "e");
}

/**
 * Production build for one flavor.
 *
 * @param {string} flavor "wporg" or "github".
 */
async function build(flavor) {
  const start = Date.now();
  try {
    const steps = flavor === "wporg" ? 7 : 6;
    const bar = new ProgressBar(steps);
    bar.tick("clean");
    await cleanCompiled();
    bar.tick("prettier");
    await formatSources();
    bar.tick("assets");
    await buildAssets();
    bar.tick("i18n");
    await makePot(flavor === "github");
    bar.tick("zip");
    await makeZip(flavor);
    if (flavor === "wporg") {
      bar.tick("plugin-check");
      await pluginCheck(config.name, config.version, {
        // The plugin name "KPiR" is intentionally short (4 letters) — it is
        // the product brand. PCP requires ≥5 latin letters in the name to
        // derive the initial slug, which does not apply here.
        ignoreCodes: [
          "plugin_header_unsupported_plugin_name",
          // All plugin globals and template-scope variables are already
          // prefixed with "kpir_" (e.g. $kpir_args, $kpir_base), but PCP's
          // prefix scanner cannot derive "kpir" from the 4-letter plugin
          // name, so it reports every assignment as non-prefixed.
          "WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound",
        ],
      });
    }
    bar.tick("done");
    log(
      `BUILD OK (${flavor}) in ${((Date.now() - start) / 1000).toFixed(2)}s`,
      "s",
    );
  } catch (error) {
    log("BUILD FAILED", "e");
    printError(error);
    process.exit(1);
  }
}

/**
 * Dev watcher — rebuild assets on JS/SCSS change (grunt watch parity).
 */
function dev() {
  const exitAfterMs = Bun.env.DEV_EXIT_AFTER_MS || "";
  if (exitAfterMs) {
    setTimeout(() => {
      log(`dev — exiting after ${exitAfterMs} ms (DEV_EXIT_AFTER_MS)`, "i");
      process.exit(0);
    }, Number(exitAfterMs));
  }
  log(
    "dev — watching for changes (no auto browser reload, refresh manually)",
    "i",
  );

  let timer = null;
  let running = false;
  const queue = new Set();

  const JS_DIRS = ["assets/scripts/admin/src/"];
  const SCSS_DIR = "assets/styles/src/";

  const relevant = (rel) =>
    JS_DIRS.some((dir) => rel.startsWith(dir) && rel.endsWith(".js")) ||
    (rel.startsWith(SCSS_DIR) && rel.endsWith(".scss"));

  const processQueue = async () => {
    timer = null;
    if (running) {
      return;
    }
    running = true;
    const changed = [...queue].filter(relevant);
    queue.clear();
    try {
      const isJs = changed.some(
        (f) => f.startsWith("assets/scripts/admin/src/") && f.endsWith(".js"),
      );
      const isCss = changed.some(
        (f) => f.startsWith("assets/styles/src/") && f.endsWith(".scss"),
      );
      if (isJs) {
        await buildAssets();
      } else if (isCss) {
        const { buildCss } = await import("./pipelines.mjs");
        await buildCss();
      }
      log(`dev — rebuilt assets (${changed.length} changed)`, "s");
    } catch (error) {
      printError(error);
    } finally {
      running = false;
    }
  };

  watch(root, { recursive: true }, (event, filename) => {
    if (!filename) {
      return;
    }
    const rel = filename.replace(/\\/g, "/");
    if (!relevant(rel)) {
      return;
    }
    queue.add(rel);
    if (!timer) {
      timer = setTimeout(processQueue, 500);
    }
  });
}

/**
 * PHPUnit (grunt `test` parity, jshint dropped).
 */
async function test() {
  const bin = existsSync(join(root, "vendor/bin/phpunit"))
    ? join(root, "vendor/bin/phpunit")
    : "phpunit";
  const result = await spawnAsync(
    bin,
    ["--configuration", "tests/php/phpunit.xml", "--colors=always"],
    "PHPUnit",
    false,
  );
  if (result.stdout.trim()) {
    console.log(result.stdout.trim());
  }
  if (result.code !== 0) {
    log(
      "If phpunit is not installed, run `composer install` or set up a WordPress test environment (see tests/php/bootstrap.php).",
      "w",
    );
    process.exit(result.code);
  }
  log("PHPUnit passed", "s");
}

/**
 * Update the iWorks git submodules (grunt never had this; parity with
 * dev-sierotki). Pulls latest changes for each submodule and fails when any
 * of them cannot be updated.
 */
async function updateLibraries() {
  const submodules = [
    { path: "includes/iworks/options", label: "iWorks Options" },
  ];
  let failed = false;
  for (const { path, label } of submodules) {
    if (!existsSync(join(root, path))) {
      log(`update-libs — ${path} not found, skipped`, "w");
      continue;
    }
    log(`update-libs — updating ${label} (${path})`, "i");
    const result = await spawnAsync("git", ["-C", path, "pull"], label, false);
    if (result.stdout.trim()) {
      console.log(result.stdout.trim());
    }
    if (result.stderr.trim()) {
      console.log(result.stderr.trim());
    }
    if (result.code !== 0) {
      failed = true;
    }
  }
  if (failed) {
    log("update-libs — one or more submodules failed to update", "e");
    process.exit(1);
  }
  log("update-libs — all iWorks libraries updated", "s");
}

/**
 * Plugin Check (PCP) on the wporg build — builds the wporg ZIP first when
 * it is missing, then runs the check against the staged release.
 */
async function pcp() {
  const zipPath = join(root, config.releaseDir, "wporg", `${config.name}.zip`);
  if (!existsSync(zipPath)) {
    log("pcp — no wporg ZIP found, building first…", "i");
    await build("wporg");
    return;
  }
  try {
    await pluginCheck(config.name, config.version);
  } catch (error) {
    printError(error);
    process.exit(1);
  }
}

// ─── Mode dispatch ────────────────────────────────────────────────────────────

const mode = process.argv[2] || "dev";

const modes = {
  dev: () => dev(),
  js: () =>
    executionTime("js", async () =>
      (await import("./pipelines.mjs")).buildJs(),
    ),
  css: () =>
    executionTime("css", async () =>
      (await import("./pipelines.mjs")).buildCss(),
    ),
  build: async () => {
    await build("github");
    await build("wporg");
  },
  "build:wporg": () => build("wporg"),
  "build:github": () => build("github"),
  pcp: () => pcp(),
  "make-pot": () => executionTime("make-pot", () => makePot(false)),
  test: () => test(),
  "update-libs": () => updateLibraries(),
};

if (!modes[mode]) {
  log(
    `Unknown mode "${mode}". Available: ${Object.keys(modes).join(", ")}`,
    "e",
  );
  process.exit(1);
}

try {
  await modes[mode]();
} catch (error) {
  printError(error);
  process.exit(1);
}
