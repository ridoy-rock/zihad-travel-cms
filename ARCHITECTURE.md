# Architecture

How Zihad Travel CMS is put together. Deeper per-module documentation lives
in [docs/](docs/); this file is the map.

## The Kernel & Modular Architecture

`Plugin` (singleton, booted on `plugins_loaded`) owns a reflection
auto-wiring DI container (`Core\Container`) and boots **service providers**
in two phases — `register()` binds services, then `boot()` attaches hooks:

```
Core → Settings → Admin → Frontend → RestApi → Modules
```

**Modules** are self-contained feature packages under `includes/Modules/`,
implementing `Contracts\Module` (`id()`, `is_available()`, `register()`),
loaded last by `ModuleManager`. A content module bundles everything about its
domain:

```
Modules/Tour/
├── TourModule.php          wires components (admin editor only in wp-admin)
├── TourPostType.php        CPT registration        ─┐
├── TourTypeTaxonomy.php    taxonomy registration    ├─ declarative (M)
├── TourMeta.php            registered meta + keys  ─┘
├── TourRepository.php      data access only
├── TourService.php         business logic + view-models
├── TourEditor.php          tabbed admin editor
└── TourImportMapping.php   import/export mapping
```

Placeholders (Booking, Seo, Ai, Analytics) reserve the same shape for future
work. Everything is extensible without core edits via filters:
`ztc_service_providers`, `ztc_modules`, `ztc_admin_pages`,
`ztc_rest_controllers`, `ztc_import_mappings`, `ztc_settings_tabs`,
`{post_type}_editor_tabs`, `ztc_translation_provider`.

## MVC

- **Model** — declarative registration bases (`PostTypes\BasePostType`,
  `Taxonomies\BaseTaxonomy`, `PostTypes\BasePostMeta`) plus repositories and
  services (below). Every meta field is registered with a type, sanitizer,
  default and REST schema; meta keys exist only as `*Meta` class constants.
- **View** — pure PHP templates in `templates/` (admin, frontend, cards).
  Frontend templates read their view-model via `ztc_view()` and render parts
  via `ztc_part()`; themes override any template by copying it to
  `{theme}/zihad-travel-cms/…`. No queries, no business logic in templates.
- **Controller** — `Frontend\TemplateLoader` (routes plugin URLs on
  `template_include`, builds view-models, hands them over with
  `set_query_var`), REST controllers, and the admin pages/editors.

## Repositories

`Data\BaseRepository` is the **only** layer that touches WordPress data APIs
(`get_post`, `WP_Query`, meta, terms). Concrete repositories add typed
finders (`by_country()`, `by_region()`, `by_type()`). Queries run with
`suppress_filters => false` and language-specific lookups route through the
`Contracts\TranslationProvider` (null-object by default; WPML/Polylang
adapters can replace it) — the multilingual seam.

## Services

Business logic and formatting live in `*Service` classes (never in
templates, never in repositories): price/sale formatting, duration text,
image fallback chains, WhatsApp links with global fallbacks, and the
view-model builders `card_data()` / `page_data()`. Shared cross-cutting
services: `Services\MediaService` (attachment handling),
`Services\NotificationService` (queued admin notices),
`Services\HealthService` (environment checks), `Settings\GlobalSettings`
(typed access to agency identity/branding/integrations).

## Admin Editors

`Admin\UI\Editor` renders one tabbed metabox per post type (WAI-ARIA tabs,
keyboard navigable) and runs a guarded save pipeline:

```
nonce → autosave/revision guard → capability → per-field sanitize()
      → per-field save() → after_save() cross-field validation → action
```

Fields own their persistence: post meta by default, `TaxonomyField` writes
terms. 19 reusable components (Text, Textarea, RichEditor, Number, Url,
Select, MultiSelect, Checkbox, Toggle, Media, Gallery, Repeater, Faq,
Timeline, List, Seo, Taxonomy, Duration, Code) cover every editor **and** the
11-tab Settings screen (`Admin\Pages\SettingsPage`), which swaps post-meta
persistence for one batched, shape-validated option write
(`Settings\SettingsSanitizer`). AI/auto-fill toolbars can inject via
`ztc_editor_render_before/after`.

## REST

Namespace `ztc/v1`:

| Route | Access | Purpose |
|---|---|---|
| `GET /search` | public, nonce-free, `Cache-Control` from settings | AJAX search/filters; card HTML identical to server render |
| `POST/GET /import/*`, `GET /export` | `manage_options` | Import jobs (start/process/status/rollback), exports |
| `POST /demo/generate`, `POST /demo/start` | `manage_options` | Demo data; install reuses `/import/process` |
| `GET/POST /settings` | `manage_options` | Settings read/partial update through the shared sanitizer |

All content fields are additionally exposed on the core post endpoints via
registered meta. Controllers are registered through the
`ztc_rest_controllers` filter.

## Elementor

`Modules\Elementor` loads only after `elementor/loaded`: a "Travel CMS"
widget category (Tours/Visas/Countries grid widgets + CTA — all delegating to
the shared `GridRenderer`), dynamic tags (**Travel CMS Field** for post meta
with service-formatted price/duration, **Hero Image**, **Travel CMS Setting**
for global settings), and Elementor editing support for the plugin CPTs.
Widgets are the integration boundary where Elementor instantiates objects
itself, so they reach the container via `ztc()`.

## Frontend Engine

- `Views\GridRenderer` — the single card-render path used by archives,
  shortcodes, Elementor widgets and AJAX search results (`Views\Cards\*`
  render theme-overridable card templates).
- Templates: three singles + three archives + parts (hero, grid,
  search-form, FAQ with FAQPage JSON-LD, archive body, WhatsApp button).
- `assets/js/frontend.js` — dependency-free progressive enhancement for
  search/filters (debounced, `aria-busy`, no-JS fallback to normal submit).
- `Frontend\Integrations` — settings-driven output: brand CSS variables,
  custom CSS/JS (with closing-tag injection guards), GA + Facebook Pixel,
  floating WhatsApp button.
- `Frontend\Shortcodes` — thin wrappers over the same components.

## Import System

`Modules\Importer` is a generic engine; content types plug in via
`Contracts\ImportMapping` (field → target: `post:*`, `meta:`, `list:`,
`json:`, `terms:`, `relation:`, `image:`/`gallery:`/`thumbnail`). Persisted
`ImportJob`s give batching, live progress, resume-after-interruption, capped
error logs and rollback (manual or all-or-nothing); duplicate detection is
slug-based with `create`/`update`/`upsert` modes; `ImageImporter` sideloads
URLs once per source (`_ztc_source_url`). Exports reverse the same mappings
(CSV with BOM + pipe lists, unescaped-unicode JSON) and are guaranteed to
round-trip. Surfaces: REST, `wp ztc …` WP-CLI commands, and the admin
progress UI. The Demo Data module is the reference consumer: its installer
is a thin wrapper that feeds generated JSON through this engine.

## Lifecycle & Upgrades

Injectable `Activator`/`Deactivator` behind static hook bridges;
`Core\Upgrade` runs version-keyed migrations and the deferred rewrite flush;
uninstall deletes content only when explicitly enabled in settings.
