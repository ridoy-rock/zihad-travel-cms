# Zihad Travel CMS — Developer Documentation

Documentation for every module ships here, one file per feature.

| Document | Covers |
|---|---|
| [architecture.md](architecture.md) | Kernel, container, providers, modules, data layer, admin UI framework, frontend engine |
| [tour-editor.md](tour-editor.md) | The tabbed Tour editor, its fields, validation rules and extension points |
| [importer.md](importer.md) | The generic CSV/JSON import/export engine: mappings, jobs, REST, WP-CLI |
| [demo-data.md](demo-data.md) | The regenerable demo content generator and the installer that reuses the importer |
| [settings.md](settings.md) | The settings screen (11 built-in tabs + filter-added tabs), settings data layer, REST API, frontend integrations and dashboard |
| [seo.md](seo.md) | Meta/OpenGraph/Twitter output, canonical URLs, robots, Schema.org JSON-LD, Yoast/Rank Math deferral and the `ztc_seo_*` filters |
| [setup-wizard.md](setup-wizard.md) | The eleven-step first-run wizard: settings orchestration, resume/skip/rerun semantics, no-JS demo install, REST API, extension hooks |
| [search-widget.md](search-widget.md) | The tabbed Visa/Tour search widget: one render path for shortcode/Elementor/homepage, duration mirror, range filters, no-JS archive parity, caching |
| [booking.md](booking.md) | Visa/tour inquiry forms: one submission pipeline (validation, honeypot, rate limit), private Inquiry CPT, Mailer contract, REST endpoint, shared rendering |
| [release-checklist.md](release-checklist.md) | 1.0 release hardening status, security/performance review summaries and the manual QA script |

## Conventions

- **Namespace** `ZihadTravelCMS\` → `includes/` (PSR-4, Composer or built-in fallback autoloader).
- **Prefixes**: options/meta/hooks `ztc_`, constants `ZTC_`, CSS classes `.ztc-`.
- **Text domain**: `zihad-travel-cms`; multilingual field semantics in `wpml-config.xml`.
- **Tests**: standalone smoke suites (WP-function stubs, no WordPress install needed) run with
  `php -d zend.assertions=1 -d assert.exception=1 <suite>.php`.
