# Roadmap — v1.0 to v2.0

Strict module order within each release; every module ships with docs in
`/docs`, automated tests, and one logical commit. Nothing rewrites the
existing architecture — features extend it through modules, providers and
filters.

## v1.0 — Launch (in progress)

Everything required to replace WP Travel Engine on a production agency site.

| Status | Deliverable |
|---|---|
| ✅ | Foundation: kernel/DI/providers, CPTs + taxonomies + registered meta, repositories/services/cards, admin editor framework, frontend engine, AJAX search, shortcodes, Elementor widgets + dynamic tags, Health page |
| ✅ | Module 1 — Tour Editor (9 tabs, validation) |
| ✅ | Module 2 — Import/Export engine (CSV/JSON, jobs, progress, resume, rollback, REST, WP-CLI) |
| ✅ | Module 3 — Demo Data generator + installer (bilingual, deterministic, importer-driven) |
| ✅ | Module 4 — Global Dashboard & Settings (11 tabs, REST, integrations, dashboard widgets) |
| ⬜ | Module 5 — SEO: global defaults + per-post overrides, meta title/description, canonical URLs, OpenGraph, Twitter Cards, Schema.org (TouristTrip / GovernmentService / Country), breadcrumb schema; auto-defers to Yoast/Rank Math |
| ⬜ | Module 6 — Setup Wizard: first-run installer (company info, defaults, demo data, permalink check) |
| ⬜ | Module 7 — Homepage Search Widget: tabbed Visa (Country, Visa Type) / Tour (Country, Tour Type, Duration, Budget); shortcode + Elementor widget + AJAX via the existing SearchService |
| ⬜ | Module 8 — Booking / Inquiry: visa & tour inquiry forms, inquiry CPT, WhatsApp CTA, email notifications |
| ⬜ | Release hardening: ship real Bootstrap 5 builds, migrate smoke suites into `tests/` + CI (PHPCS/PHPStan/suites), custom capabilities ("Travel Manager" role), licensing/update mechanism, regenerate `.pot` |

## v1.1 — Operations

- Analytics module (activate the placeholder): view counts, WhatsApp click
  tracking, inquiry conversion stats, dashboard charts (custom table via
  `Core\Upgrade` migrations).
- Inquiry management screen (statuses, notes, export through the importer
  engine's mapping contract).
- Reviews/ratings for tours; review schema.
- More Elementor widgets (search hero, FAQ, itinerary timeline) + theme
  builder single-template support.

## v1.2 — Multilingual & Money

- WPML/Polylang adapters implementing the existing `TranslationProvider`
  contract; bilingual demo install (en + bn linked translations, shared slugs).
- Multi-currency display with conversion rates; Bangla numeral formatting.
- Duration/price numeric mirrors for richer search filtering and sorting.

## v1.5 — AI Module

- Editor toolbar auto-fill (via the existing `ztc_editor_render_before` hook
  + REST-registered meta) for visa requirements, tour itineraries and SEO
  text; provider-agnostic API abstraction with per-module prompts; usage
  limits and review-before-save flow.

## v2.0 — Online Booking Platform

- Real bookings: availability calendars, departure dates, seat inventory.
- Payment gateways (local BD gateways + Stripe/PayPal), deposits, invoices.
- Customer accounts: booking history, document upload for visa processing.
- Notification center (email/SMS/WhatsApp templates).
- Expanded public REST API for mobile apps / headless frontends.

---

Sequencing beyond v1.0 may be re-prioritized; within a release the module
order is binding. See [PROJECT_STATUS.md](PROJECT_STATUS.md) for the current
position.
