/**
 * Asset pipelines — grunt-parity JS and CSS builds.
 *
 * JS:   assets/scripts/admin/src/*.js → concat → kpir.js → terser → kpir.min.js
 * CSS:  assets/styles/src/admin/*.scss → sass → assets/styles/admin/*.css
 *       → concat → kpir-admin.css; every non-min CSS → *.min.css
 */

import { join, relative } from "node:path";
import { readdirSync, existsSync } from "node:fs";
import { log } from "./consoleHelpers.mjs";
import { config } from "./config.mjs";
import {
  concatFiles,
  sassCompile,
  terserMinify,
  cleanCssMinify,
  writeFileAtomic,
  prettierSources,
  cleanGlob,
} from "./tools.mjs";

const root = new URL("..", import.meta.url).pathname;

/**
 * Version banner used by every built asset (grunt concat/uglify/cssmin parity).
 */
function banner() {
  return (
    `/*! ${config.title} - v${config.version}\n` +
    ` * ${config.homepage}\n` +
    ` * Copyright (c) ${config.buildYear}\n` +
    ` * Licensed ${config.license}\n` +
    ` */`
  );
}

/**
 * JS sources to concatenate, in grunt-configured order.
 */
const JS_SOURCES = [
  "assets/scripts/admin/src/datepicker.js",
  "assets/scripts/admin/src/invoice.js",
  "assets/scripts/admin/src/jpk.js",
  "assets/scripts/admin/src/select2.js",
];

/**
 * SCSS files to compile: target CSS (relative to assets/styles) → source SCSS.
 */
const SCSS_FILES = [
  {
    css: "assets/styles/admin/post-type-invoice.css",
    scss: "assets/styles/src/admin/post-type-invoice.scss",
  },
  {
    css: "assets/styles/admin/wordpress-admin-dashboard.css",
    scss: "assets/styles/src/admin/wordpress-admin-dashboard.scss",
  },
  {
    css: "assets/styles/admin/reports.css",
    scss: "assets/styles/src/admin/reports.scss",
  },
];

/**
 * JS pipeline: prettier sources → concat → minify.
 */
export async function buildJs() {
  await prettierSources(["assets/scripts/admin/src/**/*.js"]);
  const concatTarget = join(root, "assets/scripts/admin/kpir.js");
  const content = await concatFiles(
    JS_SOURCES.map((f) => join(root, f)),
    banner(),
  );
  writeFileAtomic(concatTarget, content);
  log(`js — ${relative(root, concatTarget)}`, "i");

  const minTarget = join(root, "assets/scripts/admin/kpir.min.js");
  const minified = await terserMinify(concatTarget, banner());
  writeFileAtomic(minTarget, minified);
  log(`js — ${relative(root, minTarget)}`, "s");
}

/**
 * CSS pipeline: sass compile → concat admin CSS → minify all non-min CSS.
 */
export async function buildCss() {
  // sass compile (grunt sass task).
  for (const { css, scss } of SCSS_FILES) {
    const compiled = await sassCompile(join(root, scss));
    writeFileAtomic(join(root, css), compiled);
    log(`sass — ${relative(root, join(root, css))}`, "i");
  }

  // concat admin CSS into the single admin stylesheet (grunt concat_css).
  const adminDir = join(root, "assets/styles/admin");
  const adminCssFiles = existsSync(adminDir)
    ? readdirSync(adminDir)
        .filter((f) => f.endsWith(".css") && !f.endsWith(".min.css"))
        .sort()
        .map((f) => join(adminDir, f))
    : [];
  if (adminCssFiles.length) {
    const concatTarget = join(root, `assets/styles/${config.name}-admin.css`);
    const content = await concatFiles(adminCssFiles, banner());
    writeFileAtomic(concatTarget, content);
    log(`css — ${relative(root, concatTarget)}`, "i");
  }

  // minify every non-min CSS file in assets/styles (grunt cssmin).
  const stylesDir = join(root, "assets/styles");
  const cssFiles = existsSync(stylesDir)
    ? readdirSync(stylesDir).filter(
        (f) => f.endsWith(".css") && !f.endsWith(".min.css"),
      )
    : [];
  for (const file of cssFiles) {
    const source = join(stylesDir, file);
    const minified = await cleanCssMinify(
      source,
      await Bun.file(source).text(),
      banner(),
    );
    writeFileAtomic(`${source.slice(0, -4)}.min.css`, minified);
    log(`css — ${relative(root, `${source.slice(0, -4)}.min.css`)}`, "s");
  }
}

/**
 * Full asset build (grunt default task parity).
 */
export async function buildAssets() {
  await buildJs();
  await buildCss();
}

/**
 * Prettier pass over the JS/JSON sources (build pipelines run it first).
 */
export async function formatSources() {
  await prettierSources([
    "assets/scripts/admin/src/**/*.js",
    "bundler/**/*.js",
    "bundler/**/*.mjs",
    "*.js",
  ]);
  log("prettier — sources formatted", "s");
}

/**
 * Remove built files (i18n only).
 */
export async function cleanCompiled() {
  await cleanGlob("languages/*.mo");
  log("clean — languages/*.mo removed", "i");
}
