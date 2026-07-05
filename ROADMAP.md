# Roadmap — v1.0 to v2.0

Strict module order within each release; every module ships with docs in
`/docs`, automated tests, and one logical commit. Nothing rewrites the
existing architecture — features extend it through modules, providers and
filters.

## v1.0 — Launch (in progress)

Everything required to run a production travel agency site.

| Status | Deliverable |
|---|---|
| ✅ | Foundation: kernel/DI/providers, CPTs + taxonomies + registered meta, repositories/services/cards, admin editor framework, frontend engine, AJAX search, shortcodes, Elementor widgets + dynamic tags, Health page |
| ✅ | Module 1 — Tour Editor (9 tabs, validation) |
| ✅ | Module 2 — Import/Export engine (CSV/JSON, jobs, progress, resume, rollback, REST, WP-CLI) |
| ✅ | Module 3 — Demo Data generator + installer (bilingual, deterministic, importer-driven) |
| ✅ | Module 4 — Global Dashboard & Settings (11 tabs, REST, integrations, dashboard widgets) |
| ✅ | Module 5 — SEO: global defaults + per-post overrides (incl. robots/canonical), meta title/description/keywords, canonical URLs, OpenGraph, Twitter Cards, Schema.org (TouristTrip / GovernmentService / Country / CollectionPage), breadcrumb schema, 8 output filters; auto-defers to Yoast/Rank Math |
| ✅ | Module 6 — Setup Wizard: eleven-step first-run installer (company info, branding, contact, social, WhatsApp, Maps, analytics, homepage, demo data, permalink check); resumable/skippable/rerunnable, no-JS capable, REST-driven, pure orchestration of existing services |
| ✅ | Module 7 — Homepage Search Widget: tabbed Visa (Country, Visa Type) / Tour (Country, Tour Type, Duration, Budget); shortcode + Elementor widget + settings-gated homepage injection; CSS-only tabs + no-JS archive filter parity; duration mirror meta + cached country options; via the existing SearchService/GridRenderer |
| ✅ | Module 8 — Booking / Inquiry: visa & tour inquiry forms (no-JS + REST, honeypot + rate limit), private inquiry CPT with admin columns, WhatsApp CTA, email notifications through the new Mailer contract |
| ✅ | Release hardening: real Bootstrap 5 build shipped, smoke suites in `tests/` + CI (PHPCS/PHPStan/suites), `.pot` regenerated, Plugin Check clean, dual-channel builds (wordpress.org / self-hosted — see [docs/distribution.md](docs/distribution.md)) |
| ⬜ | Custom capabilities ("Travel Manager" role) — moved to v1.1; licensing/update client — moved to the separate Pro add-on plugin |

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
