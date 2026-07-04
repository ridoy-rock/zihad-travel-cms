# Zihad Travel CMS — Developer Documentation

Documentation for every module ships here, one file per feature.

| Document | Covers |
|---|---|
| [architecture.md](architecture.md) | Kernel, container, providers, modules, data layer, admin UI framework, frontend engine |
| [tour-editor.md](tour-editor.md) | The tabbed Tour editor, its fields, validation rules and extension points |

## Conventions

- **Namespace** `ZihadTravelCMS\` → `includes/` (PSR-4, Composer or built-in fallback autoloader).
- **Prefixes**: options/meta/hooks `ztc_`, constants `ZTC_`, CSS classes `.ztc-`.
- **Text domain**: `zihad-travel-cms`; multilingual field semantics in `wpml-config.xml`.
- **Tests**: standalone smoke suites (WP-function stubs, no WordPress install needed) run with
  `php -d zend.assertions=1 -d assert.exception=1 <suite>.php`.
