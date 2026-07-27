# Tech Stack

## Core Platform
- **Moodle** 5.2.x (branch `502`), PHP-based LMS. Version metadata lives in `public/version.php`.
- **PHP** >= 8.3 (see `composer.json`).
- Database: MySQL/MariaDB (also supports PostgreSQL, MS SQL — see `composer.json` suggest section). Local dev config in `config.php` (contains secrets — do not print/echo its contents).

## Backend Dependencies (Composer)
Managed via `composer.json` / `composer.lock`. Notable libraries: `slim/slim` (routing for some subsystems), `guzzlehttp/guzzle`, `monolog/monolog`, `firebase/php-jwt`, `phpmailer/phpmailer`, `ezyang/htmlpurifier`, `lbuchs/webauthn`, AWS SDK. Treat `vendor/` as generated — do not hand-edit.

## Frontend Stack
- **Node** `>=22.11.0 <23` (see `.nvmrc`: `lts/jod`).
- **React** 19 + **react-dom** 19 for newer components; most legacy UI uses **AMD/RequireJS** modules (`amd/src/*.js` compiled to `amd/build/*.min.js`).
- **@moodlehq/design-system** for shared UI components.
- **Sass/SCSS** for styling (compiled via Grunt).
- Build tooling: **Grunt** (`Gruntfile.js`, tasks in `.grunt/tasks/`) plus **esbuild** (`.esbuild/`) for JS bundling/aliasing.
- Linting: **ESLint** (`.eslintrc`), **stylelint** (`.stylelintrc`), **JSHint** (`.jshintrc`), **gherkin-lint** for Behat feature files (`.gherkin-lintrc`).

## PHP Code Style
- Coding standard enforced via **PHP_CodeSniffer** with the `moodle` ruleset (`phpcs.xml.dist`), targeting PHP 8.3+. Follow Moodle coding style (tabs, docblocks, `moodle_exception`, etc.) for any PHP changes.

## Testing
- **PHPUnit** — config in `phpunit.xml.dist`. Moodle core/plugin unit tests live in each component's `tests/` directory.
- **Behat** — acceptance/UI tests, config in `public/behat.yml.dist`; feature files in `tests/behat/` per component.

## Common Commands

Install dependencies:
```
composer install
npm install
```

Frontend build (via Grunt, run from repo root):
```
npx grunt          # build JS/CSS for all components
npx grunt watch     # watch mode
npx grunt eslint
npx grunt stylelint
```

Update bundled React/design-system packages (runs automatically post-install):
```
npm run update-packages
```

PHP lint / coding standard:
```
vendor/bin/phpcs --standard=phpcs.xml.dist path/to/file.php
```

Run PHPUnit tests (from a configured Moodle install, typically via CLI):
```
vendor/bin/phpunit --config phpunit.xml.dist
php admin/cli/checks.php
```

Run Behat tests:
```
php admin/tool/behat/cli/init.php
vendor/bin/behat --config public/behat.yml.dist
```

Moodle CLI maintenance (from `admin/cli/`):
```
php admin/cli/cron.php
php admin/cli/purge_caches.php
php admin/cli/upgrade.php
```

Note: this is a Windows environment (Laragon). Adapt path separators / use `php` and `composer`/`npm` as available on PATH; prefer running one command at a time rather than chaining with `&&`.
