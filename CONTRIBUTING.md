# Contributing

Zihad Travel CMS is a commercial-grade codebase. Every contribution — human
or AI-assisted — follows the rules below. Read
[ARCHITECTURE.md](ARCHITECTURE.md) before writing code.

## Coding Standards

- **PHP 8.2+**, `declare(strict_types=1)` in every file (except the
  bootstrap, which stays parseable on old PHP for its version guard).
- **WordPress Coding Standards** — ruleset in `phpcs.xml.dist`
  (`composer lint` / `composer lint:fix`), with PSR-4 file naming
  (`Plugin.php`, not `class-plugin.php`).
- **PSR-4**: `ZihadTravelCMS\` → `includes/`. One class per file; namespaces
  mirror folders.
- **Prefixes**: options/meta/hooks `ztc_`, constants `ZTC_`, CSS `.ztc-`,
  script/style handles `ztc-`. Text domain: `zihad-travel-cms`.
- Meta keys are referenced **only** via `*Meta` class constants — never raw
  strings.
- **JavaScript**: native/dependency-free, event delegation, progressive
  enhancement (every feature must degrade without JS). **CSS**: mobile-first,
  `.ztc-` prefixed blocks.
- Every user-facing string is translatable; Bangla is a first-class locale
  (UTF-8 end to end; update `wpml-config.xml` when adding fields).
- **Security**: nonce + capability check on every write; sanitize per field
  on input, escape on output; REST file access by media ID only; public REST
  endpoints are read-only and nonce-free (cacheable); no raw SQL outside
  `uninstall.php`.

## Architecture Rules

1. **Never rewrite existing modules or the core architecture.** Features
   *extend*: add a module, a provider, a field component, or hook a filter
   (`ztc_modules`, `ztc_rest_controllers`, `ztc_admin_pages`,
   `ztc_import_mappings`, `ztc_settings_tabs`, `{post_type}_editor_tabs`, …).
2. **Reuse before you build**: services for logic, repositories for data
   access, `GridRenderer`/cards for anything that lists content, the field
   components for any admin form, the importer for anything that ingests
   data (the demo installer is the reference — zero import logic of its own),
   `GlobalSettings`/`Config` for any setting read.
3. Layer boundaries are hard: repositories touch WP data APIs; services hold
   business logic; templates are pure views (`ztc_view()` / `ztc_part()`,
   theme-overridable — no queries, no HTML in PHP classes beyond escaped
   component markup).
4. Classes are constructor-injected via the container. Statics only for pure
   helpers (`Arr`, `Str`) and WordPress hook bridges.
5. No content or demo data hardcoded in PHP — data lives in data files
   (`demo-data/sources/`).
6. New modules must be independently testable and disabled-safe
   (`is_available()` gates on dependencies — see `ElementorModule`).

## Testing Requirements

- **Every module ships with an automated test suite** before it is
  considered complete. Suites live in [tests/](tests/) — standalone PHP
  scripts using WordPress-function stubs (no WP install required):

  ```bash
  tests/run.sh              # every suite
  php -d zend.assertions=1 -d assert.exception=1 tests/<suite>.php
  ```

- A suite must cover: happy path (render/round-trip), sanitization of
  hostile input, security guards (forged nonce writes nothing), and the
  module's key invariants (e.g. importer resume/rollback, generator
  determinism).
- **All existing suites must pass before every commit** — currently 15:
  boot, registration, services, editor framework, visa/country/tour editors,
  frontend engine, importer, demo data, settings/dashboard, SEO, setup
  wizard, search widget, booking/inquiries.
- Static checks: `composer lint` (PHPCS/WPCS), `composer stan` (PHPStan),
  `node --check` (JS). CI runs all of it on every push/PR
  ([.github/workflows/ci.yml](.github/workflows/ci.yml)).
- If you change `demo-data/sources/`, regenerate the committed output files
  and re-run the demo-data suite (determinism is asserted).

## Commit Conventions

- **One logical commit per module/feature** — never mix modules. Docs and
  tests for a module belong in its commit.
- Subject: `Module N: <name>` for roadmap modules; otherwise a concise
  imperative summary (`Fix: …`, `Docs: …`).
- Body: bullet list of what changed and why, including any bug found/fixed
  along the way and the test coverage added.
- Before committing, the checklist is: plan reviewed → code → docs in
  `/docs` → tests written → **all** suites green → lint clean → commit.

## Documentation Requirements

Every feature is documented in `/docs` (one file per module, indexed in
`docs/README.md`) **in the same commit**. Update `CHANGELOG.md`,
`PROJECT_STATUS.md` and, when scope shifts, `ROADMAP.md`.
