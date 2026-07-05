# Zihad Travel CMS

A commercial-grade WordPress Travel CMS plugin for travel
agencies. Manage **Countries**, **Visas**
and **Tours** with professional tabbed editors, a themeable Bootstrap 5
frontend, AJAX search, Elementor widgets and dynamic tags, a generic
import/export engine, and installable bilingual (English/Bangla) demo data.

> Development status: pre-release. See [PROJECT_STATUS.md](PROJECT_STATUS.md)
> and [ROADMAP.md](ROADMAP.md).

## Features

- **Three content types** — Countries (destination guides), Visas (services
  with requirements, documents, application process), Tours (packages with
  itinerary, pricing, hotels, gallery) — plus Visa Type, Tour Type and Region
  taxonomies. Every field is registered post meta: typed, sanitized and
  REST-exposed (Gutenberg / Elementor / headless ready).
- **Professional admin editors** — reusable tabbed editor framework
  (WAI-ARIA tabs, 19 field components incl. repeaters, FAQ/timeline builders,
  media/gallery pickers) with guarded save pipelines and cross-field
  validation. No default metaboxes, no duplicated UI.
- **Frontend engine** — MVC template loader with archive + single templates
  for all three types, fully theme-overridable
  (`{theme}/zihad-travel-cms/frontend/…`); mobile-first CSS; FAQ accordions
  with FAQPage JSON-LD; SEO-friendly URLs (`/tour/`, `/visa/`, `/country/`,
  `/visa-type/`, `/tour-type/`, `/region/`).
- **AJAX search & filters** — public, cacheable REST endpoint
  (`GET ztc/v1/search`) with keyword, region, type, country, price, duration
  and budget filters; progressive enhancement (works without JavaScript —
  archive URLs filter server-side).
- **Homepage search widget** — tabbed Visa (Country, Visa Type) / Tour
  (Country, Tour Type, Duration, Budget) search with CSS-only tabs; one
  shared render path for the shortcode, the Elementor widget and the
  optional homepage auto-injection.
- **Booking / inquiries** — visa & tour inquiry forms on the single pages
  (plus shortcode/Elementor placements) feeding a private Inquiries screen;
  works without JavaScript, honeypot + rate-limit protected; email
  notifications through a swappable mail abstraction (`ztc_mailer`).
- **Elementor** — "Travel CMS" widget category (Tours/Visas/Countries grids,
  CTA, Travel Search, Inquiry Form) and dynamic tags (post fields, hero
  image, global settings).
- **Shortcodes** — `[ztc_tours]`, `[ztc_visas]`, `[ztc_countries]`,
  `[ztc_search]`, `[ztc_search_widget]`, `[ztc_inquiry_form]`, `[ztc_cta]`.
- **Import / Export** — generic CSV/JSON engine with batched jobs, live
  progress, resume, error logs, duplicate detection (create/update/upsert),
  rollback, and image sideloading from URLs. REST + WP-CLI + admin UI.
  Extensible: any module can register an import mapping.
- **Demo data** — deterministic generator (105 countries, 473 visas,
  132 tours) built from data files with full Bangla support; installs
  through the importer, re-installs update instead of duplicating.
- **SEO** — meta title/description/keywords, canonical URLs, robots
  directives, OpenGraph, Twitter Cards and Schema.org JSON-LD
  (TouristTrip, GovernmentService, Country, BreadcrumbList, FAQPage) with
  per-post overrides and global defaults; automatically defers to
  Yoast SEO / Rank Math; every output filterable (`ztc_seo_*`).
- **Setup wizard** — eleven-step first-run installer (resumable,
  skippable, no-JS capable, REST-driven) that writes through the normal
  settings pipeline and installs demo data via the import engine;
  re-runnable from Travel CMS → Setup.
- **Settings & dashboard** — 12-tab settings screen (branding, contact,
  WhatsApp, Maps/Analytics/Pixel, booking, performance, custom CSS/JS),
  REST settings API, dashboard widgets (content counts, demo status,
  recent imports), floating WhatsApp button, brand-color CSS variables.
