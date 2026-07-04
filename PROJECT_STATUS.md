# Zihad Travel CMS — Project Status

_Last updated: 2026-07-04 (after Module 5: SEO)._

_See also: [README.md](README.md) · [ARCHITECTURE.md](ARCHITECTURE.md) · [ROADMAP.md](ROADMAP.md) (v1.0 → v2.0) · [CHANGELOG.md](CHANGELOG.md) · [CONTRIBUTING.md](CONTRIBUTING.md)._

## Project Goal

A commercial-grade WordPress Travel CMS plugin that fully replaces WP Travel
Engine for travel agencies (primary market: Bangladesh — first-class Bangla
content support). It manages three content types — **Countries**, **Visas**
and **Tours** — with professional admin editors, a themeable frontend, AJAX
search, Elementor integration, a generic import/export engine, installable
demo data, a full SEO module, and (upcoming) booking/inquiry and AI modules.

**Targets:** PHP 8.2+, WordPress 6.4+, WordPress Coding Standards, PSR-4,
OOP/MVC, Bootstrap 5 frontend, native JavaScript, translation-ready,
security-first, cache-friendly.

## Architecture

```
Plugin (kernel, singleton)
└── Core\Container (reflection auto-wiring DI)
    └── Service providers (register bindings → boot hooks):
        Core → Settings → Admin → Frontend → RestApi → Modules
```

- **Modules** (`includes/Modules/`): self-contained feature packages
  implementing `Contracts\Module`, loaded by `ModuleManager` (filter
  `ztc_modules`). Content modules bundle PostType + Taxonomy + Meta +
  Repository + Service + Editor + ImportMapping.
- **Data layer (M)**: declarative CPT/taxonomy/meta registration
  (`BasePostType`, `BaseTaxonomy`, `BasePostMeta` — every field typed,
  sanitized, REST-exposed; meta keys are class constants).
  `Data\BaseRepository` is the only layer touching WP data APIs;
  `*Service` classes hold business logic and build view-models.
- **Admin UI framework**: `Admin\UI\Editor` — tabbed metaboxes (WAI-ARIA)
  with a guarded save pipeline (nonce → capability → per-field sanitize →
  per-field save → `after_save()` validation). 19 reusable field components
  (Text, Textarea, RichEditor, Number, Url, Select, MultiSelect, Checkbox,
  Toggle, Media, Gallery, Repeater, Faq, Timeline, List, Seo, Taxonomy,
  Duration, Code). The Settings screen reuses the same tabs/fields.
- **Frontend engine (V/C)**: `Frontend\TemplateLoader` routes plugin URLs and
  injects view-models (`ztc_view()`); templates in `templates/frontend/` are
  pure views, theme-overridable at `{theme}/zihad-travel-cms/…`.
  `Views\GridRenderer` is the single card-render path shared by archives,
  shortcodes, Elementor widgets and AJAX search results.
- **REST** (`ztc/v1`): search (public, cacheable), import/export, demo data,
  settings (admin). **WP-CLI**: `wp ztc import|export|…`, `wp ztc demo …`.
- **Multilingual readiness**: `TranslationProvider` contract (null-object
  default, WPML/Polylang adapters pluggable), `wpml-config.xml`, Bangla seed
  data, locale-aware demo generator with stable latin slugs.
- **Lifecycle**: injectable Activator/Deactivator, `Core\Upgrade`
  (version-keyed migrations + deferred rewrite flush), opt-in uninstall
  cleanup.

Full details: [docs/architecture.md](docs/architecture.md).

## Completed Modules

