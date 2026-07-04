# Homepage Search Widget

The tabbed Visa/Tour search widget: **Visas** (keyword, Country, Visa
Type) and **Tours** (keyword, Country, Tour Type, Duration, Budget).
One render path — `Views\SearchWidgetRenderer` + the theme-overridable
`templates/frontend/parts/search-widget.php` part — serves all three
surfaces, so no markup is ever duplicated:

| Surface | Entry point |
|---|---|
| Shortcode | `[ztc_search_widget tabs="visa,tour" default="visa" heading=""]` |
| Elementor | "Travel Search" widget (Travel CMS category) — controls for heading, tabs, open tab |
| Homepage | Auto-prepended to the front page content when **Settings → Homepage → Search Widget** is on (skipped on Elementor-built front pages; veto via the `ztc_show_homepage_search` filter) |

## Progressive enhancement (twice over)

- **Tabs** are CSS-only radio tabs — switching needs no JavaScript.
- **Forms** submit normally to their type's archive
  (`/tour/`, `/visa/`), where `Modules\Search\ArchiveFilters` applies
  the *same* filter clauses to the main query via `pre_get_posts` —
  no-JS search has full parity and every filtered result set has a
  plain, shareable, cacheable URL.
- **With JavaScript**, the existing `frontend.js` drives the forms
  through `GET ztc/v1/search` into the widget's results container —
  cards pixel-identical to server renders (shared `GridRenderer`).

## Duration filtering (performance)

Tour duration is stored as a `{days, nights}` object — not queryable.
Module 7 adds a numeric mirror, **`ztc_duration_days`**
(`TourMeta::DURATION_DAYS`, registered int meta), maintained by
`TourDurationSync` on every write path (editor, REST, importer, demo
installer) via the `added/updated/deleted_post_meta` hooks. Duration
filters run as plain `NUMERIC` meta queries. Existing pre-release
content backfills on the next save or demo re-install.

## Range parameters

The widget's single-select Duration and Budget dropdowns post
`"min-max"` values (`0` max = open-ended, e.g. `15-0`):

- `duration` — day range against `ztc_duration_days`.
- `budget` — price range against `ztc_price` (labels rendered in the
  site currency).

Both are validated on the REST route (pattern + sanitizer), parsed by
`SearchService::parse_range()` (malformed input is ignored), and served
by `SearchService::filter_clauses()` — the **one** translation of
request parameters into SQL clauses, shared by the REST endpoint and
the archive filters. `min_price`/`max_price` keep working unchanged.

## Caching & queries

- Country options (both tabs share them) are fetched **once** and
  cached in the `ztc_country_options` transient for 30 minutes;
  `SearchModule` flushes it on country save/delete. One query per cache
  window, zero per render.
- Term lists use `get_terms()` (core object-cached).
- The part renders entirely from the prepared view-model — no queries
  in templates, no per-row lookups.
- REST responses keep the settings-driven `Cache-Control` header.

## Extension points

- `ztc_search_widget_data` — the full view-model before rendering.
- `ztc_search_widget_durations` — duration options (`"min-max"` => label).
- `ztc_search_widget_budget_steps` / `ztc_search_widget_budgets` —
  budget thresholds / final options.
- `ztc_show_homepage_search` — veto the homepage injection.
- `ztc_search_query_args` (existing) — final WP_Query args.
- Theme override: copy the part to
  `yourtheme/zihad-travel-cms/frontend/parts/search-widget.php`.

## Multilingual

All labels translatable; term names/country titles arrive translated
via the query layer (`suppress_filters => false`); duration/budget
option labels are translatable strings; `ztc_duration_days` is declared
`copy` in `wpml-config.xml`.

## Testing

`search-widget-smoke.php` covers: range parsing and clause generation
(incl. malformed input and min/max regression), the shared
`filter_clauses()` API, the duration mirror sync on all meta paths,
REST arg validation (pattern accepts `4-7`/empty, rejects injection),
country-option caching + invalidation, widget markup (CSS tabs, no-JS
archive actions, all five filters, hostile-title escaping), byte-equal
shortcode/Elementor output, archive filtering (main query only, admin
untouched, junk params ignored) and the homepage injection guards.