- **Engineering** — PSR-4 + DI container, service providers, module system,
  WP-CLI commands, version-keyed upgrade migrations, opt-in uninstall
  cleanup, translation-ready (`wpml-config.xml`, Bangla-first demo data).

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| WordPress | 6.4+ |
| Elementor (optional) | 3.x — only for the Elementor widgets/tags |
| Permalinks | "Pretty" permalinks required for `/tour/` etc. |

## Installation

1. Copy (or clone) the plugin into `wp-content/plugins/zihad-travel-cms`.
2. (Optional, dev) `composer install` — the plugin ships a PSR-4 fallback
   autoloader, so Composer is not required at runtime.
3. Activate **Zihad Travel CMS** on the Plugins screen.
4. The **Setup Wizard** opens automatically — company info, branding,
   contact channels, tracking and optional demo data in eleven guided,
   skippable steps (re-run anytime from **Travel CMS → Setup**).
5. Fine-tune under **Travel CMS → Settings**; check **Travel CMS →
   Health**.
6. Demo content (if not installed during setup): **Travel CMS →
   Import / Export → Demo Data → Install**, or `wp ztc demo install`.

## Folder Structure

```
zihad-travel-cms/
├── zihad-travel-cms.php     Bootstrap: constants, requirements guard, autoload, hooks
├── uninstall.php            Opt-in data cleanup (multisite-aware)
├── composer.json            PSR-4: ZihadTravelCMS\ → includes/
├── phpcs.xml.dist           WordPress Coding Standards ruleset
├── wpml-config.xml          Multilingual field semantics (WPML/Polylang)
├── assets/                  css/, js/ (admin + frontend), images/, vendor/bootstrap/
├── demo-data/               Generated demo JSON + sources/ (seeds & templates)
├── docs/                    Per-module developer documentation
├── includes/                All PHP (PSR-4 root)
│   ├── Plugin.php           Kernel (DI container + service providers)
│   ├── Core/                Container, Config, Assets, Upgrade, lifecycle
│   ├── Contracts/           Registrable, Module, ImportMapping, TranslationProvider
│   ├── Admin/               Menu, dashboard, pages (Settings, Health, Import/Export)
│   │   └── UI/              Tabbed editor framework + 19 field components
│   ├── Frontend/            TemplateLoader, Shortcodes, Integrations
│   ├── Views/               GridRenderer + card components
│   ├── Data/                BaseRepository
│   ├── PostTypes/ Taxonomies/  Declarative registration bases
│   ├── RestApi/ Settings/ Services/ Helpers/ Translations/
│   └── Modules/             Country, Visa, Tour, Search, Importer, DemoData,
│                            Elementor + placeholders (Booking, Seo, Ai, Analytics)
├── templates/               Pure views: admin/, frontend/ (theme-overridable), cards/
└── languages/               zihad-travel-cms.pot
```

## Development Setup

```bash
git clone <repo> wp-content/plugins/zihad-travel-cms
cd wp-content/plugins/zihad-travel-cms
composer install          # dev tools: PHPCS/WPCS, PHPCompatibility, PHPStan
composer lint             # WordPress Coding Standards
composer stan             # PHPStan level 5
composer test             # all smoke suites (tests/run.sh)
composer build:wporg      # WordPress.org zip (Update URI stripped, channel gate)
composer build:pro        # self-hosted/development zip (identical to the source tree)
```

- **Tests**: standalone smoke suites in [tests/](tests/)
  (WordPress-function stubs — no WP install needed); CI runs them on
  PHP 8.2/8.3 plus PHPCS and PHPStan on every push.
  See [CONTRIBUTING.md](CONTRIBUTING.md#testing-requirements).
- **WP-CLI**: `wp ztc import|export|import-status|import-rollback`,
  `wp ztc demo generate|install`.
- **Docs**: start with [ARCHITECTURE.md](ARCHITECTURE.md), then the
  per-module docs in [docs/](docs/).

## License

GPL-2.0-or-later.