| # | Deliverable | Commit | Highlights |
|---|---|---|---|
| — | **Foundation** | `ad36f06` | Kernel/DI/providers; Country/Visa/Tour CPTs + taxonomies + 55 registered meta fields; repositories/services/cards; admin UI framework; Visa & Country editors; frontend engine (templates, AJAX search, shortcodes, Elementor widgets + dynamic tags); Health page; notifications; demo-ready plumbing |
| 1 | **Tour Editor** | `a9e3b42` | 9 declarative tabs, DurationField, cross-field sale-price validation via `after_save()` |
| 2 | **Import/Export engine** | `f7ef0d3` | Generic mapping contract; CSV/JSON both directions; batched jobs with progress/resume/error log/rollback; duplicate detection (create/update/upsert); image URL sideloading with dedup; REST + WP-CLI + admin progress UI |
| 3 | **Demo Data generator + installer** | `95ddd61` | Data-driven (105 seed countries en+bn, localized template bank); deterministic generator (105 countries / 473 visas / 132 tours, byte-identical regeneration); installer is a thin wrapper over the importer; `en`/`bn` locales with shared slugs |
| 4 | **Global Dashboard & Settings** | `89cc196` | 11-tab settings screen (reusing the field framework); structural SettingsSanitizer on every write path; REST settings API; Elementor SettingTag; frontend Integrations (brand CSS vars, custom CSS/JS, GA, FB Pixel, floating WhatsApp); dashboard widgets (counts, demo status, recent imports) |
| 5 | **SEO** | — | Title/description/keywords, canonical URLs (incl. pagination), robots, OpenGraph + Twitter Cards on all plugin routes; Schema.org JSON-LD via one SchemaService (TouristTrip, GovernmentService, Country, CollectionPage, BreadcrumbList — FAQPage stays with the FAQ part); per-post `ztc_seo` extended with robots/canonical; `seo.*` settings + 12th tab via existing filters; auto-defers to Yoast/Rank Math; 8 `ztc_seo_*` output filters; once-per-request memoized resolution |

**Testing:** 12 standalone smoke suites (WP-function stubs, no WordPress
install needed) covering boot, registration, services, the editor framework,
all three editors, the frontend engine, importer, demo data,
settings/dashboard and SEO — all green after Module 5.

**Docs:** [docs/](docs/) — architecture, tour editor, importer, demo data,
settings, SEO.

## Remaining Roadmap (strict order)

1. **Setup Wizard** — professional first-run installer (company info,
   defaults, demo data, permalink check).
2. **Homepage Search Widget** — tabbed Visa (Country, Visa Type) / Tour
   (Country, Tour Type, Duration, Budget) search; shortcode + Elementor
   widget + AJAX, reusing `SearchService`/`GridRenderer`.
3. **Booking / Inquiry Forms** — visa & tour inquiries, WhatsApp CTA, email
   notifications (settings section already exists).
4. **AI Module** — auto-fill via the editor toolbar hooks + REST-registered
   meta (extension points already in place).

**Release hardening (before 1.0 ship, alongside the modules):** ship real
Bootstrap 5 builds in `assets/vendor/bootstrap/` (currently empty
placeholders); migrate scratchpad test suites into `tests/` with CI
(PHPCS + PHPStan + suites); custom capabilities / Travel Manager role;
licensing & update mechanism; regenerate the `.pot` file.

## Coding Rules

- **Never rewrite the architecture** — every feature extends it (modules,
  providers, filters). Reuse services, renderers and field components; no
  duplicated logic (the demo installer *is* the importer; settings consumers
  read `GlobalSettings`/`Config`).
- PHP 8.2+, WPCS (ruleset in `phpcs.xml.dist`), PSR-4 (`ZihadTravelCMS\` →
  `includes/`), classes injectable via the container (statics only for pure
  helpers and WP hook bridges).
- Prefixes: `ztc_`/`ZTC_`/`.ztc-`; text domain `zihad-travel-cms`; meta keys
  referenced only via `*Meta` class constants.
- Security: nonce + capability on every write; per-field sanitization on
  input, escaping on output; REST file access by media ID only; public REST
  is read-only and nonce-free (cacheable).
- Templates are pure views (theme-overridable); no HTML in business logic;
  no demo/content data hardcoded in PHP.
- Every string translatable; Bangla is a first-class locale.
- Per module: plan bullets first → implement → docs in `/docs` → tests →
  run **all** suites → one logical commit.

## Current Completion

**≈ 70%** toward a shippable 1.0.

| Area | Status |
|---|---|
| Architecture / kernel / data layer | ██████████ 100% |
| Admin editors (Visa, Country, Tour) | ██████████ 100% |
| Frontend engine (templates, search, shortcodes) | █████████░ 90% (homepage widget pending) |
| Elementor integration | ████████░░ 80% (widgets + 3 dynamic tags; more widgets with search/booking) |
| Import/Export + Demo data | ██████████ 100% |
| Dashboard & Settings | ██████████ 100% |
| SEO | ██████████ 100% |
| Setup wizard | ░░░░░░░░░░ 0% |
| Booking / inquiry | █░░░░░░░░░ 10% (settings + WhatsApp CTA exist) |
| AI module | █░░░░░░░░░ 10% (hooks + REST meta ready) |
| Release hardening (assets, CI, caps, licensing) | ██░░░░░░░░ 20% |
