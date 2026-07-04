# Changelog

All notable changes to Zihad Travel CMS. The format follows
[Keep a Changelog](https://keepachangelog.com/); the project is pre-release,
so all work is under **Unreleased** (targeting 1.0.0) in chronological order.

## [Unreleased]

### Foundation — 2026-07-04 (`ad36f06`)

- Plugin kernel: PSR-4 autoloading (Composer + fallback), reflection
  auto-wiring DI container, two-phase service providers, module system with
  `ztc_modules` filter, requirements guard (PHP 8.2+ / WP 6.4+).
- Lifecycle: injectable activator/deactivator, version-keyed upgrade
  migrations with deferred rewrite flush, opt-in multisite-aware uninstall.
- Content: Country, Visa and Tour post types (`/country/`, `/visa/`,
  `/tour/`), Visa Type / Tour Type / Region taxonomies, 55 registered meta
  fields (typed, sanitized, REST-exposed), meta keys as class constants.
- Data layer: repositories (translation-provider-aware queries), services
  with `card_data()`/`page_data()` view-models, shared Media/Notification/
  Health services, `GlobalSettings`.
- Admin UI framework: WAI-ARIA tabbed editor with guarded save pipeline and
  reusable field components (text, rich editor, media, gallery, repeater,
  FAQ/timeline builders, taxonomy, SEO group, …); Visa editor (9 tabs) and
  Country editor (8 tabs); Plugin Health page; admin menu.
- Frontend engine: MVC template loader with theme-overridable archive/single
  templates, shared `GridRenderer` card path, AJAX search module
  (`GET ztc/v1/search`, public + cacheable) with progressive-enhancement JS,
  shortcodes (`ztc_tours`, `ztc_visas`, `ztc_countries`, `ztc_search`,
  `ztc_cta`), FAQPage JSON-LD, mobile-first CSS.
- Elementor: widget category, grid + CTA widgets, Field and Hero Image
  dynamic tags, CPT editing support.
- I18n: text domain, `wpml-config.xml`, `TranslationProvider` contract with
  null-object default.

### Module 1: Tour Editor — 2026-07-04 (`a9e3b42`)

- Nine-tab declarative Tour editor (General, Hero, Gallery, Itinerary,
  Inclusions, Hotels, Travel Info, FAQ, SEO).
- New reusable `DurationField` (days/nights object).
- `Editor::after_save()` cross-field validation hook; invalid sale price
  (≥ regular) is cleared with an admin warning.
- Tour hero image meta (`ztc_hero_image`) with hero → featured → gallery
  fallback; Tour drops the default Custom Fields metabox; Tour Type classic
  metabox hidden (no duplicated UI).
- Fixed: `supports` overrides now replace wholesale instead of merging by
  index.
- Docs: `docs/` created (README, architecture, tour-editor).

### Module 2: Import/Export Engine — 2026-07-04 (`f7ef0d3`)

- Generic `ImportMapping` contract + `ztc_import_mappings` filter; built-in
  Country/Visa/Tour mappings.
- Field targets: post fields, meta, pipe/JSON string lists, structured JSON,
  auto-created terms, relations (country lookup), image/gallery/thumbnail
  URL sideloading with `_ztc_source_url` dedup.
- Persisted jobs: batching, live progress, resumable interrupted imports,
  capped error log, slug-based duplicate detection with
  create/update/upsert modes, manual + all-or-nothing rollback.
- Exports reverse the same mappings (CSV with BOM + pipe lists,
  unescaped-unicode JSON) and round-trip through the importer.
- REST endpoints under `ztc/v1/import|export` (media-ID file references
  only); WP-CLI `wp ztc import|export|import-status|import-rollback`;
  Import/Export admin page with media picker, progress bar and error log.
- Fixed: `post:slug` now maps to `post_name` (explicit slugs were ignored).

### Module 3: Demo Data Generator + Installer — 2026-07-04 (`95ddd61`)

- Data-driven sources: 105 seed countries (English + Bangla names, ISO,
  capitals, currencies, regions, cities) and a fully localized (en/bn)
  template bank with token interpolation — no demo content in PHP.
- Deterministic generator: 105 countries / 473 visas / 132 tours in importer
  JSON format; byte-identical regeneration; stable latin slugs shared across
  locales; `bn` locale produces a full Bangla content set.
- Placeholder images by URL (seeded picsum + real flagcdn flags), swappable
  via data.
- Installer is a thin wrapper over the import engine (upsert) — re-installs
  update instead of duplicating; REST + `wp ztc demo generate|install` +
  Demo Data card with progress bar; generated files ship committed.

### Module 4: Global Dashboard & Settings — 2026-07-04 (`89cc196`)

- Settings schema: homepage, WhatsApp, integrations (Maps/GA/Pixel), booking,
  performance and custom-code sections; `SettingsSanitizer` enforces the
  schema on every write path.
- 11-tab Settings screen built from the shared Tab + field components
  (General, Homepage, Branding, Contact, Social Media, WhatsApp, Maps,
  Analytics, Booking, Performance, Custom CSS/JS); batched option writes via
  `admin-post` with nonce/capability guards; `ztc_settings_tabs` filter; new
  reusable `CodeField`.
- REST `GET/POST ztc/v1/settings`; Elementor "Travel CMS Setting" dynamic
  tag.
- Frontend integrations: brand-color CSS variables, custom CSS/JS output
  with injection guards, Google Analytics + Facebook Pixel snippets,
  floating WhatsApp button (theme-overridable part).
- Performance settings consumed by existing services: Bootstrap asset
  toggle, setting-driven search `Cache-Control` TTL.
- Dashboard: stat cards (Countries/Visas/Tours), demo-data status widget,
  recent-imports table, quick actions.

### Documentation — 2026-07-04

- Root documentation set: README, PROJECT_STATUS, ROADMAP, ARCHITECTURE,
  CHANGELOG, CONTRIBUTING.
