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

### Module 5: SEO — 2026-07-04

- `Modules\Seo`: document title, meta description/keywords, canonical
  URLs (per-post override, archives, `/page/N/` pagination), robots
  directives (per-post + `noindex_archives`), OpenGraph and Twitter
  Card tags on all plugin routes — singles, archives and taxonomy
  archives.
- Schema.org JSON-LD through one reusable `SchemaService` (typed
  `node()` builder, recursive empty-property cleaning): TouristTrip
  (itinerary ItemList + numeric-price Offer), GovernmentService
  (serviceType, areaServed, fee), Country (Bangla `alternateName`),
  CollectionPage and BreadcrumbList; FAQPage intentionally stays with
  the frontend FAQ part next to the visible questions.
- Auto-deferral: the output pipeline is inert while Yoast SEO or
  Rank Math is active (`ztc_seo_defer` filter to override); the module
  itself keeps loading so the settings schema is never dropped by the
  structural sanitizer.
- `ztc_seo` meta extended with `robots` and `canonical` (registered
  REST schema, `SeoField` UI with a whitelisted robots select and URL
  canonical input); `BasePostMeta` object fields gained a `url`
  sanitize kind.
- New `seo.*` settings section + 12th settings tab (separator, default
  description/keywords/social image, Twitter handle, OG/Twitter/schema
  toggles, archive indexing, per-type archive titles/descriptions) —
  registered via `ztc_default_settings`/`ztc_settings_tabs`, so every
  existing write path (form, REST) accepts it unchanged; new keys
  registered as WPML admin-texts.
- Developer filters for every generated value: `ztc_seo_title`,
  `ztc_seo_description`, `ztc_seo_canonical`, `ztc_seo_robots`,
  `ztc_seo_opengraph`, `ztc_seo_twitter`, `ztc_seo_schema`,
  `ztc_seo_head`.
- Performance: SEO data resolves once per request (memoized in
  `SeoService`, filters included).
- Tests: new `seo-smoke` suite (12 suites total, all green); visa- and
  country-editor suites updated for the extended `ztc_seo` shape.
- Docs: `docs/seo.md`.

### Module 6: Setup Wizard — 2026-07-04

- `Modules\Wizard`: an eleven-step first-run installer (Welcome,
  Company, Branding, Contact, Social, WhatsApp, Maps, Analytics/Pixel,
  Homepage, optional Demo Data, Finish) that only orchestrates existing
  functionality — shared field components pre-filled from Config,
  saves through the settings pipeline (field sanitize → structural
  SettingsSanitizer → one batched write), demo install through
  DemoDataInstaller/ImportService, finish summary from
  DashboardData + HealthService (incl. the permalink check).
- Resumable (progress in `ztc_wizard_state`; completed steps
  revisitable), skippable (per step and entirely), rerunnable from
  Travel CMS → Setup, restartable without touching settings; each step
  saves independently and every field shows its saved value before
  anything is written.
- Progressive enhancement: plain forms/redirects throughout; the demo
  step installs without JavaScript via bounded batch slices per
  submission, and reuses the existing admin.js REST progress loop when
  JavaScript is available.
- First-run flow: one-shot redirect after activation (via the existing
  `ztc_activated` action — Activator untouched) plus a dismissible
  "finish setting up" notice on plugin screens.
- REST under `ztc/v1`: `GET /wizard`, `POST /wizard/step`,
  `/wizard/skip`, `/wizard/complete`, `/wizard/reset` (all
  `manage_options`); demo installs reuse `/demo/start` +
  `/import/process`.
- Extension points: `ztc_wizard_steps` filter;
  `ztc_wizard_step_saved`, `ztc_wizard_step_completed`,
  `ztc_wizard_completed`, `ztc_wizard_reset` actions.
- Tests: new `wizard-smoke` suite (13 suites total, all green; includes
  a full no-JS demo install through the real import engine);
  registration suite updated for the new module id.
- Docs: `docs/setup-wizard.md`; dashboard gained a Setup Wizard quick
  action.

### Module 7: Homepage Search Widget — 2026-07-05

- Tabbed Visa/Tour search widget — Visas: keyword, Country, Visa Type;
  Tours: keyword, Country, Tour Type, Duration, Budget — with **one**
  render path (`Views\SearchWidgetRenderer` + theme-overridable
  `search-widget.php` part) shared verbatim by the
  `[ztc_search_widget]` shortcode, the new "Travel Search" Elementor
  widget and the settings-gated homepage auto-injection
  (`homepage.show_search`; Elementor-built front pages excluded;
  `ztc_show_homepage_search` veto filter).
- Progressive enhancement twice over: CSS-only radio tabs (no
  JavaScript at all), and forms that submit to the type archives where
  the new `ArchiveFilters` component applies the same clauses to the
  main query via `pre_get_posts` — full no-JS parity plus shareable,
  cacheable filtered URLs. With JavaScript the existing frontend.js
  drives the forms through `GET ztc/v1/search` unchanged.
- Performance: new `ztc_duration_days` numeric mirror
  (`TourDurationSync` keeps it in sync on every meta write path) so
  duration filters run as plain NUMERIC meta queries; country select
  options cached in a transient (flushed on country save/delete);
  single-select `duration`/`budget` "min-max" range params validated on
  the REST route and parsed by one shared
  `SearchService::filter_clauses()` used by REST and archives alike.
- Fixed: archive queries no longer inherit a junk empty-string clause
  when merging into an unset `tax_query`/`meta_query`.
- Tests: new `search-widget-smoke` suite (14 suites total, all green);
  registration/frontend suites updated for the new meta key and
  shortcode.
- Docs: `docs/search-widget.md`.

### Module 8: Booking / Inquiry Forms — 2026-07-05

- `Modules\Booking`: visa and tour inquiry forms feeding a **private**
  `ztc_inquiry` post type (no frontend, no REST exposure, REST-hidden
  meta — visitor data never leaves wp-admin; "Add New" disabled) with
  contact/type/subject/status admin list columns.
- One submission pipeline (`InquiryService::submit()`) shared by both
  entry points: sanitize every field → honeypot → per-IP rate limit
  (5/10 min, `ztc_inquiry_rate_limit`) → server-side validation
  (required name, valid email, 5000-char message cap, type whitelist,
  Booking toggles, related-post/type match, `ztc_inquiry_validate`) →
  persist → notify → `ztc_inquiry_created`.
- No-JS baseline: nonce-protected `admin-post.php` handler (logged-in +
  logged-out) redirecting back with a result flag and a stable form
  anchor; frontend.js only enhances (inline REST submit with native
  fallback on network failure).
- `POST ztc/v1/inquiry`: public by design, every arg typed, validated,
  sanitized and described (email format, type enum, honeypot arg);
  200/400 (field errors)/429 (rate limited) responses.
- New `Contracts\Mailer` + `Services\WpMailer` — the plugin never calls
  `wp_mail()` directly; providers swap in via the `ztc_mailer` filter.
  Notification recipient falls back Booking email → company email →
  site admin; subject/body/headers (Reply-To visitor) all filterable.
- One render path: `Views\InquiryFormRenderer` + theme-overridable
  `inquiry-form.php` part shared by the single visa/tour templates
  (injected through the `ztc_template_view` seam — zero service
  changes), the `[ztc_inquiry_form]` shortcode and the new "Inquiry
  Form" Elementor widget; all surfaces hide when the type's inquiries
  are disabled in settings.
- Tests: new `booking-smoke` suite (15 suites total, all green);
  frontend suite updated for the new shortcode.
- Docs: `docs/booking.md`; `ztc_inquiry` declared non-translatable in
  `wpml-config.xml`.
