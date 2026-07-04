# SEO Module

`Modules\Seo` renders search-engine and social metadata on every plugin
route (the tour/visa/country singles, their archives and the
visa-type/tour-type/region taxonomy archives): document title, meta
description/keywords, canonical URLs, robots directives, OpenGraph,
Twitter Cards and a Schema.org JSON-LD graph. It automatically steps
aside while Yoast SEO or Rank Math is active.

## Components

| Class | Role |
|---|---|
| `SeoModule` | Wires the components. Always loads (see *Deferral*). |
| `SeoSettings` | Adds the `seo.*` section to the settings schema (`ztc_default_settings`) and the SEO tab to the settings screen (`ztc_settings_tabs`). |
| `SeoService` | Resolves the effective SEO values for the current request — computed **once per request** and memoized. |
| `SchemaService` | The single place JSON-LD nodes are built (`node()` + recursive empty-value cleaning). New schema types are one builder method away. |
| `SeoOutput` | The head render pipeline: `document_title_parts`, `wp_head` (priority 1), `wp_robots`, plus `rel_canonical` takeover on plugin routes. |

## Value resolution (precedence)

For every value: **per-post `ztc_seo` meta → `seo.*` global settings →
derived from content**.

| Value | Meta key | Setting | Derived fallback |
|---|---|---|---|
| Title | `ztc_seo.title` | `seo.archive_{type}_title` (archives) | `{post title / archive label} {separator} {site name}` |
| Description | `ztc_seo.description` | `seo.archive_{type}_description`, `seo.default_description` | Country short description, then excerpt (trimmed to ~160 chars) |
| Keywords | `ztc_seo.keywords` | `seo.default_keywords` | — |
| Canonical | `ztc_seo.canonical` | — | Permalink / archive link / term link, `/page/N/` on paginated archives |
| Robots | `ztc_seo.robots` | `seo.noindex_archives` (archives) | index,follow |
| Social image | — | `seo.default_og_image`, logo | The content's hero fallback chain (hero meta → featured → gallery) via the content services |

The `ztc_seo` meta object (registered on all three post types, edited
through the shared `SeoField` on every editor's SEO tab, REST-exposed)
now carries five properties: `title`, `description`, `keywords`,
`robots` (`''`, `noindex`, `nofollow`, `noindex,nofollow`) and
`canonical` (URL override).

## Settings (`seo.*`, SEO tab)

`enabled`, `title_separator`, `default_description`, `default_keywords`,
`default_og_image` (attachment ID), `twitter_handle`, `og_enabled`,
`twitter_enabled`, `schema_enabled`, `noindex_archives`, and per-type
archive title/description
(`archive_tour_title`, `archive_tour_description`, visa/country
equivalents). All flow through the shared `SettingsSanitizer`, the
`admin-post` form pipeline and `GET/POST ztc/v1/settings` with no
settings-code changes — the schema arrives via the
`ztc_default_settings` filter. Translatable strings are registered in
`wpml-config.xml` (admin-texts).

## Deferral (Yoast / Rank Math)

`SeoService::deferred()` detects Yoast SEO (`WPSEO_VERSION`) and
Rank Math (`RANK_MATH_VERSION`) and every `SeoOutput` callback re-checks
`SeoService::enabled()` (deferral + the `seo.enabled` toggle), so the
whole pipeline is inert while another SEO plugin runs — no duplicate
tags. Override detection with the `ztc_seo_defer` filter.

The **module itself always loads**: if it unloaded, the `seo.*` schema
would vanish from `Config::defaults()` and the structural
`SettingsSanitizer` would silently drop saved SEO settings on the next
settings write.

## Schema.org output

One `<script type="application/ld+json">` per page with an
`@context`/`@graph` envelope:

| Route | Nodes |
|---|---|
| Single tour | `TouristTrip` (itinerary `ItemList`, `Offer` with numeric price + currency, `TravelAgency` provider) + `BreadcrumbList` |
| Single visa | `GovernmentService` (serviceType from the visa type, `areaServed` country, fee offer, provider) + `BreadcrumbList` |
| Single country | `Country` (`alternateName` = Bangla name) + `BreadcrumbList` |
| Archives | `CollectionPage` + `BreadcrumbList` |

Every node is built through `SchemaService::node()`, which recursively
strips empty properties — graphs stay valid Schema.org and Google Rich
Results compatible. Breadcrumbs follow Google's guidelines (the last
item carries no URL).

**FAQPage** is *not* emitted here: it ships with the frontend FAQ
template part (`templates/frontend/parts/faq.php`), next to the visible
questions it describes, as required by Google's structured-data
guidelines. `SchemaService` intentionally never duplicates it.

## Developer filters

Every generated output is filterable before rendering:

| Filter | Payload |
|---|---|
| `ztc_seo_title` | `(string $title, array $data)` |
| `ztc_seo_description` | `(string $description, array $data)` |
| `ztc_seo_canonical` | `(string $canonical, array $data)` |
| `ztc_seo_robots` | `(string $robots, array $data)` |
| `ztc_seo_opengraph` | `(array $property_to_content, array $data)` |
| `ztc_seo_twitter` | `(array $name_to_content, array $data)` |
| `ztc_seo_schema` | `(array $graph, array $data)` — return `array()` to suppress |
| `ztc_seo_head` | `(string $head_markup, array $data)` — the assembled block |
| `ztc_seo_defer` | `(bool $defer)` — force/disable third-party deferral |

The four value filters run inside `SeoService::data()` **before
memoization**, so they execute exactly once per request; add them
before the first head render (e.g. on `init`).

## Performance

`SeoService::data()` memoizes the fully resolved (and filtered) array
for the request; title, robots, head tags and schema all read from the
same computation. `refresh()` drops the memo (tests, long-running
processes).

## Elementor & REST

- Head output hooks `wp_head`, so pages rendered through Elementor
  theme-builder templates get identical tags; the pipeline is skipped
  inside the Elementor editor preview (`elementor-preview`).
- `ztc_seo` (including the new `robots`/`canonical`) is registered meta
  with a REST schema — readable/writable on the core post endpoints.
- The `seo.*` section is readable/writable through
  `GET/POST ztc/v1/settings`.

## Multilingual

Per-post values live in translatable `ztc_seo` meta; canonical URLs
derive from permalinks (filtered per-language by WPML/Polylang);
`og:locale` uses `get_locale()`; the `seo.*` default texts are
registered as WPML admin-texts.

## Testing

`seo-smoke.php` (standalone, WP-stubs with a real hook system) covers:
settings schema + sanitizer round-trip, the 12-tab settings screen,
meta-schema extension (hostile input, `javascript:` canonical
rejection), `SeoField` whitelisting, resolution precedence, request
memoization, archive/taxonomy/paged canonicals, robots merging, escaped
head output, all nine filters, JSON-LD graph validity (types, offers,
breadcrumb positions, no FAQPage) and the three deferral paths
(settings toggle, `ztc_seo_defer`, Yoast constant).
