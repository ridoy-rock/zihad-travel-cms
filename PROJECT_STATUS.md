# Zihad Travel CMS — Project Status

_Last updated: 2026-07-05 (after Module 8 + release hardening)._

_See also: [README.md](README.md) · [ARCHITECTURE.md](ARCHITECTURE.md) · [ROADMAP.md](ROADMAP.md) (v1.0 → v2.0) · [CHANGELOG.md](CHANGELOG.md) · [CONTRIBUTING.md](CONTRIBUTING.md)._

## Project Goal

A commercial-grade WordPress Travel CMS plugin for travel agencies
(primary market: Bangladesh — first-class Bangla content support). It manages three content types — **Countries**, **Visas**
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
| 5 | **SEO** | `d5542e0` | Title/description/keywords, canonical URLs (incl. pagination), robots, OpenGraph + Twitter Cards on all plugin routes; Schema.org JSON-LD via one SchemaService (TouristTrip, GovernmentService, Country, CollectionPage, BreadcrumbList — FAQPage stays with the FAQ part); per-post `ztc_seo` extended with robots/canonical; `seo.*` settings + 12th tab via existing filters; auto-defers to Yoast/Rank Math; 8 `ztc_seo_*` output filters; once-per-request memoized resolution |
| 6 | **Setup Wizard** | `36a693c` | Eleven-step first-run installer that only orchestrates existing services (settings pipeline, demo installer, health checks, dashboard data); resumable/skippable/rerunnable, independent per-step saves, pre-filled fields (no silent overwrites), no-JS demo install via bounded import batches, one-shot activation redirect, `ztc/v1/wizard*` REST, `ztc_wizard_steps` filter + lifecycle actions |
| 7 | **Homepage Search Widget** | `ac2526b` | Tabbed Visa (Country, Visa Type) / Tour (Country, Tour Type, Duration, Budget) search; one render path for shortcode + Elementor "Travel Search" widget + settings-gated homepage injection; CSS-only tabs, no-JS archive parity via `ArchiveFilters` (`pre_get_posts` reusing `SearchService::filter_clauses()`); `ztc_duration_days` numeric mirror + sync; cached country options; validated `duration`/`budget` REST range params |
| 8 | **Booking / Inquiry Forms** | `99626a7` | Visa/tour inquiry forms → private `ztc_inquiry` CPT (REST-hidden, admin columns); one pipeline for the nonce-protected no-JS handler + validated public `POST ztc/v1/inquiry` (honeypot, per-IP rate limit, server-side validation); `Contracts\Mailer` abstraction (`ztc_mailer`) with filterable notifications; one render path (single templates via `ztc_template_view`, `[ztc_inquiry_form]`, Elementor "Inquiry Form") |
| — | **Release hardening** | `b9a8b4b`…`38d7462` | Tests → `tests/` + CI (PHP 8.2/8.3, PHPCS, PHPStan); Bootstrap 5.3.3 shipped; PHPCS + PHPStan level 5 clean; security/capability/performance audit; `.pot` regenerated (534 strings); LICENSE + Update URI; `.distignore` + `bin/package.sh` verified zip; `docs/release-checklist.md` with the manual QA script |

**Testing:** 15 standalone smoke suites (WP-function stubs, no WordPress
install needed) covering boot, registration, services, the editor framework,
all three editors, the frontend engine, importer, demo data,
settings/dashboard, SEO, the setup wizard, the search widget and
booking/inquiries — all green after Module 8.

**Docs:** [docs/](docs/) — architecture, tour editor, importer, demo data,
settings, SEO, setup wizard, search widget, booking.

## Remaining Roadmap

1. **Manual QA before tagging 1.0.0** — run the on-site script in
   [docs/release-checklist.md](docs/release-checklist.md) (fresh install,
   wizard + demo, search, inquiries, Elementor, mobile) on the staged
   Local "testing" site.
2. **v1.1+** — Travel Manager role, inquiry management screen, analytics,
   reviews, WPML/Polylang adapters, AI module (see [ROADMAP.md](ROADMAP.md)).
   Licensing and the update client move to the separate Pro add-on plugin
   (see [docs/distribution.md](docs/distribution.md)).

**Distribution (2026-07-05):** freemium strategy — the free plugin ships on
wordpress.org, fully functional; Pro arrives later as a separate add-on
plugin extending the public `ztc_*` seams. Two build channels from one
source tree: `composer build:wporg` (Update URI stripped, updater/licensing
gate) and `composer build:pro` (self-hosted, identical to today). Plugin
Check: 0 ERRORs on the wporg build. See
[docs/distribution.md](docs/distribution.md).

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

**≈ 98%** toward a shippable 1.0 — code complete and hardened; manual
on-site QA is the last gate.

| Area | Status |
|---|---|
| Architecture / kernel / data layer | ██████████ 100% |
| Admin editors (Visa, Country, Tour) | ██████████ 100% |
| Frontend engine (templates, search, shortcodes) | ██████████ 100% |
| Elementor integration | ██████████ 100% (6 widgets + 3 dynamic tags) |
| Import/Export + Demo data | ██████████ 100% |
| Dashboard & Settings | ██████████ 100% |
| SEO | ██████████ 100% |
| Setup wizard | ██████████ 100% |
| Booking / inquiry | ██████████ 100% |
| AI module | █░░░░░░░░░ 10% (hooks + REST meta ready) |
| Release hardening (assets, CI, analysis, packaging) | █████████░ 95% (manual QA + update client pending) |
