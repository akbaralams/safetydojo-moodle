# Project Structure

This is a Moodle core checkout, organized by Moodle's standard plugin/component conventions. Most of the tree is upstream Moodle code; site-specific work is concentrated in a few locations (see below).

## Top-Level Layout
- `public/` — the actual web root. Almost all Moodle components live here now (Moodle 5.x moved most of core under `public/`): `public/mod/` (activity modules), `public/blocks/`, `public/theme/`, `public/local/` (local plugins), `public/admin/`, `public/course/`, `public/lib/` (frontend/output helpers), etc.
- `lib/` — a slimmer top-level lib dir containing setup (`lib/setup.php`), Behat extension code (`lib/behat/`), and bundled JS (`lib/js/bundles/` — React, react-dom, design-system, generated, do not hand-edit).
- `admin/cli/` — command-line maintenance scripts (cron, backups, upgrades, user/session management).
- `theme/` — installed themes outside of core. Only `theme/boost_union/` present — the active custom theme (child of Boost).
- `.esbuild/`, `.grunt/` — build tooling config/scripts for JS/CSS bundling.
- `.upgradenotes/` — auto-generated upgrade note YAML files (via `npm run upgradenote`).
- `.kiro/steering/` — Kiro steering docs (this file and siblings).
- `config.php` / `config-dist.php` — site configuration; `config.php` contains live DB credentials, never expose its contents.
- `composer.json`, `package.json`, `Gruntfile.js`, `phpcs.xml.dist`, `phpunit.xml.dist`, `tsconfig.json` — root build/test/lint configuration.

## Custom / Site-Specific Code
- `public/local/dashboard/` — small custom local plugin (dashboard landing page): `index.php`, `lib.php`, `settings.php`, `access.php`, `version.php`, `lang/`.
- `public/local/kopere_dashboard/` — third-party admin dashboard plugin with its own `classes/`, `amd/`, `page/`, `plugins/`, `scss/`, `templates/`.
- `public/local/kopere_bi/` — third-party BI/reporting plugin with `biblocks/`, `bifilters/`, `classes/`, `assets/`, `templates/`.
- `theme/boost_union/` — active theme; customization entry points are `settings.php`, `lib.php`, `renderers.php`, `layout/`, `scss/`, `templates/`, `flavours/` (per-flavour overrides), `smartmenus/`.

## Standard Moodle Plugin Anatomy (applies to any component you touch)
Each plugin/component (mod, block, local, theme, etc.) typically follows this internal structure:
- `version.php` — plugin version/dependency declaration.
- `lib.php` — main library/callback functions.
- `db/` — `install.xml` (schema), `upgrade.php`, `access.php` (capabilities), `events.php`, `services.php`, `tasks.php`.
- `classes/` — autoloaded PHP classes (namespaced `component_name\...`), including `classes/task/`, `classes/privacy/provider.php`, `classes/external/`.
- `lang/en/` — English language strings (`component_name.php`).
- `templates/` — Mustache templates for rendering.
- `amd/src/` — ES module JS sources; compiled output goes to `amd/build/*.min.js` (generated, do not edit by hand).
- `scss/` or `styles.css` — styling.
- `tests/` — PHPUnit tests (`*_test.php`) and `tests/behat/*.feature` acceptance tests.

## Working Conventions
- Prefer making changes inside `public/local/*` or `theme/boost_union/*` for site-specific behavior; avoid editing core Moodle components under `public/mod`, `public/admin`, `public/course`, etc. directly — use plugin hooks, overrides, or callbacks instead.
- Never hand-edit generated/build output: `amd/build/*.min.js`, `lib/js/bundles/*`, `vendor/`, `node_modules/`.
- New language strings go in the relevant plugin's `lang/en/<component>.php`, not inline in PHP/templates.
- Database schema changes for a plugin require both `db/install.xml` updates and a corresponding `db/upgrade.php` step plus a version bump in `version.php`.
